<?php
// actions/add_missing_items.php
require_once __DIR__ . '/../includes/db.php';

$itemsToAdd = [
    // Safety Shoes
    ['CM.CU.07.0014', 'Safety Shoes - 36', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0015', 'Safety Shoes - 37', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0016', 'Safety Shoes - 38', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0017', 'Safety Shoes - 39', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0018', 'Safety Shoes - 40', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0010', 'Safety Shoes - 41', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0011', 'Safety Shoes - 42', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0012', 'Safety Shoes - 43', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0019', 'Safety Shoes - 44', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0020', 'Safety Shoes - 45', 'Facilities Management -laundry', 'pair'],
    ['CM.CU.07.0022', 'Safety Shoes - 46', 'Facilities Management -laundry', 'pair'],
    // White Gloves (Cleaned up name)
    ['CM.PR.03.0075', 'Soft White Gloves with Rubber tip', 'Facilities Management -laundry', 'pc']
];

$category = 'uniform & PPEs';

try {
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM ItemMaster WHERE item_code = :code");
    $stmtInsert = $conn->prepare("
        INSERT INTO ItemMaster (item_code, item_name, category, sub_group, default_uom, price)
        VALUES (:code, :name, :cat, :sub, :uom, :price)
    ");

    $count = 0;
    foreach ($itemsToAdd as $item) {
        $code = $item[0];
        $name = $item[1];
        $sub = $item[2];
        $uom = $item[3];
        $price = 0.00; // Default price

        // Check if exists
        $stmtCheck->execute([':code' => $code]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo "Skipping $name ($code) - already exists.\n";
            continue;
        }

        // Insert
        $stmtInsert->execute([
            ':code' => $code,
            ':name' => $name,
            ':cat' => $category,
            ':sub' => $sub,
            ':uom' => $uom,
            ':price' => $price
        ]);
        echo "Inserted $name ($code).\n";
        $count++;
    }

    echo "Done. Inserted $count items.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>