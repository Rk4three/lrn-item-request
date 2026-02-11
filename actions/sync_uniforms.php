<?php
/**
 * Sync Uniforms from actual unifs.csv to ItemMaster table
 * This script:
 * 1. Deletes all existing "uniform & PPEs" items from ItemMaster
 * 2. Imports all items from actual unifs.csv
 */

session_start();
require_once '../includes/db.php';

// Only allow admin access
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$csvPath = dirname(__DIR__) . '/actual unifs.csv';

if (!file_exists($csvPath)) {
    die("CSV file not found: $csvPath");
}

try {
    $conn->beginTransaction();

    // Step 1: Delete existing uniform items
    $deleteStmt = $conn->prepare("DELETE FROM ItemMaster WHERE category = 'uniform & PPEs'");
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();

    // Step 2: Read and parse CSV
    $rows = [];
    if (($handle = fopen($csvPath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Skip header row
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (!empty($data[0])) { // Skip empty rows
                $rows[] = $data;
            }
        }
        fclose($handle);
    }

    // Step 3: Insert new items
    $insertStmt = $conn->prepare("
        INSERT INTO ItemMaster (category, item_code, item_name, sub_group, default_uom) 
        VALUES (:category, :item_code, :item_name, :sub_group, :uom)
    ");

    $insertedCount = 0;
    foreach ($rows as $row) {
        $category = trim($row[0] ?? '');
        $itemCode = trim($row[1] ?? '');
        $itemName = trim($row[2] ?? '');
        $subGroup = trim($row[3] ?? '');
        $uom = trim($row[4] ?? 'pc');

        if (empty($itemCode) || empty($itemName))
            continue;

        $insertStmt->execute([
            ':category' => $category,
            ':item_code' => $itemCode,
            ':item_name' => $itemName,
            ':sub_group' => $subGroup,
            ':uom' => $uom
        ]);
        $insertedCount++;
    }

    $conn->commit();

    echo "<h2>Uniform Sync Complete!</h2>";
    echo "<p>Deleted: <b>$deletedCount</b> old uniform items</p>";
    echo "<p>Inserted: <b>$insertedCount</b> new uniform items from CSV</p>";
    echo "<p><a href='../create.php'>Go to Create Request</a></p>";

} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?>