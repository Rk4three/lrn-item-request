<?php
// index.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_name = $_SESSION['full_name'] ?? 'User';
$user_position = $_SESSION['position_title'] ?? 'Employee';
$photo_id = $_SESSION['user_photo_id'] ?? $_SESSION['user_id'];

$is_management = (stripos($user_position, 'Supervisor') !== false) || (stripos($user_position, 'Manager') !== false);
$is_approver_in_db = $_SESSION['is_approver'] ?? false;
$approver_departments = $_SESSION['approver_departments'] ?? [];

// Fetch all departments from Approvers table for filter dropdown
// Approver Dept List not needed for filter anymore
//$approverDeptList = [];
//try {
//    $deptStmt = $conn->query("SELECT DISTINCT department FROM Approvers ORDER BY department");
//    $approverDeptList = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
//} catch (PDOException $e) {
//    $approverDeptList = [];
//}
$approverDeptList = [];

// --- PAGINATION ---
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// --- FILTER LOGIC ---
$whereClauses = [];
$params = [];

if (!empty($_GET['status_filter'])) {
    $whereClauses[] = "r.status = :status";
    $params[':status'] = $_GET['status_filter'];
}
if (!empty($_GET['search_id'])) {
    $searchRaw = $_GET['search_id'];
    $rawId = (strlen($searchRaw) > 4) ? intval(substr($searchRaw, 2)) : intval($searchRaw);
    $whereClauses[] = "r.request_id = :req_id";
    $params[':req_id'] = $rawId;
}
// Requestor Name Filter
if (!empty($_GET['search_requestor'])) {
    $whereClauses[] = "r.requestor_name LIKE :req_name";
    $params[':req_name'] = "%" . $_GET['search_requestor'] . "%";
}
if (!empty($_GET['date_filed'])) {
    $whereClauses[] = "CAST(r.created_at AS DATE) = :date_filed";
    $params[':date_filed'] = $_GET['date_filed'];
}
if (!empty($_GET['assigned_area']) && $_GET['assigned_area'] !== 'All') {
    $whereClauses[] = "r.assigned_area = :area";
    $params[':area'] = $_GET['assigned_area'];
}
if (!empty($_GET['date_needed'])) {
    $whereClauses[] = "CAST(r.date_needed AS DATE) = :date_needed";
    $params[':date_needed'] = $_GET['date_needed'];
}
// STRICT DEPARTMENT FILTER REMOVED
// Users can now see ALL requests.
// Approval logic remains in the loop below to ensure only correct people can approve.
$user_id = $_SESSION['user_id'] ?? 0;

// Check for Super Viewers (ID 4, 1320) - Logic kept for reference but no longer needed for filtering
$isSuperViewer = ($user_id == 4 || $user_id == 1320);

// Check for Laundry Managers - Logic kept for reference
$isLaundry = (stripos($user_position, 'Laundry') !== false) || (stripos($_SESSION['dept'] ?? '', 'Laundry') !== false);
// Enhanced Management Check
$userRoleProfile = $_SESSION['role_profile'] ?? '';
$userJobLevel = $_SESSION['job_level'] ?? '';
$userPosition = $_SESSION['position_title'] ?? '';

$isManagement = false;
$mgmtKeywords = ['Manager', 'Supervisor', 'Head'];

foreach ($mgmtKeywords as $keyword) {
    if (
        stripos($userRoleProfile, $keyword) !== false ||
        stripos($userJobLevel, $keyword) !== false ||
        stripos($userPosition, $keyword) !== false
    ) {
        $isManagement = true;
        break;
    }
}

$isLaundryManager = $isLaundry && $isManagement;

// No query filters added for department.
// $whereClauses[] = ... is removed.

// Remove old approver filter logic if it exists (it was lines 64-67)
// ... keeping other filters
$sqlWhere = "";
if (count($whereClauses) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $whereClauses);
}

// Stats
try {
    $statsSQL = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected FROM Requests";
    $statsStmt = $conn->prepare($statsSQL);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

// Pagination Count
try {
    $countSQL = "SELECT COUNT(*) as total FROM Requests r $sqlWhere";
    $countStmt = $conn->prepare($countSQL);
    $countStmt->execute($params);
    $total_records = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $items_per_page);
} catch (PDOException $e) {
    $total_records = 0;
    $total_pages = 1;
}

// Fetch Requests
try {
    $sql = "SELECT r.*, (SELECT approver FROM RequestItems ri WHERE ri.request_id = r.request_id LIMIT 1) as primary_approver FROM Requests r $sqlWhere ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Request System</title>
    <link rel="icon" href="assets/img/La-Rose-Official-Logo-Revised.jpg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#f472b6', 'primary-hover': '#ec4899', 'pastel-bg': '#fff1f2', 'pastel-card': '#ffe4e6', surface: '#ffffff', } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #fce7f3;
        }

        .stat-item {
            transition: background-color 0.2s ease;
        }

        .table-row {
            border-bottom: 1px solid #fff1f2;
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background-color: #fff1f2;
        }
    </style>
