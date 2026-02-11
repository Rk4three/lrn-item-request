<?php
// change_password_tool.php
require_once 'includes/db.php';

$message = '';
$status = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $biometricsId = $_POST['biometrics_id'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (!empty($biometricsId) && !empty($newPassword)) {
        try {
            // Check if user exists first (using username column which holds Biometrics ID)
            $checkSql = "SELECT COUNT(*) FROM users WHERE username = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$biometricsId]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                // Hash the new password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password
                $updateSql = "UPDATE users SET password = ? WHERE username = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$newHash, $biometricsId]);

                $message = "Password successfully updated for Biometrics ID: " . htmlspecialchars($biometricsId);
                $status = "success";
            } else {
                $message = "User with Biometrics ID not found: " . htmlspecialchars($biometricsId);
                $status = "error";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $status = "error";
        }
    } else {
        $message = "Please fill in all fields.";
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Update Password</h1>
        <p class="text-slate-500 text-sm mb-6">Enter the Biometrics ID and the new password to update the database
            encryption.</p>

        <?php if (!empty($message)): ?>
            <div
                class="mb-6 p-4 rounded-xl <?php echo $status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                <p class="font-bold text-sm"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Biometrics
                    ID</label>
                <input type="text" name="biometrics_id" required
                    class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-400 outline-none transition-all"
                    placeholder="e.g. 2023-1234">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">New Password</label>
                <input type="password" name="new_password" required
                    class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-400 outline-none transition-all"
                    placeholder="Enter new password">
            </div>

            <button type="submit"
                class="w-full bg-pink-500 hover:bg-pink-600 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-pink-200 mt-2">
                Update Password
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-xs font-bold text-slate-400 hover:text-slate-600">Back to Home</a>
        </div>
    </div>

</body>

</html>