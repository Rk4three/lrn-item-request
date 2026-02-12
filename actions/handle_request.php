<?php
// handle_request.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_role = $_SESSION['job_level'] ?? '';
$user_title = $_SESSION['position_title'] ?? '';

function getApproverSignature()
{
    $name = $_SESSION['full_name'] ?? 'Unknown';
    $title = $_SESSION['position_title'] ?? 'Approver';
    return $name . " - " . $title;
}

function hasPermission($conn, $requestId, $userTitle)
{
    $userId = $_SESSION['user_id'] ?? 0;

    // 1. Super Approvers (ID 4 and 1320) - Can approve EVERYTHING
    if ($userId == 4 || $userId == 1320) {
        return true;
    }

    // 2. Check if this is a Uniform/PPE request
    $stmtU = $conn->prepare("SELECT COUNT(*) FROM RequestItems WHERE request_id = :id AND (category LIKE '%Uniform%' OR category LIKE '%PPE%')");
    $stmtU->execute([':id' => $requestId]);
    $isUniformRequest = $stmtU->fetchColumn() > 0;

    // 3. Laundry Management Logic
    // "Laundry supervisors/managers"
    $isLaundry = (stripos($userTitle, 'Laundry') !== false) || (stripos($_SESSION['dept'] ?? '', 'Laundry') !== false);
    $isManagement = (stripos($userTitle, 'Supervisor') !== false) || (stripos($userTitle, 'Manager') !== false);
    $isLaundryManager = $isLaundry && $isManagement;

    if ($isUniformRequest) {
        // STRICT RULE: Only Users 4, 1320 OR Laundry Managers can approve Uniforms
        if ($isLaundryManager) {
            return true;
        }
        return false; // Everyone else is denied for Uniforms
    }

    // 4. Standard Logic for Non-Uniform Requests

    // Get the required approver department from the request
    $stmt = $conn->prepare("SELECT approver FROM RequestItems WHERE request_id = :id LIMIT 1");
    $stmt->execute([':id' => $requestId]);
    $requiredDepartment = $stmt->fetchColumn();

    // If no department specified, allow any management (fallback)
    if (empty($requiredDepartment)) {
        return $isManagement;
    }

    // Check if user is in Approvers table (from session)
    $is_approver_in_db = $_SESSION['is_approver'] ?? false;
    $approver_departments = $_SESSION['approver_departments'] ?? [];

    // User must be management to approve anything
    if (!$isManagement) {
        return false;
    }

    // Check if user's own department matches the request's department
    $userDepartment = trim($_SESSION['dept'] ?? '');
    $requiredDepartment = trim($requiredDepartment);

    // Check partial/case-insensitive match
    $isAuthorized = false;
    if (!empty($userDepartment) && !empty($requiredDepartment)) {
        if (
            stripos($requiredDepartment, $userDepartment) !== false ||
            stripos($userDepartment, $requiredDepartment) !== false
        ) {
            $isAuthorized = true;
        }
    }

    if (!$isAuthorized) {
        foreach ($approver_departments as $dept) {
            $dept = trim($dept);
            if (
                stripos($requiredDepartment, $dept) !== false ||
                stripos($dept, $requiredDepartment) !== false
            ) {
                $isAuthorized = true;
                break;
            }
        }
    }

    // Plant Manager override
    if (stripos($userTitle, 'Plant Manager') !== false) {
        return true;
    }

    return $isAuthorized;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- APPROVE ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'approve') {
        $id = $_POST['request_id'];

        if (!hasPermission($conn, $id, $user_title)) {
            $_SESSION['message'] = "Unauthorized: Your position ($user_title) cannot approve this request.";
            $_SESSION['msg_type'] = "error";
            header("Location: ../index.php");
            exit();
        }

        $signature = getApproverSignature();

        $remarks = $_POST['approval_remarks'] ?? null;

        try {
            $sql = "UPDATE Requests SET 
                    status = 'Approved', 
                    rejection_reason = :remarks, 
                    updated_at = CURRENT_TIMESTAMP,
                    action_by = :by,
                    action_date = CURRENT_TIMESTAMP
                    WHERE request_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':remarks' => $remarks, ':by' => $signature, ':id' => $id]);

            $_SESSION['message'] = "Request Approved Successfully.";
            $_SESSION['msg_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['msg_type'] = "error";
        }
        header("Location: ../index.php");
        exit();
    }

    // --- REJECT ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'reject') {
        $id = $_POST['request_id'];

        if (!hasPermission($conn, $id, $user_title)) {
            $_SESSION['message'] = "Unauthorized: Your position ($user_title) cannot reject this request.";
            $_SESSION['msg_type'] = "error";
            header("Location: ../index.php");
            exit();
        }

        $reason = $_POST['rejection_reason'] ?? 'No reason provided';
        $signature = getApproverSignature();

        try {
            $sql = "UPDATE Requests SET 
                    status = 'Rejected', 
                    rejection_reason = :reason, 
                    updated_at = CURRENT_TIMESTAMP,
                    action_by = :by,
                    action_date = CURRENT_TIMESTAMP
                    WHERE request_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':reason' => $reason, ':by' => $signature, ':id' => $id]);

            $_SESSION['message'] = "Request Rejected.";
            $_SESSION['msg_type'] = "warning";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['msg_type'] = "error";
        }
        header("Location: ../index.php");
        exit();
    }

    // --- UNDO ACTION ---
    if (isset($_POST['action']) && $_POST['action'] === 'undo') {
        $id = $_POST['request_id'];

        if (!hasPermission($conn, $id, $user_title)) {
            $_SESSION['message'] = "Unauthorized action.";
            $_SESSION['msg_type'] = "error";
            header("Location: ../index.php");
            exit();
        }

        // PREVENT UNDO IF APPROVED
        $checkStmt = $conn->prepare("SELECT status FROM Requests WHERE request_id = :id");
        $checkStmt->execute([':id' => $id]);
        $currentStatus = $checkStmt->fetchColumn();

        if ($currentStatus === 'Approved') {
            $_SESSION['message'] = "Cannot undo an Approved request.";
            $_SESSION['msg_type'] = "error";
            header("Location: ../index.php");
            exit();
        }

        try {
            $sql = "UPDATE Requests SET status = 'Pending', rejection_reason = NULL, action_by = NULL, action_date = NULL, updated_at = CURRENT_TIMESTAMP WHERE request_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);

            $_SESSION['message'] = "Action Undone. Request is now Pending.";
            $_SESSION['msg_type'] = "info";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['msg_type'] = "error";
        }
        header("Location: ../index.php");
        exit();
    }



    // --- CREATE / UPDATE ACTION ---
    try {
        $isUpdate = isset($_POST['request_id']) && !empty($_POST['request_id']);

        $conn->beginTransaction();

        $requestor = $_POST['requestor'];
        $emp_id = $_POST['emp_id'];
        // NEW: Grab photo ID
        $photo_id = $_POST['photo_id'] ?? null;
        $dept = $_POST['department'];
        $area = $_POST['assigned_area'];
        $date_needed = $_POST['date_needed'];
        $time_needed = $_POST['time_needed'];
        $auth_deduct = isset($_POST['auth_deduct']) ? 1 : 0;
        $company_issued = isset($_POST['company_issued']) ? 1 : 0;

        // If company issued, waive deduction override removed. We store both flags.

        if ($isUpdate) {
            // Update Header
            $requestID = $_POST['request_id'];
            $sqlHeader = "UPDATE Requests SET assigned_area = :area, date_needed = :date_needed, 
                          time_needed = :time_needed, auth_deduct = :auth_deduct, 
                          is_company_issued = :is_company_issued, updated_at = CURRENT_TIMESTAMP 
                          WHERE request_id = :id";
            $stmtHeader = $conn->prepare($sqlHeader);
            $stmtHeader->execute([
                ':area' => $area,
                ':date_needed' => $date_needed,
                ':time_needed' => $time_needed,
                ':auth_deduct' => $auth_deduct,
                ':is_company_issued' => $company_issued,
                ':id' => $requestID
            ]);

            $delItems = $conn->prepare("DELETE FROM RequestItems WHERE request_id = :id");
            $delItems->execute([':id' => $requestID]);

            $msg = "Request updated successfully!";

        } else {
            // Insert Header - ADDED requestor_photo_id
            $sqlHeader = "INSERT INTO Requests (requestor_name, employee_id, requestor_photo_id, department, assigned_area, date_needed, time_needed, auth_deduct, is_company_issued, created_at) 
                          VALUES (:requestor, :emp_id, :photo_id, :dept, :area, :date_needed, :time_needed, :auth_deduct, :is_company_issued, CURRENT_TIMESTAMP)";
            $stmtHeader = $conn->prepare($sqlHeader);
            $stmtHeader->execute([
                ':requestor' => $requestor,
                ':emp_id' => $emp_id,
                ':photo_id' => $photo_id, // Bind photo_id
                ':dept' => $dept,
                ':area' => $area,
                ':date_needed' => $date_needed,
                ':time_needed' => $time_needed,
                ':auth_deduct' => $auth_deduct,
                ':is_company_issued' => $company_issued
            ]);
            $requestID = $conn->lastInsertId();
            $msg = "Request created successfully!";
        }

        // Insert Items
        $sqlItem = "INSERT INTO RequestItems (request_id, category, item_name, item_code, sub_group, uom, size, quantity, approver, items_needed) 
                    VALUES (:request_id, :category, :item_name, :item_code, :sub_group, :uom, :size, :quantity, :approver, :items_needed)";
        $stmtItem = $conn->prepare($sqlItem);

        // Include uniform helper for validation
        require_once '../includes/uniform_helper.php';

        if (isset($_POST['items']) && !empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['item_name']))
                    continue;

                $category = strtolower($item['category'] ?? '');
                $quantity = (int) ($item['qty'] ?? 1);

                if ($quantity < 1) {
                    throw new Exception("Quantity for item '{$item['item_name']}' must be at least 1.");
                }

                // --- UNIFORM VALIDATION ---
                if (strpos($category, 'uniform') !== false) {
                    // Check uniform eligibility
                    $eligibility = checkUniformRequestEligibility(
                        $conn,
                        $emp_id,
                        $item['item_name'],
                        $dept,
                        $_SESSION['position_title'] ?? '',
                        $item['size'] ?? null
                    );

                    if (!$eligibility['allowed']) {
                        throw new Exception("Uniform request denied for '{$item['item_name']}': {$eligibility['reason']}");
                    }

                    // Enforce quantity limit
                    if ($quantity > $eligibility['remaining']) {
                        throw new Exception("Quantity limit exceeded for '{$item['item_name']}'. Maximum allowed: {$eligibility['remaining']} ({$eligibility['issuance']} issuance).");
                    }
                }
                // --- END UNIFORM VALIDATION ---

                $stmtItem->execute([
                    ':request_id' => $requestID,
                    ':category' => $item['category'],
                    ':item_name' => $item['item_name'],
                    ':item_code' => $item['item_code'],
                    ':sub_group' => $item['sub_group'],
                    ':uom' => $item['uom'],
                    ':size' => $item['size'] ?? null,
                    ':quantity' => $quantity,
                    ':approver' => $dept, // Force Approver = Requestor Department
                    ':items_needed' => $item['items_needed'] ?? null
                ]);
            }
        }

        $conn->commit();
        $_SESSION['message'] = $msg;
        $_SESSION['msg_type'] = "success";
        header("Location: ../index.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "error";
        header("Location: ../create.php");
        exit();
    }
}
?>