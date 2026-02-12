<?php
// includes/uniform_helper.php
// Helper functions for uniform allowance management

/**
 * Parse the uniform.csv file and return structured allowance data
 * @return array Structured uniform allowances data
 */
function parseUniformCSV($csvPath = null)
{
    if ($csvPath === null) {
        $csvPath = dirname(__DIR__) . '/uniform.csv';
    }

    if (!file_exists($csvPath)) {
        return ['error' => 'Uniform CSV file not found'];
    }

    $rows = [];
    if (($handle = fopen($csvPath, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    }

    if (count($rows) < 4) {
        return ['error' => 'Invalid CSV format'];
    }

    // Row index 1 (0-based) = Header row with uniform names
    // Row index 2 = Issuance types (daily/deployment)
    // Row index 3+ = Department/Level data

    $headerRow = $rows[1];
    $issuanceRow = $rows[2];

    // Build uniform types array (columns 2 onwards)
    $uniformTypes = [];
    for ($i = 2; $i < count($headerRow); $i++) {
        $uniformName = trim($headerRow[$i]);
        if (!empty($uniformName)) {
            $uniformTypes[$i] = [
                'name' => $uniformName,
                'issuance' => strtolower(trim($issuanceRow[$i] ?? 'deployment'))
            ];
        }
    }

    // Build allowances by department and level
    $allowances = [];
    $currentDepartment = '';

    for ($r = 3; $r < count($rows); $r++) {
        $row = $rows[$r];

        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Get department (column 0) - if empty, use previous department
        $dept = trim($row[0] ?? '');
        if (!empty($dept)) {
            $currentDepartment = $dept;
        }

        // Get level (column 1)
        $level = strtolower(trim($row[1] ?? ''));
        if (empty($level) || empty($currentDepartment)) {
            continue;
        }

        // Initialize department if not exists
        if (!isset($allowances[$currentDepartment])) {
            $allowances[$currentDepartment] = [];
        }

        // Initialize level if not exists
        if (!isset($allowances[$currentDepartment][$level])) {
            $allowances[$currentDepartment][$level] = [];
        }

        // Parse each uniform column
        foreach ($uniformTypes as $colIndex => $uniformInfo) {
            $qty = trim($row[$colIndex] ?? '');
            if (!empty($qty) && is_numeric($qty)) {
                $allowances[$currentDepartment][$level][$uniformInfo['name']] = [
                    'quantity' => (int) $qty,
                    'issuance' => $uniformInfo['issuance']
                ];
            }
        }
    }

    return [
        'uniformTypes' => $uniformTypes,
        'allowances' => $allowances
    ];
}

/**
 * Get uniform allowances for a specific user based on their department and position
 * @param string $userDepartment User's department
 * @param string $userPosition User's position/title
 * @return array Allowed uniforms with quantities and issuance types
 */
function getUserUniformAllowances($userDepartment, $userPosition)
{
    // Return empty array to disable restrictions and allow all uniforms
    return [];
}

/**
 * Check if a user can request a specific uniform item
 * @param PDO $conn Database connection
 * @param int $employeeId Employee's biometrics ID
 * @param string $uniformName Name of the uniform item
 * @param string $userDepartment User's department
 * @param string $userPosition User's position
 * @param string $size Size of the uniform (optional)
 * @return array ['allowed' => bool, 'reason' => string, 'max_qty' => int, 'remaining' => int]
 */
function checkUniformRequestEligibility($conn, $employeeId, $uniformName, $userDepartment, $userPosition, $size = null)
{
    $allowances = getUserUniformAllowances($userDepartment, $userPosition);

    // If no allowances defined for this user, allow all (no restrictions)
    if (empty($allowances)) {
        return [
            'allowed' => true,
            'reason' => '',
            'max_qty' => 999,
            'remaining' => 999,
            'issuance' => 'none'
        ];
    }

    // Normalize item name - keep only letters, numbers, spaces
    $normalizedItemName = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $uniformName));
    $normalizedItemName = preg_replace('/\s+/', ' ', trim($normalizedItemName));

    // Find matching allowance
    $matchedAllowance = null;
    $matchedUniformKey = null;

    foreach ($allowances as $allowedUniform => $details) {
        // Normalize the CSV allowance name the same way
        $normalizedAllowance = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $allowedUniform));
        $normalizedAllowance = preg_replace('/\s+/', ' ', trim($normalizedAllowance));

        // Split into keywords (words with 2+ chars)
        $keywords = array_filter(explode(' ', $normalizedAllowance), function ($k) {
            return strlen($k) >= 2;
        });

        if (empty($keywords))
            continue;

        // Check if ALL keywords from CSV are found in the item name
        $allMatch = true;
        foreach ($keywords as $keyword) {
            if (strpos($normalizedItemName, $keyword) === false) {
                $allMatch = false;
                break;
            }
        }

        if ($allMatch) {
            $matchedAllowance = $details;
            $matchedUniformKey = $allowedUniform;
            break;
        }
    }

    if ($matchedAllowance === null) {
        // Fallback for Safety Shoes and White Gloves (requested to be always visible)
        $lowerName = strtolower($uniformName);
        if (strpos($lowerName, 'safety shoes') !== false) {
            return [
                'allowed' => true,
                'reason' => 'Standard PPE',
                'max_qty' => 1,
                'remaining' => 1,
                'issuance' => 'deployment'
            ];
        }
        if (strpos($lowerName, 'white gloves') !== false) {
            return [
                'allowed' => true,
                'reason' => 'Standard PPE',
                'max_qty' => 5, // Assuming consumable
                'remaining' => 5,
                'issuance' => 'daily'
            ];
        }
        if (strpos($lowerName, 'white lab gown') !== false) {
            return [
                'allowed' => true,
                'reason' => 'Standard Uniform',
                'max_qty' => 3,
                'remaining' => 3,
                'issuance' => 'daily'
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'This uniform is not allowed for your department/position',
            'max_qty' => 0,
            'remaining' => 0
        ];
    }

    $maxQty = $matchedAllowance['quantity'];
    $issuance = $matchedAllowance['issuance'];

    // Calculate date range based on issuance type
    if ($issuance === 'daily') {
        // Check requests made today
        $dateCondition = "CAST(r.created_at AS DATE) = CURRENT_DATE";
        $periodDescription = "today";
    } else {
        // Deployment: Check requests in the last 6 months
        $dateCondition = "r.created_at >= CURRENT_DATE - INTERVAL '6 months'";
        $periodDescription = "the last 6 months";
    }

    // Query to get total quantity requested for this uniform type
    // We need to match the uniform name pattern in the item_name
    $sql = "SELECT COALESCE(SUM(ri.quantity), 0) as total_requested
            FROM RequestItems ri
            INNER JOIN Requests r ON ri.request_id = r.request_id
            WHERE r.employee_id = :emp_id
            AND r.status != 'Rejected'
            AND $dateCondition
            AND (
                LOWER(ri.item_name) LIKE :pattern1
                OR LOWER(ri.item_name) LIKE :pattern2
            )";

    $pattern = '%' . strtolower($matchedUniformKey) . '%';

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':emp_id' => $employeeId,
            ':pattern1' => $pattern,
            ':pattern2' => $pattern
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalRequested = (int) ($result['total_requested'] ?? 0);
    } catch (PDOException $e) {
        // If query fails, allow the request but log the error
        // error_log("Uniform eligibility check failed: " . $e->getMessage());
        $totalRequested = 0;
    }

    $remaining = max(0, $maxQty - $totalRequested);

    if ($remaining <= 0) {
        return [
            'allowed' => false,
            'reason' => "You have already requested the maximum quantity ($maxQty) of this uniform in $periodDescription",
            'max_qty' => $maxQty,
            'remaining' => 0,
            'issuance' => $issuance
        ];
    }

    return [
        'allowed' => true,
        'reason' => '',
        'max_qty' => $maxQty,
        'remaining' => $remaining,
        'issuance' => $issuance
    ];
}

