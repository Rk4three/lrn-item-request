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
    $data = parseUniformCSV();

    if (isset($data['error'])) {
        return [];
    }

    $allowances = $data['allowances'];

    // Determine user level from position
    $positionLower = strtolower($userPosition);
    $userLevel = 'rank and file'; // Default

    if (strpos($positionLower, 'manager') !== false || strpos($positionLower, 'head') !== false || strpos($positionLower, 'director') !== false) {
        $userLevel = 'manager';
    } elseif (strpos($positionLower, 'supervisor') !== false || strpos($positionLower, 'lead') !== false || strpos($positionLower, 'sr.') !== false || strpos($positionLower, 'senior') !== false) {
        $userLevel = 'supervisor';
    }

    // Try to find matching department with multiple strategies
    $matchedDept = null;
    $userDeptLower = strtolower(trim($userDepartment));

    // Manual Override: Map "Production Department - LRN" to "Prodn -Office Based"
    // This allows them to see Black Pants which are available for Prodn -Office Based
    if (strpos($userDeptLower, 'production department - lrn') !== false) {
        $matchedDept = 'Prodn -Office Based';
    } else {
        // Normalize user department - remove common words
        $userDeptNormalized = preg_replace('/\s*(department|dept|division|team|group|unit)\s*/i', ' ', $userDeptLower);
        $userDeptNormalized = preg_replace('/\s+/', ' ', trim($userDeptNormalized));


        // Common abbreviation mappings
        $abbreviations = [
            'it' => ['information technology', 'info tech', 'i.t.', 'i.t'],
            'hr' => ['human resources', 'human resource', 'h.r.', 'h.r'],
            'r&i' => ['research and innovation', 'research & innovation', 'r and i', 'rni'],
            'r&d' => ['research and development', 'research & development', 'r and d', 'rnd'],
            'qa' => ['quality assurance', 'q.a.', 'q.a'],
            'qc' => ['quality control', 'q.c.', 'q.c'],
            'f&a' => ['finance and accounting', 'finance & accounting', 'finance', 'accounting'],
            'production' => ['prod', 'prodn', 'manufacturing'],
            'warehouse' => ['wh', 'warehousing'],
            'logistics' => ['log', 'delivery', 'transport'],
            'engineering' => ['eng', 'engg', 'maintenance'],
            'facilities' => ['facility', 'fac', 'janitorial', 'maintenance'],
            'creatives' => ['creative', 'marketing', 'design'],
            'sales' => ['sales and crm', 'sales & crm', 'crm', 'customer'],
            'supply chain' => ['pmc', 'pur', 'purchasing', 'procurement'],
        ];

        foreach (array_keys($allowances) as $csvDept) {
            $csvDeptLower = strtolower(trim($csvDept));

            // Normalize CSV department
            $csvDeptNormalized = preg_replace('/\s*(department|dept|division|team|group|unit)\s*/i', ' ', $csvDeptLower);
            $csvDeptNormalized = preg_replace('/\s+/', ' ', trim($csvDeptNormalized));

            // Strategy 1: Exact match
            if ($csvDeptLower === $userDeptLower || $csvDeptNormalized === $userDeptNormalized) {
                $matchedDept = $csvDept;
                break;
            }

            // Strategy 2: Partial match (contains)
            if (strpos($userDeptNormalized, $csvDeptNormalized) !== false || strpos($csvDeptNormalized, $userDeptNormalized) !== false) {
                $matchedDept = $csvDept;
                break;
            }

            // Strategy 3: Abbreviation matching
            foreach ($abbreviations as $abbrev => $variants) {
                // Check if CSV dept matches the abbreviation or its variants
                $csvMatches = ($csvDeptNormalized === $abbrev) ||
                    (strpos($csvDeptNormalized, $abbrev) !== false);

                foreach ($variants as $variant) {
                    if (strpos($csvDeptNormalized, $variant) !== false) {
                        $csvMatches = true;
                        break;
                    }
                }

                if ($csvMatches) {
                    // Check if user dept matches the same abbreviation or variants
                    $userMatches = ($userDeptNormalized === $abbrev) ||
                        (strpos($userDeptNormalized, $abbrev) !== false);

                    foreach ($variants as $variant) {
                        if (strpos($userDeptNormalized, $variant) !== false) {
                            $userMatches = true;
                            break;
                        }
                    }

                    if ($userMatches) {
                        $matchedDept = $csvDept;
                        break 2;
                    }
                }
            }

            // Strategy 4: Key word matching - extract significant words and see if they match
            $csvWords = array_filter(preg_split('/[\s\-\_\(\)\,]+/', $csvDeptNormalized), fn($w) => strlen($w) >= 2);
            $userWords = array_filter(preg_split('/[\s\-\_\(\)\,]+/', $userDeptNormalized), fn($w) => strlen($w) >= 2);

            if (!empty($csvWords) && !empty($userWords)) {
                $commonWords = array_intersect($csvWords, $userWords);
                // If at least half the CSV words match, consider it a match
                if (count($commonWords) >= ceil(count($csvWords) / 2)) {
                    $matchedDept = $csvDept;
                    break;
                }
            }
        }
    } // End of manual override else block


    if ($matchedDept === null) {
        // error_log("Uniform allowance: No department match found for '{$userDepartment}'");
        return [];
    }

    // Get allowances for matched department and level
    $deptAllowances = $allowances[$matchedDept] ?? [];
    $userAllowances = $deptAllowances[$userLevel] ?? [];

    // If no allowances for the specific level, try rank and file as fallback
    if (empty($userAllowances) && $userLevel !== 'rank and file') {
        $userAllowances = $deptAllowances['rank and file'] ?? [];
    }

    return $userAllowances;
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