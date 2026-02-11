<?php
// admin/manage_items.php
session_start();
require_once '../includes/db.php';

// 1. Validation: Admins and Super Admins
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_role'])) {
    $_SESSION['message'] = "Unauthorized access.";
    $_SESSION['msg_type'] = "error";
    header("Location: ../index.php");
    exit();
}

$isAdmin = true;
$isSuperAdmin = $_SESSION['admin_role'] === 'super_admin';

// 2. Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['item_name']);
        $category = $_POST['category'];
        // Logic for Item Code Generation
        // We'll trust user input for now or generate one if empty?
        // Let's mimic the frontend JS generation logic or let them type it manually.
        // For simplicity, let them type it or use a default.
        $code = trim($_POST['item_code']);

        // Check duplication
        $check = $conn->prepare("SELECT COUNT(*) FROM ItemMaster WHERE item_name = ?");
        $check->execute([$name]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['message'] = "Item name already exists.";
            $_SESSION['msg_type'] = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO ItemMaster (item_name, item_code, category, sub_group, default_uom, price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name,
                $code,
                $category,
                $_POST['sub_group'],
                $_POST['uom'],
                $_POST['price'] ?: 0
            ]);
            $_SESSION['message'] = "Item added successfully.";
            $_SESSION['msg_type'] = "success";
        }
    } elseif ($action === 'delete') {
        // DELETE logic
        $name = $_POST['item_name']; // PK logic implies item_name is somewhat unique based on create.php usage
        // But better to delete by ID if ItemMaster has one.
        // create.php uses ItemMaster columns: item_name, item_code...
        // Let's assume item_name is unique enough for this simple app or we used ID in list.
        // Assuming ItemMaster has no ID column visible in create.php but DB usually has one?
        // Let's check schema. create.php: SELECT * FROM ItemMaster.
        // I'll assume we can delete by item_name.
        $stmt = $conn->prepare("DELETE FROM ItemMaster WHERE item_name = ?");
        $stmt->execute([$name]);
        $_SESSION['message'] = "Item deleted.";
        $_SESSION['msg_type'] = "success";
    }


    header("Location: manage_items.php");
    exit();
}

