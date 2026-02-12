<?php
// create.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase limits for processing large requests (Super Admins)
ini_set('memory_limit', '512M');
set_time_limit(300);
require_once 'includes/db.php';
require_once 'includes/uniform_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_name = $_SESSION['full_name'] ?? 'User';
$employee_id = $_SESSION['user_id'] ?? 0;
$department = $_SESSION['dept'] ?? 'Employee';
$user_position = $_SESSION['position_title'] ?? '';
$photo_id = $_SESSION['user_photo_id'] ?? $employee_id;

// Fetch uniform allowances for this user
$uniformAllowances = getUniformAllowancesForJS($department, $user_position);

// Fetch already requested uniform quantities (for tracking limits)
$uniformRequestHistory = [];
try {
    // Get requests made today (for daily items)
    $stmtDaily = $conn->prepare("
        SELECT ri.item_name, SUM(ri.quantity) as total_qty
        FROM RequestItems ri
        INNER JOIN Requests r ON ri.request_id = r.request_id
        WHERE r.employee_id = :emp_id
        AND r.status != 'Rejected'
        AND CAST(r.created_at AS DATE) = CURRENT_DATE
        AND ri.category LIKE '%uniform%'
        GROUP BY ri.item_name
    ");
    $stmtDaily->execute([':emp_id' => $employee_id]);
    $dailyRequests = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dailyRequests as $req) {
        $uniformRequestHistory['daily'][strtolower($req['item_name'])] = (int) $req['total_qty'];
    }

    // Get requests made in last 6 months (for deployment items)
    $stmtDeployment = $conn->prepare("
        SELECT ri.item_name, SUM(ri.quantity) as total_qty
        FROM RequestItems ri
        INNER JOIN Requests r ON ri.request_id = r.request_id
        WHERE r.employee_id = :emp_id
        AND r.status != 'Rejected'
        AND r.status != 'Rejected'
        AND r.created_at >= CURRENT_DATE - INTERVAL '6 months'
        AND ri.category LIKE '%uniform%'
        GROUP BY ri.item_name
    ");
    $stmtDeployment->execute([':emp_id' => $employee_id]);
    $deploymentRequests = $stmtDeployment->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deploymentRequests as $req) {
        $uniformRequestHistory['deployment'][strtolower($req['item_name'])] = (int) $req['total_qty'];
    }
} catch (PDOException $e) {

}

// Fetch Master Lists
$stmtM = $conn->prepare("SELECT * FROM ItemMaster ORDER BY item_name ASC");
$stmtM->execute();
$items = $stmtM->fetchAll(PDO::FETCH_ASSOC);
$stmtC = $conn->prepare("SELECT DISTINCT category FROM ItemMaster WHERE category IS NOT NULL ORDER BY category ASC");
$stmtC->execute();
$categories = $stmtC->fetchAll(PDO::FETCH_COLUMN);

