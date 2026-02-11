<?php
// auth/login_action.php
session_start();
require_once '../includes/db.php'; // Reuse connection from include

// Helper: Legacy Password Check for mock data (md5, sha1, plain)
function checkLegacyPassword($inputPassword, $storedHash)
{
    // Hash check logic (for existing users if using real password logic)
    // For mock: we check plain text 'password123' if storedHash is bcrypt
    // Or if storedHash is literal 'password123' (which we populated in init.sql)

    if ($inputPassword === $storedHash)
        return true;
    if (password_verify($inputPassword, $storedHash))
        return true;

    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Query Mock Users Table
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $loginSuccess = false;

    if ($user) {
        $storedHash = $user['password'];
        if (checkLegacyPassword($password, $storedHash)) {
            $loginSuccess = true;
        }
    }

    if ($loginSuccess) {
        // Set session
        $_SESSION['user_id'] = $user['empcode'];
        $_SESSION['full_name'] = $user['fullname'];
        $_SESSION['dept'] = $user['department'];
        $_SESSION['user_photo_id'] = $user['employee_id'];

        // Position/Role
        $_SESSION['position_title'] = $user['position_title'];
        $_SESSION['job_level'] = $user['job_level'];
        $_SESSION['role_profile'] = $user['role_profile'];

        // Approver Check (Postgres)
        $fullName = $_SESSION['full_name'];
        $stmtApp = $conn->prepare("SELECT department FROM Approvers WHERE approver_name_1 = :name1 OR approver_name_2 = :name2");
        $stmtApp->execute([':name1' => $fullName, ':name2' => $fullName]);
        $approverDepts = $stmtApp->fetchAll(PDO::FETCH_COLUMN);

        $_SESSION['is_approver'] = !empty($approverDepts);
        $_SESSION['approver_departments'] = $approverDepts;

        // Admin Check (Postgres)
        $biometricsId = $_SESSION['user_id'];
        $stmtAdm = $conn->prepare("SELECT role FROM ItemRequest_Admins WHERE biometrics_id = :id");
        $stmtAdm->execute([':id' => $biometricsId]); // Correctly mapped to biometrics_id in init.sql
        $adminRole = $stmtAdm->fetchColumn();

        $_SESSION['admin_role'] = $adminRole ?: null;

        header("Location: ../index.php");
        exit();
    } else {
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
}
?>