/**
 * Get all uniform allowances formatted for JavaScript
 * @param string $userDepartment User's department
 * @param string $userPosition User's position
 * @return array Formatted for JS consumption
 */
function getUniformAllowancesForJS($userDepartment, $userPosition)
{
    $allowances = getUserUniformAllowances($userDepartment, $userPosition);

    $result = [];
    foreach ($allowances as $uniformName => $details) {
        $result[] = [
            'name' => $uniformName,
            'maxQuantity' => $details['quantity'],
            'issuance' => $details['issuance']
        ];
    }

    return $result;
}

/**
 * Store/update uniform allowances in database from CSV
 * This can be called to sync CSV data to database for faster access
 * @param PDO $conn Database connection
 * @return bool Success status
 */
function syncUniformAllowancesToDB($conn)
{
    $data = parseUniformCSV();

    if (isset($data['error'])) {
        return false;
    }

    try {
        // Create table if not exists
        $conn->exec("
            CREATE TABLE IF NOT EXISTS UniformAllowances (
                id SERIAL PRIMARY KEY,
                department VARCHAR(100) NOT NULL,
                level VARCHAR(50) NOT NULL,
                uniform_name VARCHAR(200) NOT NULL,
                max_quantity INT NOT NULL,
                issuance_type VARCHAR(20) NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(department, level, uniform_name)
            )
        ");

        // Clear existing data
        $conn->exec("DELETE FROM UniformAllowances");

        // Insert new data
        $stmt = $conn->prepare("
            INSERT INTO UniformAllowances (department, level, uniform_name, max_quantity, issuance_type)
            VALUES (:dept, :level, :uniform, :qty, :issuance)
        ");

        foreach ($data['allowances'] as $dept => $levels) {
            foreach ($levels as $level => $uniforms) {
                foreach ($uniforms as $uniformName => $details) {
                    $stmt->execute([
                        ':dept' => $dept,
                        ':level' => $level,
                        ':uniform' => $uniformName,
                        ':qty' => $details['quantity'],
                        ':issuance' => $details['issuance']
                    ]);
                }
            }
        }

        return true;
    } catch (PDOException $e) {
        // error_log("Failed to sync uniform allowances: " . $e->getMessage());
        return false;
    }
}
?>