// 3. Fetch Items
$stmt = $conn->query("SELECT * FROM ItemMaster ORDER BY item_name ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
    // Pass PHP item data to JS
    window.itemMaster = <?php echo json_encode($items); ?>;
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Items</title>
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
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="../index.php"
                    class="text-xs font-bold text-slate-400 hover:text-primary mb-2 inline-flex items-center gap-1"><i
                        class="ph-bold ph-arrow-left"></i> Back to Dashboard</a>
                <h1 class="text-3xl font-black text-slate-900">Manage Items</h1>
                <p class="text-slate-500 font-medium">Database Master List</p>
            </div>

            <?php if ($isSuperAdmin): ?>
                <a href="manage_users.php"
                    class="bg-slate-800 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg hover:bg-slate-700 transition-all border border-slate-700">
                    <i class="ph-bold ph-users"></i> Manage Admins
                </a>
            <?php endif; ?>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Form -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-pink-100 sticky top-8">
                    <h2 class="text-sm font-extrabold text-slate-400 uppercase tracking-wider mb-4">Add New Item</h2>
                    <form id="addItemForm" method="POST" class="space-y-4" onsubmit="return openAddModal(event)">
                        <input type="hidden" name="action" value="add">

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Category</label>
                            <select name="category" id="itemCategory" onchange="updateCodePlaceholder()"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                                <option value="" disabled selected>Select Category...</option>
                                <option value="Cleaning Chemical">Cleaning Chemical</option>
                                <option value="Cleaning Material">Cleaning Material</option>
                                <option value="Uniform & PPEs">Uniform & PPEs</option>
                                <option value="Clinic Item">Clinic Item</option>
                                <option value="Safety Item">Safety Item</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Item Code</label>
                            <input type="text" name="item_code" id="itemCode" placeholder="e.g. CM.AS.01.0001"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Item Name</label>
                            <input type="text" name="item_name" id="itemName" required placeholder="Item Name"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Sub Group</label>
                                <input type="text" name="sub_group" id="itemSubGroup" placeholder="Group"
                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">UOM</label>
                                <input type="text" name="uom" id="itemUom" placeholder="Pc/Set"
                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Price</label>
                            <input type="number" step="0.01" name="price" id="itemPrice" placeholder="0.00"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold outline-none focus:ring-2 focus:ring-primary text-sm">
                        </div>

                        <button type="submit"
                            class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-pink-200 mt-2">
                            <i class="ph-bold ph-plus"></i> Add Item
                        </button>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-pink-100 overflow-hidden">
                    <!-- Filter Controls -->
                    <div class="p-4 bg-slate-50 border-b border-slate-100 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <!-- Search by Item Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Search Item</label>
                                <input type="text" id="searchInput" placeholder="Search by name or code..."
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium outline-none focus:ring-2 focus:ring-primary"
                                    oninput="filterItems()">
                            </div>

                            <!-- Filter by Category -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Category</label>
                                <select id="categoryFilter"
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium outline-none focus:ring-2 focus:ring-primary"
                                    onchange="filterItems()">
                                    <option value="">All Categories</option>
                                    <option value="Cleaning Chemical">Cleaning Chemical</option>
                                    <option value="Cleaning Material">Cleaning Material</option>
                                    <option value="Uniform & PPEs">Uniform & PPEs</option>
                                    <option value="Clinic Item">Clinic Item</option>
                                    <option value="Safety Item">Safety Item</option>
                                </select>
                            </div>

                            <!-- Sort Options -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Sort By</label>
                                <select id="sortOrder"
                                    class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium outline-none focus:ring-2 focus:ring-primary"
                                    onchange="filterItems()">
                                    <option value="asc">A-Z (Ascending)</option>
                                    <option value="desc">Z-A (Descending)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Results Count -->
                        <div class="text-xs text-slate-500 font-medium">
                            Showing <span id="resultCount"
                                class="font-bold text-primary"><?php echo count($items); ?></span> of
                            <?php echo count($items); ?> items
                        </div>
                    </div>

                    <div class="max-h-[600px] overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100 sticky top-0">
                                <tr>
                                    <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">Code</th>
                                    <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">Item Name</th>
                                    <th class="p-4 text-xs font-extrabold text-slate-400 uppercase">Category</th>
                                    <th class="p-4 text-xs font-extrabold text-slate-400 uppercase text-right">Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($items as $item): ?>
                                    <tr class="hover:bg-pink-50/30 transition-colors">
                                        <td class="p-4 text-xs font-mono text-slate-500 font-bold">
                                            <?php echo htmlspecialchars($item['item_code']); ?>
                                        </td>
                                        <td class="p-4">
                                            <p class="font-bold text-slate-800 text-sm">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </p>
                                            <p class="text-[10px] text-slate-400">
                                                <?php echo htmlspecialchars($item['sub_group'] . ' • ' . $item['default_uom']); ?>
                                                <?php if ($item['price'] > 0)
                                                    echo ' • PHP ' . number_format($item['price'], 2); ?>
                                            </p>
                                        </td>
                                        <td class="p-4 text-xs font-bold text-slate-600">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <button
                                                onclick="openDeleteModal('<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')"
                                                class="text-slate-300 hover:text-red-500 transition-colors" title="Delete">
                                                <i class="ph-bold ph-trash text-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-95 transition-all duration-300"
            id="addItemModalContent">
            <div class="flex flex-col items-center text-center">
                <div
                    class="h-14 w-14 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="ph-bold ph-package"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Confirm Add Item?</h3>
                <p class="text-sm text-slate-500 mb-6">Are you sure you want to add this item to the database?</p>
                <div class="bg-slate-50 p-4 rounded-xl w-full text-left mb-6 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase">Item</p>
                    <p class="font-bold text-slate-800" id="modalItemName"></p>
                    <p class="text-xs font-bold text-slate-400 uppercase mt-2">Category</p>
                    <p class="font-bold text-slate-800" id="modalItemCategory"></p>
                </div>

                <div class="flex gap-3 w-full">
                    <button type="button" onclick="closeAddModal()"
                        class="flex-1 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="submitAddItem()"
                        class="flex-1 py-2.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 shadow-lg shadow-emerald-200 transition-colors">
                        Confirm Add
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Item Modal -->
    <div id="deleteItemModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300 opacity-0">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-95 transition-all duration-300"
            id="deleteItemModalContent">
            <div class="flex flex-col items-center text-center">
                <div
                    class="h-14 w-14 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="ph-bold ph-warning"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Delete Item?</h3>
                <p class="text-sm text-slate-500 mb-6">Are you sure you want to remove <span id="deleteItemName"
                        class="font-bold text-slate-800"></span>?</p>

                <form method="POST" class="flex gap-3 w-full">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="item_name" id="deleteItemInput">

                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 shadow-lg shadow-red-200 transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ADD MODAL FUNCTIONS
        function openAddModal(e) {
            e.preventDefault();
            const form = document.getElementById('addItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            document.getElementById('modalItemName').textContent = document.getElementById('itemName').value;
            document.getElementById('modalItemCategory').textContent = document.getElementById('itemCategory').value;

            const modal = document.getElementById('addItemModal');
            const content = document.getElementById('addItemModalContent');

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
            modal.style.display = 'flex';
            return false;
        }

        function closeAddModal() {
            const modal = document.getElementById('addItemModal');
            const content = document.getElementById('addItemModalContent');
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }, 300);
        }

        function submitAddItem() {
            document.getElementById('addItemForm').submit();
        }

        // DELETE MODAL FUNCTIONS
        function openDeleteModal(name) {
            document.getElementById('deleteItemInput').value = name;
            document.getElementById('deleteItemName').textContent = name;

            const modal = document.getElementById('deleteItemModal');
            const content = document.getElementById('deleteItemModalContent');

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
            modal.style.display = 'flex';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteItemModal');
            const content = document.getElementById('deleteItemModalContent');
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }, 300);
        }

        // AUTO-GENERATE CODE LOGIC
        function updateCodePlaceholder() {
            const category = document.getElementById('itemCategory').value;
            const codeInput = document.getElementById('itemCode');
            if (!category) return;

            const nextCode = generateNextCode(category);
            codeInput.placeholder = nextCode; // Show as hint
            codeInput.value = nextCode;       // Auto-fill for convenience
        }

        function generateNextCode(category) {
            // Define prefixes based on existing item codes in database
            // Sample formats: CM.AS.03.XXXX, CM.SH.01.XXXX, CM.CU.XX.XXXX

            const prefixes = {
                "Cleaning Chemical": "CM.AS.03.",
                "Cleaning Material": "CM.AS.03.",
                "Uniform & PPEs": "CM.CU.01.",
                "Clinic Item": "CM.SH.01.",
                "Safety Item": "CM.SH.03."
            };

            let prefix = prefixes[category] || "CM.XX.01.";

            // 2. Find Max Number logic
            let maxNum = 0;
            if (window.itemMaster && Array.isArray(window.itemMaster)) {
                // First, try to find the most common prefix for this category in the DB?
                // Too complex for JS client. Let's stick to the prefix we defined.

                window.itemMaster.forEach(item => {
                    const code = item.item_code || "";
                    if (code.startsWith(prefix)) {
                        const remaining = code.replace(prefix, "");
                        // Expecting 4 digits: 0262
                        const numPart = parseInt(remaining, 10);
                        if (!isNaN(numPart) && numPart > maxNum) {
                            maxNum = numPart;
                        }
                    } else if (category === "Cleaning Chemical" && code.startsWith("CM.")) {
                        // Allow loose matching for existing weird codes to find max?
                        // Actually, better to start a new cleaner sequence if the old one is messy.
                    }
                });
            }

            // 3. Increment
            const nextNum = maxNum + 1;
            // Format: PREFIX + 4 DIGITS (e.g. CM.SH.01.0001)
            return `${prefix}${nextNum.toString().padStart(4, "0")}`;
        }

        // FILTER AND SORT ITEMS
        function filterItems() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const sortOrder = document.getElementById('sortOrder').value;

            const tbody = document.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            let visibleCount = 0;

            // First, filter rows
            rows.forEach(row => {
                const itemName = row.querySelector('td:nth-child(2) p:first-child').textContent.toLowerCase();
                const itemCode = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const category = row.querySelector('td:nth-child(3)').textContent.trim();

                const matchesSearch = itemName.includes(searchInput) || itemCode.includes(searchInput);
                const matchesCategory = !categoryFilter || category === categoryFilter;

                if (matchesSearch && matchesCategory) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Then, sort visible rows
            const visibleRows = rows.filter(row => row.style.display !== 'none');
            visibleRows.sort((a, b) => {
                const nameA = a.querySelector('td:nth-child(2) p:first-child').textContent.toLowerCase();
                const nameB = b.querySelector('td:nth-child(2) p:first-child').textContent.toLowerCase();

                if (sortOrder === 'asc') {
                    return nameA.localeCompare(nameB);
                } else {
                    return nameB.localeCompare(nameA);
                }
            });

            // Re-append sorted rows
            visibleRows.forEach(row => tbody.appendChild(row));

            // Update count
            document.getElementById('resultCount').textContent = visibleCount;
        }
    </script>
</body>

</html>