</head>

<body class="text-slate-800 flex flex-col min-h-screen bg-pastel-bg overflow-x-hidden">

    <nav class="glass-nav sticky top-0 z-50 px-6 py-4 shadow-sm">
        <div class="w-full max-w-[95%] mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="assets/img/La-Rose-Official-Logo-Revised.jpg" alt="Logo"
                    class="w-14 h-14 rounded-xl shadow-lg object-cover">
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900">La Rose Noire</h1>
                    <p class="text-xs font-bold text-primary uppercase tracking-widest">Item Request System</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden sm:flex items-center gap-3">
                    <div class="relative h-14 w-14 rounded-xl overflow-hidden shadow-sm border flex-shrink-0 bg-white">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-pink-400 to-purple-400 flex items-center justify-center text-white font-black text-lg">
                            <?php echo substr($user_name, 0, 1); ?>
                        </div>
                        <img id="userProfileImg" src="/assets/emp_photos/<?php echo $photo_id; ?>.jpg"
                            class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300"
                            onload="handleProfileImageLoad(this)"
                            onerror="handleImgError(this, '<?php echo $photo_id; ?>')" alt="User">
                        <script>
                                // Immediately check if image is cached and show it
                                (function () {
                                    var img = document.getElementById('userProfileImg');
                                    if (img.complete && img.naturalHeight > 0) {
                                        img.style.transition = 'none';
                                        img.classList.remove('opacity-0');
                                    }
                                })();
                        </script>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-sm text-slate-900 leading-tight">
                            <?php echo htmlspecialchars($user_name); ?>
                        </p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wide">
                            <?php echo htmlspecialchars($user_position); ?>
                        </p>
                    </div>
                </div>

                <div class="border-l border-pink-100 pl-6 flex items-center gap-2">
                    <?php if (isset($_SESSION['admin_role'])): ?>
                        <div class="flex gap-2 mr-2">
                            <a href="admin/manage_items.php"
                                class="h-10 px-4 rounded-xl bg-white border border-pink-200 text-pink-600 font-bold text-xs hover:bg-pink-50 transition-all flex items-center gap-2 shadow-sm"
                                title="Manage Items">
                                <i class="ph-bold ph-package text-lg"></i> <span class="hidden lg:inline">Items</span>
                            </a>
                            <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                <a href="admin/manage_users.php"
                                    class="h-10 px-4 rounded-xl bg-slate-800 text-white font-bold text-xs hover:bg-slate-700 transition-all flex items-center gap-2 shadow-lg"
                                    title="Manage Admins">
                                    <i class="ph-bold ph-users text-lg"></i> <span class="hidden lg:inline">Admins</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="h-12 w-12 bg-pink-100 text-primary rounded-full flex items-center justify-center font-bold hover:bg-pink-200 transition-all cursor-pointer"
                        onclick="window.location.href='auth/logout.php'" title="Logout">
                        <i class="ph-bold ph-sign-out text-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['message'])): ?>
        <div id="alert-message" class="w-full max-w-[95%] mx-auto mt-4 transition-opacity duration-1000">
            <div
                class="p-4 rounded-xl shadow-sm border-l-4 <?php echo $_SESSION['msg_type'] == 'success' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-red-50 border-red-500 text-red-700'; ?> flex items-center gap-3">
                <i
                    class="ph-fill <?php echo $_SESSION['msg_type'] == 'success' ? 'ph-check-circle' : 'ph-warning-circle'; ?> text-xl"></i>
                <span class="font-semibold text-sm"><?php echo $_SESSION['message']; ?></span>
            </div>
        </div>
        <script>
            setTimeout(function () {
                var alert = document.getElementById('alert-message');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(function () { alert.remove(); }, 1000);
                }
            }, 5000);
        </script>
        <?php unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    endif; ?>

    <div class="w-full max-w-[95%] mx-auto py-8 flex-grow">

        <div
            class="bg-white rounded-3xl shadow-xl shadow-pink-100/50 border border-slate-100 mb-10 overflow-hidden relative">
            <div class="grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">

                <?php $isActive = empty($_GET['status_filter']); ?>
                <a href="index.php"
                    class="group relative p-6 flex items-center justify-between transition-all duration-300 hover:bg-slate-50 <?php echo $isActive ? 'bg-slate-50/80' : ''; ?>">
                    <?php if ($isActive): ?>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-slate-800 rounded-t-full"></div><?php endif; ?>
                    <div>
                        <p
                            class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1 group-hover:text-slate-600 transition-colors">
                            Total Requests</p>
                        <h3
                            class="text-4xl font-black text-slate-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            <?php echo $stats['total']; ?>
                        </h3>
                    </div>
                    <div
                        class="h-14 w-14 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform shadow-inner">
                        <i class="ph-fill ph-stack"></i>
                    </div>
                </a>

                <?php $isActive = ($_GET['status_filter'] ?? '') === 'Pending'; ?>
                <a href="index.php?status_filter=Pending"
                    class="group relative p-6 flex items-center justify-between transition-all duration-300 hover:bg-amber-50/30 <?php echo $isActive ? 'bg-amber-50/60' : ''; ?>">
                    <?php if ($isActive): ?>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-amber-500 rounded-t-full"></div><?php endif; ?>
                    <div>
                        <p
                            class="text-xs font-bold text-amber-600/60 uppercase tracking-widest mb-1 group-hover:text-amber-600 transition-colors">
                            Pending</p>
                        <h3
                            class="text-4xl font-black text-amber-500 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            <?php echo $stats['pending']; ?>
                        </h3>
                    </div>
                    <div
                        class="h-14 w-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform shadow-inner">
                        <i class="ph-fill ph-clock-countdown"></i>
                    </div>
                </a>

                <?php $isActive = ($_GET['status_filter'] ?? '') === 'Approved'; ?>
                <a href="index.php?status_filter=Approved"
                    class="group relative p-6 flex items-center justify-between transition-all duration-300 hover:bg-emerald-50/30 <?php echo $isActive ? 'bg-emerald-50/60' : ''; ?>">
                    <?php if ($isActive): ?>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-emerald-500 rounded-t-full"></div><?php endif; ?>
                    <div>
                        <p
                            class="text-xs font-bold text-emerald-600/60 uppercase tracking-widest mb-1 group-hover:text-emerald-600 transition-colors">
                            Approved</p>
                        <h3
                            class="text-4xl font-black text-emerald-500 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            <?php echo $stats['approved']; ?>
                        </h3>
                    </div>
                    <div
                        class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform shadow-inner">
                        <i class="ph-fill ph-check-circle"></i>
                    </div>
                </a>

                <?php $isActive = ($_GET['status_filter'] ?? '') === 'Rejected'; ?>
                <a href="index.php?status_filter=Rejected"
                    class="group relative p-6 flex items-center justify-between transition-all duration-300 hover:bg-red-50/30 <?php echo $isActive ? 'bg-red-50/60' : ''; ?>">
                    <?php if ($isActive): ?>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-red-500 rounded-t-full"></div><?php endif; ?>
                    <div>
                        <p
                            class="text-xs font-bold text-red-600/60 uppercase tracking-widest mb-1 group-hover:text-red-600 transition-colors">
                            Rejected</p>
                        <h3
                            class="text-4xl font-black text-red-500 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            <?php echo $stats['rejected']; ?>
                        </h3>
                    </div>
                    <div
                        class="h-14 w-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl group-hover:rotate-12 transition-transform shadow-inner">
                        <i class="ph-fill ph-x-circle"></i>
                    </div>
                </a>

            </div>
        </div>

        <div class="flex flex-col items-center md:flex-row md:justify-between md:items-end mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-black text-slate-900">Requests</h2>
                <p class="text-slate-500 font-medium text-base">Manage and track item requests</p>
            </div>
            <a href="create.php"
                class="bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-pink-200 transition-all flex items-center gap-2 transform hover:-translate-y-0.5"><i
                    class="ph-bold ph-plus"></i> New Request</a>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-pink-100 mb-6">
            <form method="GET" action="index.php"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
                <input type="hidden" name="status_filter" value="<?php echo $_GET['status_filter'] ?? ''; ?>">

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Request ID</label>
                    <div class="relative"><i
                            class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i><input
                            type="text" name="search_id" value="<?php echo $_GET['search_id'] ?? ''; ?>"
                            placeholder="e.g. 260001"
                            class="w-full pl-8 pr-3 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-pink-200 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Requestor</label>
                    <div class="relative">
                        <i class="ph-bold ph-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search_requestor"
                            value="<?php echo $_GET['search_requestor'] ?? ''; ?>" placeholder="Name..."
                            class="w-full pl-8 pr-3 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-pink-200 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div class="space-y-2"><label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date
                        Filed</label><input type="date" name="date_filed"
                        value="<?php echo $_GET['date_filed'] ?? ''; ?>"
                        class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-pink-200 focus:border-primary outline-none transition-all">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Area</label>
                    <select name="assigned_area"
                        class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-pink-200 focus:border-primary outline-none transition-all">
                        <option value="All">Any Area</option>
                        <option value="Production 1" <?php echo ($_GET['assigned_area'] ?? '') == 'Production 1' ? 'selected' : ''; ?>>
                            Production 1
                        </option>
                        <option value="Production 2" <?php echo ($_GET['assigned_area'] ?? '') == 'Production 2' ? 'selected' : ''; ?>>
                            Production 2
                        </option>
                        <option value="Production 3" <?php echo ($_GET['assigned_area'] ?? '') == 'Production 3' ? 'selected' : ''; ?>>
                            Production 3
                        </option>
                        <option value="Production 4" <?php echo ($_GET['assigned_area'] ?? '') == 'Production 4' ? 'selected' : ''; ?>>
                            Production 4
                        </option>
                        <option value="Prodn - Office Based" <?php echo ($_GET['assigned_area'] ?? '') == 'Prodn - Office Based' ? 'selected' : ''; ?>>Prodn - Office Based
                        </option>
                        <option value="Admin- Office Based" <?php echo ($_GET['assigned_area'] ?? '') == 'Admin- Office Based' ? 'selected' : ''; ?>>
                            Admin- Office Based
                        </option>
                        <option value="Admin - Shuttle Driver" <?php echo ($_GET['assigned_area'] ?? '') == 'Admin - Shuttle Driver' ? 'selected' : ''; ?>>Admin - Shuttle Driver
                        </option>
                        <option value="Facilities - Office Based" <?php echo ($_GET['assigned_area'] ?? '') == 'Facilities - Office Based' ? 'selected' : ''; ?>>
                            Facilities - Office Based
                        </option>
                        <option value="Facilities - Canteen 1st Floor" <?php echo ($_GET['assigned_area'] ?? '') == 'Facilities - Canteen 1st Floor' ? 'selected' : ''; ?>>Facilities - Canteen 1st Floor
                        </option>
                        <option value="Facilities - Canteen 2nd Floor" <?php echo ($_GET['assigned_area'] ?? '') == 'Facilities - Canteen 2nd Floor' ? 'selected' : ''; ?>>Facilities - Canteen 2nd Floor
                        </option>
                        <option value="Facilities - 1st Class Lounge" <?php echo ($_GET['assigned_area'] ?? '') == 'Facilities - 1st Class Lounge' ? 'selected' : ''; ?>>Facilities - 1st Class Lounge
                        </option>
                        <option value="Total Quality (QA, Labels)" <?php echo ($_GET['assigned_area'] ?? '') == 'Total Quality (QA, Labels)' ? 'selected' : ''; ?>>Total Quality (QA, Labels)
                        </option>
                        <option value="Total Quality (QC)" <?php echo ($_GET['assigned_area'] ?? '') == 'Total Quality (QC)' ? 'selected' : ''; ?>>Total Quality (QC)
                        </option>
                        <option value="Supply Chain (PMC, PUR)" <?php echo ($_GET['assigned_area'] ?? '') == 'Supply Chain (PMC, PUR)' ? 'selected' : ''; ?>>Supply Chain (PMC, PUR)
                        </option>
                        <option value="Sales and CRM" <?php echo ($_GET['assigned_area'] ?? '') == 'Sales and CRM' ? 'selected' : ''; ?>>Sales and CRM
                        </option>
                        <option value="IT" <?php echo ($_GET['assigned_area'] ?? '') == 'IT' ? 'selected' : ''; ?>>IT
                        </option>
                        <option value="R&I" <?php echo ($_GET['assigned_area'] ?? '') == 'R&I' ? 'selected' : ''; ?>>R&I
                        </option>
                        <option value="HR" <?php echo ($_GET['assigned_area'] ?? '') == 'HR' ? 'selected' : ''; ?>>HR
                        </option>
                        <option value="Warehouse" <?php echo ($_GET['assigned_area'] ?? '') == 'Warehouse' ? 'selected' : ''; ?>>Warehouse
                        </option>
                        <option value="Engineering" <?php echo ($_GET['assigned_area'] ?? '') == 'Engineering' ? 'selected' : ''; ?>>Engineering
                        </option>
                        <option value="Internal Security" <?php echo ($_GET['assigned_area'] ?? '') == 'Internal Security' ? 'selected' : ''; ?>>Internal Security
                        </option>
                        <option value="Logistics - Office Based" <?php echo ($_GET['assigned_area'] ?? '') == 'Logistics - Office Based' ? 'selected' : ''; ?>>Logistics - Office Based
                        </option>
                        <option value="Logistics - Driver & Helper" <?php echo ($_GET['assigned_area'] ?? '') == 'Logistics - Driver & Helper' ? 'selected' : ''; ?>>Logistics - Driver & Helper
                        </option>
                        <option value="Canteen" <?php echo ($_GET['assigned_area'] ?? '') == 'Canteen' ? 'selected' : ''; ?>>Canteen
                        </option>
                    </select>
                </div>

                <div class="space-y-2"><label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date
                        Needed</label><input type="date" name="date_needed"
                        value="<?php echo $_GET['date_needed'] ?? ''; ?>"
                        class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-pink-200 focus:border-primary outline-none transition-all">
                </div>

                <!-- Filter Removed: Users only see their own department -->

                <div class="flex gap-2 col-span-1 md:col-auto">
                    <a href="index.php"
                        class="bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 px-4 py-3 rounded-lg font-bold flex-1 text-center transition-all flex items-center justify-center gap-2"
                        title="Reset Filters">
                        <i class="ph-bold ph-arrow-counter-clockwise"></i>
                    </a>

                    <button type="submit"
                        class="bg-primary hover:bg-primary-hover text-white px-4 py-3 rounded-lg font-bold flex-grow shadow-lg shadow-pink-200 transition-all flex items-center justify-center gap-2">
                        <i class="ph-bold ph-funnel"></i> Filter
                    </button>

                    <?php
                    // Reconstruct current query params for the print_batch.php link
                    $queryParams = $_GET;
                    unset($queryParams['page']); // Remove pagination for export
                    ?>
                    <a href="print_batch.php?<?php echo http_build_query($queryParams); ?>" target="_blank"
                        onclick="return checkExport(event)"
                        class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-3 rounded-lg font-bold transition-all flex items-center justify-center gap-2 shadow-lg"
                        title="Export to PDF">
                        <i class="ph-bold ph-file-pdf"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-pink-100 overflow-x-auto hidden lg:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-pink-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Dates
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Requestor</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Details</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $row):
                            $displayId = date('y', strtotime($row['created_at'])) . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT);
                            $createdAt = new DateTime($row['created_at']);
                            $dateNeeded = new DateTime($row['date_needed']);
                            $approverRequired = $row['primary_approver']; // Now stores department name
                    
                            // New Approver Logic for each row
                            $user_dept = $_SESSION['dept'] ?? '';
                            $departmentMatch = (strcasecmp($user_dept, $approverRequired) === 0);

                            if (!$departmentMatch && !empty($approverRequired)) {
                                foreach ($approver_departments as $dept) {
                                    if (stripos($approverRequired, $dept) !== false || stripos($dept, $approverRequired) !== false) {
                                        $departmentMatch = true;
                                        break;
                                    }
                                }
                            }

                            // Plant Manager override
                            if (stripos($user_position, 'Plant Manager') !== false) {
                                $departmentMatch = true;
                            }

                            $canApprove = $is_approver_in_db && $is_management && $departmentMatch;
                            ?>
                            <tr class="table-row">
                                <td class="px-6 py-4"><span
                                        class="font-black text-slate-800 text-base"><?php echo $displayId; ?></span></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 text-sm mb-0.5 text-slate-500">
                                            <i class="ph-bold ph-calendar-blank"></i> <?php echo $createdAt->format('M d'); ?>
                                        </div>
                                        <?php
                                        $today = new DateTime('today');
                                        $targetDate = new DateTime($row['date_needed']);
                                        $targetDate->setTime(0, 0, 0); // Normalize to midnight
                                
                                        $isPending = $row['status'] === 'Pending';
                                        $isOverdue = $targetDate < $today && $isPending;
                                        $isDueToday = $targetDate == $today && $isPending;

                                        $dateClass = 'text-primary';
                                        $warningIcon = '';

                                        if ($isOverdue) {
                                            $dateClass = 'text-red-500';
                                            $warningIcon = '<i class="ph-fill ph-warning-circle text-red-500 text-lg animate-pulse" title="Overdue"></i>';
                                        } elseif ($isDueToday) {
                                            $dateClass = 'text-amber-500';
                                            $warningIcon = '<i class="ph-fill ph-warning text-amber-500 text-lg" title="Due Today"></i>';
                                        }
                                        ?>
                                        <div class="flex items-center gap-1.5 text-sm font-bold <?php echo $dateClass; ?>">
                                            <i class="ph-fill ph-target"></i>
                                            <?php echo $dateNeeded->format('M d'); ?>
                                            <?php echo $warningIcon; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($row['requestor_name']); ?></span>
                                        <span
                                            class="text-xs text-slate-500 font-bold uppercase"><?php echo htmlspecialchars($row['department']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex w-fit items-center px-3 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200"><i
                                                class="ph-bold ph-map-pin mr-1"></i>
                                            <?php echo htmlspecialchars($row['assigned_area']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusConfig = ['Pending' => ['bg-amber-50', 'text-amber-600', 'border-amber-200', 'ph-clock'], 'Approved' => ['bg-emerald-50', 'text-emerald-600', 'border-emerald-200', 'ph-check-circle'], 'Rejected' => ['bg-red-50', 'text-red-600', 'border-red-200', 'ph-x-circle']];
                                    $s = $statusConfig[$row['status']] ?? $statusConfig['Pending'];
                                    ?>
                                    <span
                                        class="inline-flex items-center justify-center w-28 gap-1.5 py-1.5 rounded-full text-xs font-extrabold border <?php echo "{$s[0]} {$s[1]} {$s[2]}"; ?>"><i
                                            class="ph-fill <?php echo $s[3]; ?>"></i>
                                        <?php echo strtoupper($row['status']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-1">
                                        <a href="view.php?id=<?php echo $row['request_id']; ?>"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-primary hover:bg-pink-50 transition-all"
                                            title="View"><i class="ph-bold ph-eye text-xl"></i></a>
                                        <?php if ($canApprove): ?>
                                            <?php if ($row['status'] == 'Pending'): ?>
                                                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                                <button onclick="openApproveModal(<?php echo $row['request_id']; ?>)"
                                                    class="bg-emerald-500 hover:bg-emerald-600 text-white p-1.5 rounded-md shadow-sm transition-all"
                                                    title="Approve"><i class="ph-bold ph-check text-sm"></i></button>
                                                <button onclick="openRejectModal(<?php echo $row['request_id']; ?>)"
                                                    class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-md shadow-sm transition-all"
                                                    title="Reject"><i class="ph-bold ph-x text-sm"></i></button>

                                            <?php elseif ($row['status'] == 'Rejected'): // Only show Undo for Rejected, NOT Approved ?>
                                                <button onclick="openUndoModal(<?php echo $row['request_id']; ?>)"
                                                    class="text-xs font-bold text-slate-500 hover:text-slate-700 underline px-2">Undo</button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-300"><i
                                        class="ph-duotone ph-magnifying-glass text-6xl mb-4 text-pink-200"></i>
                                    <p class="text-lg font-bold text-slate-400">No requests found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-pink-50/50 border-t border-pink-100 flex justify-between items-center">
                    <p class="text-sm font-semibold text-slate-500">Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                class="pagination-link w-10 h-10 flex items-center justify-center bg-white border border-pink-200 text-slate-600 rounded-xl hover:bg-pink-50 transition-all"><i
                                    class="ph-bold ph-caret-left"></i></a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                class="pagination-link w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all <?php echo $i == $page ? 'bg-primary text-white shadow-md' : 'bg-white border border-pink-200 text-slate-600 hover:bg-pink-50'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                class="pagination-link w-10 h-10 flex items-center justify-center bg-white border border-pink-200 text-slate-600 rounded-xl hover:bg-pink-50 transition-all"><i
                                    class="ph-bold ph-caret-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile/Tablet Card View -->
        <div class="lg:hidden space-y-4">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $row):
                    $displayId = date('y', strtotime($row['created_at'])) . str_pad($row['request_id'], 4, '0', STR_PAD_LEFT);
                    $createdAt = new DateTime($row['created_at']);
                    $dateNeeded = new DateTime($row['date_needed']);
                    $approverRequired = $row['primary_approver'];

                    $user_dept = $_SESSION['dept'] ?? '';
                    $departmentMatch = (strcasecmp($user_dept, $approverRequired) === 0);

                    if (!$departmentMatch && !empty($approverRequired)) {
                        foreach ($approver_departments as $dept) {
                            if (stripos($approverRequired, $dept) !== false || stripos($dept, $approverRequired) !== false) {
                                $departmentMatch = true;
                                break;
                            }
                        }
                    }
                    if (stripos($user_position, 'Plant Manager') !== false) {
                        $departmentMatch = true;
                    }
                    $canApprove = $is_approver_in_db && $is_management && $departmentMatch;

                    $today = new DateTime('today');
                    $targetDate = new DateTime($row['date_needed']);
                    $targetDate->setTime(0, 0, 0);
                    $isPending = $row['status'] === 'Pending';
                    $isOverdue = $targetDate < $today && $isPending;
                    $isDueToday = $targetDate == $today && $isPending;
                    $dateClass = 'text-primary';
                    $warningIcon = '';
                    if ($isOverdue) {
                        $dateClass = 'text-red-500';
                        $warningIcon = '<i class="ph-fill ph-warning-circle text-red-500 text-lg animate-pulse"></i>';
                    } elseif ($isDueToday) {
                        $dateClass = 'text-amber-500';
                        $warningIcon = '<i class="ph-fill ph-warning text-amber-500 text-lg"></i>';
                    }

                    $statusConfig = ['Pending' => ['bg-amber-50', 'text-amber-600', 'border-amber-200', 'ph-clock'], 'Approved' => ['bg-emerald-50', 'text-emerald-600', 'border-emerald-200', 'ph-check-circle'], 'Rejected' => ['bg-red-50', 'text-red-600', 'border-red-200', 'ph-x-circle']];
                    $s = $statusConfig[$row['status']] ?? $statusConfig['Pending'];
                    ?>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-pink-100 flex flex-col gap-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-slate-800 text-lg"><?php echo $displayId; ?></span>
                                    <?php if ($isOverdue): ?>
                                        <span
                                            class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Overdue</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-400 font-bold flex items-center gap-1 mt-0.5">
                                    <i class="ph-bold ph-calendar-blank"></i> Filed: <?php echo $createdAt->format('M d, Y'); ?>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border <?php echo "{$s[0]} {$s[1]} {$s[2]}"; ?>">
                                <i class="ph-fill <?php echo $s[3]; ?>"></i> <?php echo strtoupper($row['status']); ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-lg font-bold border border-slate-200 shadow-sm flex-shrink-0">
                                <?php echo substr($row['requestor_name'], 0, 1); ?>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 leading-tight line-clamp-1">
                                    <?php echo htmlspecialchars($row['requestor_name']); ?>
                                </p>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                    <?php echo htmlspecialchars($row['department']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Date Needed</p>
                                <p class="font-bold text-sm <?php echo $dateClass; ?> flex items-center gap-1.5">
                                    <?php echo $warningIcon . $dateNeeded->format('M d, Y'); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Area</p>
                                <p class="font-bold text-sm text-slate-700 flex items-center gap-1">
                                    <i class="ph-fill ph-map-pin text-pink-400"></i>
                                    <?php echo htmlspecialchars($row['assigned_area']); ?>
                                </p>
                            </div>
                            <!-- Assigned To Removed -->
                        </div>

                        <div class="flex gap-3 pt-2">
                            <a href="view.php?id=<?php echo $row['request_id']; ?>"
                                class="flex-1 bg-slate-800 text-white font-bold py-3 rounded-xl text-center text-sm shadow-md active:scale-95 transition-all">
                                View Details
                            </a>
                            <?php if ($canApprove): ?>
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <button onclick="openApproveModal(<?php echo $row['request_id']; ?>)"
                                        class="flex-1 bg-emerald-500 text-white font-bold py-3 rounded-xl text-center text-sm shadow-md active:scale-95 transition-all">
                                        Approve
                                    </button>
                                    <button onclick="openRejectModal(<?php echo $row['request_id']; ?>)"
                                        class="bg-white border-2 border-red-500 text-red-500 font-bold px-4 rounded-xl text-center text-lg active:scale-95 transition-all flex items-center justify-center">
                                        <i class="ph-bold ph-x"></i>
                                    </button>
                                <?php elseif ($row['status'] == 'Rejected'): ?>
                                    <button onclick="openUndoModal(<?php echo $row['request_id']; ?>)"
                                        class="flex-1 bg-slate-100 text-slate-500 font-bold py-3 rounded-xl text-center text-sm hover:bg-slate-200 transition-all">
                                        Undo Reject
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Mobile Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-between items-center pt-4">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                                class="bg-white border border-pink-200 text-slate-600 px-4 py-2 rounded-xl font-bold text-sm shadow-sm">Previous</a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Page <?php echo $page; ?> /
                            <?php echo $total_pages; ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                                class="bg-primary text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg shadow-pink-200">Next</a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="py-12 text-center bg-white rounded-3xl border border-pink-100">
                    <i class="ph-duotone ph-magnifying-glass text-5xl mb-4 text-pink-200"></i>
                    <p class="text-base font-bold text-slate-400">No requests found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <footer class="py-12 mt-auto w-full text-center border-t border-slate-200/50">
        <div class="flex flex-col items-center justify-center hover:scale-105 transition-transform duration-300"><img
                src="assets/img/footer.png" alt="Logo" class="h-24 w-auto object-contain drop-shadow-sm mb-4">
            <p class="text-slate-400 text-xs font-bold tracking-widest uppercase">© <?php echo date('Y'); ?> La Rose
                Noire. All rights reserved.</p>
        </div>
    </footer>
    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-pink-100">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Reject Request</h3>
            <form action="actions/handle_request.php" method="POST"><input type="hidden" name="action"
                    value="reject"><input type="hidden" name="request_id" id="modalReqId"><textarea
                    name="rejection_reason" rows="3"
                    class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-200 outline-none resize-none"
                    placeholder="Reason for rejection..."></textarea>
                <div class="flex justify-end gap-2 mt-4"><button type="button" onclick="closeRejectModal()"
                        class="px-3 py-1.5 text-slate-500 font-bold hover:bg-slate-50 rounded-lg text-sm">Cancel</button><button
                        type="submit"
                        class="px-3 py-1.5 bg-red-500 text-white font-bold rounded-lg hover:bg-red-600 shadow-lg text-sm">Confirm
                        Reject</button></div>
            </form>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-emerald-100">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Approve Request</h3>
            <form action="actions/handle_request.php" method="POST"><input type="hidden" name="action"
                    value="approve"><input type="hidden" name="request_id" id="approveModalReqId"><textarea
                    name="approval_remarks" rows="3"
                    class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-200 outline-none resize-none"
                    placeholder="Remarks (optional)..."></textarea>
                <div class="flex justify-end gap-2 mt-4"><button type="button" onclick="closeApproveModal()"
                        class="px-3 py-1.5 text-slate-500 font-bold hover:bg-slate-50 rounded-lg text-sm">Cancel</button><button
                        type="submit"
                        class="px-3 py-1.5 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600 shadow-lg text-sm">Confirm
                        Approve</button></div>
            </form>
        </div>
    </div>

    <!-- Empty Export Modal -->
    <div id="emptyExportModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 w-full max-w-sm shadow-2xl text-center border border-slate-100"><i
                class="ph-duotone ph-folder-dashed text-5xl text-slate-300 mb-4"></i>
            <h3 class="text-lg font-bold text-slate-800 mb-2">No Records Found</h3>
            <p class="text-sm text-slate-500 mb-6">There are no requests matching your filters to export.</p><button
                onclick="document.getElementById('emptyExportModal').classList.add('hidden');document.getElementById('emptyExportModal').classList.remove('flex')"
                class="bg-slate-800 text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-slate-700 transition-all">Okay</button>
        </div>
    </div>

    <!-- Undo Modal -->
    <div id="undoModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-slate-200">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i class="ph-fill ph-warning text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Undo Rejection</h3>
            </div>
            <p class="text-sm text-slate-600 mb-6">Are you sure you want to undo this rejection? The request will return
                to <strong>Pending</strong> status.</p>
            <form action="actions/handle_request.php" method="POST">
                <input type="hidden" name="action" value="undo">
                <input type="hidden" name="request_id" id="undoModalReqId">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUndoModal()"
                        class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-50 rounded-lg text-sm">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-amber-500 text-white font-bold rounded-lg hover:bg-amber-600 shadow-lg text-sm">Confirm
                        Undo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const totalRecords = <?php echo $total_records ?? 0; ?>;

        function checkExport(e) {
            if (totalRecords === 0) {
                e.preventDefault();
                const m = document.getElementById('emptyExportModal');
                m.classList.remove('hidden');
                m.classList.add('flex');
                return false;
            }
            return true;
        }

        function openRejectModal(id) { document.getElementById('modalReqId').value = id; document.getElementById('rejectModal').classList.remove('hidden'); document.getElementById('rejectModal').classList.add('flex'); }
        function closeRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); document.getElementById('rejectModal').classList.remove('flex'); }

        function openApproveModal(id) { document.getElementById('approveModalReqId').value = id; document.getElementById('approveModal').classList.remove('hidden'); document.getElementById('approveModal').classList.add('flex'); }
        function closeApproveModal() { document.getElementById('approveModal').classList.add('hidden'); document.getElementById('approveModal').classList.remove('flex'); }

        function openUndoModal(id) { document.getElementById('undoModalReqId').value = id; document.getElementById('undoModal').classList.remove('hidden'); document.getElementById('undoModal').classList.add('flex'); }
        function closeUndoModal() { document.getElementById('undoModal').classList.add('hidden'); document.getElementById('undoModal').classList.remove('flex'); }

        function handleProfileImageLoad(img) {
            // If image was already cached (loaded instantly), skip animation
            if (img.complete && img.naturalWidth > 0) {
                img.style.transition = 'none';
            }
            img.classList.remove('opacity-0');
        }

        function handleImgError(img, empId) {
            const currentSrc = img.src.toLowerCase();
            const baseUrl = '/assets/emp_photos/' + empId;

            // Try extensions in order: jpg -> jpeg -> png -> hide
            if (currentSrc.endsWith('.jpg')) {
                img.src = baseUrl + '.jpeg';
            } else if (currentSrc.endsWith('.jpeg')) {
                img.src = baseUrl + '.png';
            } else if (currentSrc.endsWith('.png')) {
                img.src = baseUrl + '.gif';
            } else {
                img.style.display = 'none';
            }
        }
        document.addEventListener("DOMContentLoaded", function (event) { var scrollpos = sessionStorage.getItem('scrollpos'); if (scrollpos) { window.scrollTo(0, scrollpos); sessionStorage.removeItem('scrollpos'); } });
        var paginationLinks = document.querySelectorAll('.pagination-link'); paginationLinks.forEach(function (link) { link.addEventListener('click', function () { sessionStorage.setItem('scrollpos', window.scrollY); }); });
    </script>
</body>

</html>