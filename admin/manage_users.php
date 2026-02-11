<?php
// admin/manage_users.php
session_start();
require_once '../includes/db.php';

// 1. Validation: Only Super Admins can access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['message'] = "Unauthorized access.";
    $_SESSION['msg_type'] = "error";
    header("Location: ../index.php");
    exit();
}

// 2. Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $biometrics = trim($_POST['biometrics_id'] ?? '');
        $role = $_POST['role'] ?? 'admin';

        if ($biometrics) {
            try {
                // Check if already exists
                $check = $conn->prepare("SELECT COUNT(*) FROM ItemRequest_Admins WHERE biometrics_id = ?");
                $check->execute([$biometrics]);
                if ($check->fetchColumn() > 0) {
                    $_SESSION['message'] = "User already exists as admin.";
                    $_SESSION['msg_type'] = "warning";
                } else {
                    $stmt = $conn->prepare("INSERT INTO ItemRequest_Admins (biometrics_id, role, added_by) VALUES (?, ?, ?)");
                    $stmt->execute([$biometrics, $role, $_SESSION['full_name']]);
                    $_SESSION['message'] = "Admin added successfully.";
                    $_SESSION['msg_type'] = "success";
                }
            } catch (PDOException $e) {
                $_SESSION['message'] = "Error: " . $e->getMessage();
                $_SESSION['msg_type'] = "error";
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            $stmt = $conn->prepare("DELETE FROM ItemRequest_Admins WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Admin removed.";
            $_SESSION['msg_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['msg_type'] = "error";
        }
    }

    header("Location: manage_users.php");
    exit();
}

// 3. Fetch Data
try {
    // Join with Master List to get names (Using users table now)
    $stmt = $conn->query("
        SELECT a.*, 
        COALESCE(ml.fullname, 'Unknown') as full_name,
        ml.department
        FROM ItemRequest_Admins a
        LEFT JOIN users ml 
        ON a.biometrics_id = ml.empcode
        ORDER BY a.created_at DESC
    ");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Admins</title>
    <link rel="icon" href="../assets/img/La-Rose-Official-Logo-Revised.jpg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#f472b6', 'primary-dark': '#ec4899' },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="bg-pink-50 min-h-screen p-8 text-slate-800 font-sans">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="../index.php"
                    class="text-xs font-bold text-slate-400 hover:text-primary mb-2 inline-flex items-center gap-1"><i
                        class="ph-bold ph-arrow-left"></i> Back to Dashboard</a>
                <h1 class="text-3xl font-black text-slate-900">Manage Admins</h1>
                <p class="text-slate-500 font-medium">Super Admin Control Panel</p>
            </div>

            <a href="manage_items.php"
                class="bg-white text-slate-600 px-4 py-2 rounded-xl font-bold text-sm shadow-sm hover:bg-slate-50 transition-all border border-slate-200">
                <i class="ph-bold ph-package"></i> Manage Items
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div id="alert-message"
                class="p-4 rounded-xl mb-6 font-bold text-sm <?php echo $_SESSION['msg_type'] == 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?> transition-opacity duration-500">
                <?php echo $_SESSION['message'];
                unset($_SESSION['message']); ?>
            </div>
            <script>
                setTimeout(() => {
                    const alert = document.getElementById('alert-message');
                    if (alert) {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-pink-100 mb-8">
            <h2 class="text-sm font-extrabold text-slate-400 uppercase tracking-wider mb-4">Add New Admin</h2>
            <form method="POST" class="flex gap-4 items-end">
                <input type="hidden" name="action" value="add">
                <div class="flex-grow">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Biometrics ID</label>
                    <input type="text" name="biometrics_id" required placeholder="e.g. 1320"
                        class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Role</label>
                    <input type="text" value="Admin" readonly
                        class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-lg font-bold text-slate-500 outline-none cursor-not-allowed">
                    <input type="hidden" name="role" value="admin">
                </div>
                <button type="submit"
                    class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-bold transition-all shadow-lg shadow-pink-200">
                    <i class="ph-bold ph-plus"></i> Add
                </button>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white rounded-2xl shadow-sm border border-pink-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">User</th>
                        <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">Role</th>
                        <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">Added By</th>
                        <th class="p-4 text-xs font-extrabold text-slate-400 uppercase text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($admins as $admin): ?>
                        <tr class="hover:bg-pink-50/30 transition-colors">
                            <td class="p-4">
                                <p class="font-bold text-slate-800">
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                </p>
                                <p class="text-xs font-mono text-slate-400">
                                    <?php echo htmlspecialchars($admin['biometrics_id']); ?>
                                </p>
                            </td>
                            <td class="p-4">
                                <?php if ($admin['role'] === 'super_admin'): ?>
                                    <span
                                        class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">Super
                                        Admin</span>
                                <?php else: ?>
                                    <span
                                        class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-sm font-medium text-slate-500">
                                <?php echo htmlspecialchars($admin['added_by']); ?>
                                <span class="block text-[10px] text-slate-400">
                                    <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <?php if ($admin['role'] !== 'super_admin'): ?>
                                    <button
                                        onclick="openDeleteModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['full_name']); ?>')"
                                        class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors"
                                        title="Remove">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-95 transition-all duration-300"
            id="deleteModalContent">
            <div class="flex flex-col items-center text-center">
                <div
                    class="h-14 w-14 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="ph-bold ph-warning"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Remove Admin?</h3>
                <p class="text-sm text-slate-500 mb-6">Are you sure you want to remove <span id="deleteUserName"
                        class="font-bold text-slate-800"></span> from the admin list?</p>

                <form method="POST" class="flex gap-3 w-full">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteUserId">

                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 shadow-lg shadow-red-200 transition-colors">
                        Yes, Remove
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;

            const modal = document.getElementById('deleteModal');
            const content = document.getElementById('deleteModalContent');

            modal.classList.remove('hidden');
            // Small delay to allow display:block to apply before opacity transition
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);

            modal.style.display = 'flex';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const content = document.getElementById('deleteModalContent');

            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }, 300);
        }
    </script>
</body>

</html>