// Build uniform size map - group uniforms by base name and track sizes/codes
$uniformSizeMap = [];
foreach ($items as $item) {
    $category = strtolower($item['category'] ?? '');
    if (strpos($category, 'uniform') !== false || strpos($category, 'ppe') !== false) {
        $itemName = $item['item_name'];
        $itemCode = $item['item_code'];

        // Extract size from item name
        // Pattern matches (in priority order):
        // - Word sizes: Small, Medium, Large, Extra Large
        // - Numeric XL sizes: 10XL, 2XL, 3XL, etc.
        // - Standard sizes: XXL, XL, XS, S, M, L
        // - Shoe sizes: 36, 37, 38, etc.
        $sizePattern = '/[\s,\-]+(\d{1,2}XL|XXL|XL|XS|S|M|L|Extra Large|Large|Medium|Small|\d{2})$/i';

        if (preg_match($sizePattern, $itemName, $matches)) {
            $size = strtoupper(trim($matches[1]));
            // Normalize word sizes
            $size = str_replace(['EXTRA LARGE', 'LARGE', 'MEDIUM', 'SMALL'], ['XL', 'L', 'M', 'S'], $size);

            // Get base name by removing the size suffix
            $baseName = preg_replace($sizePattern, '', $itemName);
            $baseName = trim($baseName);

            if (!isset($uniformSizeMap[$baseName])) {
                $uniformSizeMap[$baseName] = [
                    'category' => $item['category'],
                    'sub_group' => $item['sub_group'],
                    'uom' => $item['default_uom'],
                    'price' => $item['price'],
                    'sizes' => []
                ];
            }
            $uniformSizeMap[$baseName]['sizes'][$size] = $itemCode;
        } else {
            // No size suffix - it's a one-size item (like Bonnet, Face Mask, etc.)
            if (!isset($uniformSizeMap[$itemName])) {
                $uniformSizeMap[$itemName] = [
                    'category' => $item['category'],
                    'sub_group' => $item['sub_group'],
                    'uom' => $item['default_uom'],
                    'price' => $item['price'],
                    'sizes' => ['N/A' => $itemCode]  // N/A means no size needed
                ];
            }
        }
    }
}

// Fetch Item Sizes History
$stmtSizes = $conn->prepare("SELECT DISTINCT item_name, size FROM RequestItems WHERE size IS NOT NULL AND size <> ''");
$stmtSizes->execute();
$sizeRows = $stmtSizes->fetchAll(PDO::FETCH_ASSOC);

$itemSizes = [];
foreach ($sizeRows as $row) {
    $itemSizes[$row['item_name']][] = $row['size'];
}

