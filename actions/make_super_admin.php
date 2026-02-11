<?php
require_once __DIR__ . '/../includes/db.php';

$userId = 4;
$role = 'super_admin';

// Check if exists
$stmt = $conn->prepare("SELECT COUNT(*) FROM ItemRequest_Admins WHERE biometrics_id = :id");
$stmt->execute([':id' => $userId]);

if ($stmt->fetchColumn() > 0) {
    // Update
    $update = $conn->prepare("UPDATE ItemRequest_Admins SET role = :role WHERE biometrics_id = :id");
    $update->execute([':role' => $role, ':id' => $userId]);
    echo "Updated User $userId to $role.\n";
} else {
    // Insert
    $insert = $conn->prepare("INSERT INTO ItemRequest_Admins (biometrics_id, role) VALUES (:id, :role)");
    $insert->execute([':id' => $userId, ':role' => $role]);
    echo "Inserted User $userId as $role.\n";
}
?>