// Approver Departments no longer needed for frontend selection
//$stmtDepts = $conn->prepare("SELECT DISTINCT department FROM Approvers ORDER BY department ASC");
//$stmtDepts->execute();
//$approverDepartments = $stmtDepts->fetchAll(PDO::FETCH_COLUMN);
// Instead, just pass empty array or remove usage
$approverDepartments = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Request</title>
    <link rel="icon" href="assets/img/La-Rose-Official-Logo-Revised.jpg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f472b6',
                        'primary-dark': '#ec4899',
                        'primary-light': '#fce7f3'
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }


        // Use JSON_PARTIAL_OUTPUT_ON_ERROR to prevent crashes on bad characters
        const itemMaster = <?php echo json_encode($items, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;
        const categoryList = <?php echo json_encode($categories, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;
        const itemSizes = <?php echo json_encode($itemSizes, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;
        const userPosition = "<?php echo htmlspecialchars($user_position); ?>";
        const userDepartment = "<?php echo htmlspecialchars($department); ?>";
        const approverDepartments = <?php echo json_encode($approverDepartments, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;

        // Uniform allowances for this user (based on department and position)
        const uniformAllowances = <?php echo json_encode($uniformAllowances, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;

        // Existing uniform request history (to track remaining allowances)
        const uniformRequestHistory = <?php echo json_encode($uniformRequestHistory, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;

        // Uniform size map - base names with available sizes and item codes
        const uniformSizeMap = <?php echo json_encode($uniformSizeMap, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;


    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fff1f2;
            min-height: 100vh;
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #fce7f3;
        }

        /* Hide Number Spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="text-slate-700 flex flex-col bg-pastel-pink overflow-x-hidden">

    <nav class="glass-header sticky top-0 z-50 px-6 py-4 mb-8">
        <div class="w-full max-w-[95%] mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-black text-slate-800">Create New Request</h1>

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

                <a href="index.php"
                    class="text-sm font-bold text-slate-400 hover:text-primary transition-colors border-l border-pink-100 pl-6">Cancel</a>
            </div>
        </div>
    </nav>

    <div class="w-full max-w-[95%] mx-auto flex-grow">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-6 p-4 rounded-2xl border <?php echo ($_SESSION['msg_type'] ?? 'info') === 'error'
                ? 'bg-red-50 border-red-200 text-red-700'
                : (($_SESSION['msg_type'] ?? 'info') === 'success'
                    ? 'bg-green-50 border-green-200 text-green-700'
                    : 'bg-blue-50 border-blue-200 text-blue-700'); ?> flex items-center gap-3">
                <i class="ph-fill <?php echo ($_SESSION['msg_type'] ?? 'info') === 'error'
                    ? 'ph-warning-circle'
                    : (($_SESSION['msg_type'] ?? 'info') === 'success'
                        ? 'ph-check-circle'
                        : 'ph-info'); ?> text-xl"></i>
                <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['message']); ?></span>
                <button type="button" onclick="this.parentElement.remove()"
                    class="ml-auto text-current opacity-50 hover:opacity-100">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
        <?php endif; ?>
        <form action="actions/handle_request.php" method="POST" onsubmit="return validateApprovers()">
            <input type="hidden" name="requestor" value="<?php echo htmlspecialchars($user_name); ?>">
            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($employee_id); ?>">
            <input type="hidden" name="photo_id" value="<?php echo htmlspecialchars($photo_id); ?>">
            <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">

            <div class="bg-white p-4 md:p-8 rounded-3xl shadow-sm border border-pink-100 mb-6">
                <h3 class="font-bold text-base text-pink-500 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="ph-duotone ph-user-circle text-xl"></i> Employee Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-xs font-extrabold text-slate-400 uppercase mb-1">Date Filed</span>
                        <span class="block font-black text-slate-800 text-lg"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-xs font-extrabold text-slate-400 uppercase mb-1">Requestor</span>
                        <span
                            class="block font-black text-slate-800 text-lg truncate"><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-xs font-extrabold text-slate-400 uppercase mb-1">Employee ID</span>
                        <span
                            class="block font-black text-slate-800 text-lg font-mono"><?php echo htmlspecialchars($employee_id); ?></span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="block text-xs font-extrabold text-slate-400 uppercase mb-1">Department</span>
                        <span
                            class="block font-black text-slate-800 text-lg truncate"><?php echo htmlspecialchars($department); ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 md:p-8 rounded-3xl shadow-sm border border-pink-100 mb-6">
                <h3 class="font-bold text-base text-pink-500 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="ph-duotone ph-pencil text-xl"></i> Requestor Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-slate-400 uppercase">Assigned Area</label>
                        <select name="assigned_area" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:ring-4 focus:ring-pink-100 outline-none transition-all text-sm">
                            <option value="">Select Area</option>
                            <option value="Production 1">Production 1</option>
                            <option value="Production 2">Production 2</option>
                            <option value="Production 3">Production 3</option>
                            <option value="Production 4">Production 4</option>
                            <option value="Prodn - Office Based">Prodn - Office Based</option>
                            <option value="Admin- Office Based">Admin- Office Based</option>
                            <option value="Admin - Shuttle Driver">Admin - Shuttle Driver</option>
                            <option value="Facilities - Office Based">Facilities - Office Based</option>
                            <option value="Facilities">Facilities</option>
                            <option value="Finance">Finance</option>
                            <option value="Creatives">Creatives</option>
                            <option value="Total Quality (QA, Labels)">Total Quality (QA, Labels)</option>
                            <option value="Total Quality (QC)">Total Quality (QC)</option>
                            <option value="Supply Chain (PMC, PUR)">Supply Chain (PMC, PUR)</option>
                            <option value="Sales and CRM">Sales and CRM</option>
                            <option value="IT">IT</option>
                            <option value="R&I">R&I</option>
                            <option value="HR">HR</option>
                            <option value="Warehouse">Warehouse</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Internal Security">Internal Security</option>
                            <option value="Logistics - Office Based">Logistics - Office Based</option>
                            <option value="Logistics - Driver & Helper">Logistics - Driver & Helper</option>
                            <option value="Canteen">Canteen</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-slate-400 uppercase">Date Needed</label>
                        <input type="date" name="date_needed" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:ring-4 focus:ring-pink-100 outline-none transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-extrabold text-slate-400 uppercase">Time Needed</label>
                        <input type="time" name="time_needed" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:ring-4 focus:ring-pink-100 outline-none transition-all text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-pink-100 overflow-hidden mb-6">
                <div class="p-4 md:p-6 bg-pink-50/50 border-b border-pink-100 flex justify-between items-center">
                    <h3 class="font-black text-xl text-slate-800">Items</h3>
                    <button type="button" onclick="addRow()"
                        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-pink-200 transition-all">
                        <i class="ph-bold ph-plus"></i> Add Item
                    </button>
                </div>
                <div id="itemsContainer" class="p-4 md:p-8 space-y-6"></div>
            </div>

            <div id="authDeductSection"
                class="flex flex-col md:flex-row gap-6 justify-between items-start md:items-center p-4 md:p-8 bg-white rounded-3xl shadow-sm border border-pink-100 mb-6"
                style="display: none;">
                <label class="flex items-start gap-4 cursor-pointer select-none flex-1">
                    <input type="checkbox" name="auth_deduct" value="1" id="authDeductCheckbox"
                        class="mt-1 w-5 h-5 text-primary rounded focus:ring-pink-500 border-gray-300 shrink-0">
                    <div class="space-y-1">
                        <span class="block text-base font-bold text-slate-700">Authorization for Salary Deduction</span>
                        <p class="text-sm text-slate-500 leading-relaxed text-justify">
                            I authorize La Rose Noire Phils., Inc. to deduct from my salary the amounts corresponding to
                            the items declared, in accordance with the applicable payroll cut-off coverage and company
                            payroll policies. In the event of my separation from the company, I further authorize the
                            deduction of any outstanding balances from my final or separation pay, subject to applicable
                            labor laws.
                        </p>
                    </div>
                </label>

                <div class="flex flex-col gap-4 text-right pl-4 border-l border-pink-100 min-w-[200px]">
                    <label class="flex items-center justify-end gap-2 cursor-pointer select-none">
                        <span class="text-sm font-bold text-slate-600">Company Issued?</span>
                        <input type="checkbox" name="company_issued" value="1" id="companyIssuedCheckbox"
                            onchange="calculateDeduction()"
                            class="w-4 h-4 text-primary rounded focus:ring-pink-500 border-gray-300">
                    </label>

                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase">Total Deduction</span>
                        <span class="block text-2xl font-black text-pink-500" id="deductionAmount">PHP 0.00</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-8">
                <button type="submit"
                    class="bg-emerald-500 hover:bg-emerald-600 text-white px-10 py-4 rounded-2xl font-black shadow-lg transition-all transform hover:scale-105 hover:shadow-emerald-200 shrink-0">
                    Create Request
                </button>
            </div>
        </form>
    </div>

    <footer class="py-12 mt-auto w-full text-center">
        <div class="flex flex-col items-center justify-center hover:scale-105 transition-transform duration-300">
            <img src="assets/img/footer.png" alt="Logo" class="h-24 w-auto object-contain drop-shadow-sm mb-4">
            <p class="text-slate-400 text-xs font-bold tracking-widest uppercase">© <?php echo date('Y'); ?> La Rose
                Noire. All rights reserved.</p>
        </div>
    </footer>

    <datalist id="categoryOptions"></datalist>

    <!-- Error Modal -->
    <div id="errorModal" class="fixed inset-0 z-[60] hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop">
        </div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal Panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    id="modalPanel">
                    <div class="bg-white p-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-12 w-12 flex-shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="ph-fill ph-warning-circle text-2xl text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="modalTitle">Restriction Error</h3>
                                <p class="text-sm text-slate-500 mt-1" id="modalMessage">
                                    You cannot add items for a different approver in the same request.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse">
                        <button type="button" onclick="closeErrorModal()"
                            class="inline-flex w-full justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto transition-all">
                            I Understand
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
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
    </script>
</body>

</html>