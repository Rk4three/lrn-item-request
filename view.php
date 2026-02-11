<?php
// view.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$id = $_GET['id'];
$user_position = $_SESSION['position_title'] ?? '';
$is_management = (stripos($user_position, 'Supervisor') !== false) || (stripos($user_position, 'Manager') !== false);

$sql = "SELECT * FROM Requests WHERE request_id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die("Request not found.");
}

// --- ACCESS CONTROL START ---
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserPos = $_SESSION['position_title'] ?? '';
$currentUserDept = $_SESSION['dept'] ?? '';
$approver_departments = $_SESSION['approver_departments'] ?? [];

// 1. Is Requestor?
$isRequestor = ($req['employee_id'] == $currentUserId);

// 2. Is Super Viewer? (4 or 1320)
$isSuperViewer = ($currentUserId == 4 || $currentUserId == 1320);

// 3. Is Authorized Approver/Viewer?
// MODIFICATION: Everyone can view requests now.
$canView = true;

// Original logic preserved in comments if needed for rollback:
/*
if ($isRequestor || $isSuperViewer) {
    $canView = true;
} else {
    // Check Content Type
    $hasUniforms = false;
    $stmtCheckItems = $conn->prepare("SELECT COUNT(*) FROM RequestItems WHERE request_id = :id AND (category LIKE '%Uniform%' OR category LIKE '%PPE%')");
    $stmtCheckItems->execute([':id' => $id]);
    if ($stmtCheckItems->fetchColumn() > 0) {
        $hasUniforms = true;
    }

    // Laundry Manager Check
    $isLaundry = (stripos($currentUserPos, 'Laundry') !== false) || (stripos($currentUserDept, 'Laundry') !== false);
    $isManagement = (stripos($currentUserPos, 'Supervisor') !== false) || (stripos($currentUserPos, 'Manager') !== false);
    
    if ($hasUniforms && $isLaundry && $isManagement) {
        $canView = true;
    } 
    // Normal Approver Logic (Department Match)
    else {
        // Check Dept Match
        $reqDept = $req['department'];
        if (strcasecmp($reqDept, $currentUserDept) === 0) {
            // Same Dept - Are they manager?
            // if ($isManagement) $canView = true; // Original likely allowed peers too? logic was mixed.
             $canView = true; // Assuming peers in same dept could view? 
        } else {
            // Different Dept - Are they approver?
            $approver_departments = $_SESSION['approver_departments'] ?? [];
            foreach ($approver_departments as $d) {
                if (stripos($reqDept, $d) !== false || stripos($d, $reqDept) !== false) {
                    $canView = true;
                    break;
                }
            }
        }
    }
}
*/

if (!$canView) {
    // Show friendly error or redirect
    echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
    echo "<h1 style='color: #e11d48;'>Unauthorized Access</h1>";
    echo "<p>You do not have permission to view this request.</p>";
    echo "<a href='index.php' style='color: #db2777; font-weight: bold;'>Return to Dashboard</a>";
    echo "</div>";
    exit();
}
// --- ACCESS CONTROL END ---

$stmtItems = $conn->prepare("SELECT * FROM RequestItems WHERE request_id = :id");
$stmtItems->execute([':id' => $id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
$totalItems = count($items);

$approverRequired = '';
if (!empty($items)) {
    $approverRequired = $items[0]['approver']; // This now stores the department name
}

// New Approver Logic Matches actions/handle_request.php
$userId = $_SESSION['user_id'] ?? 0;

// 1. Super Approvers
$isSuperApprover = ($userId == 4 || $userId == 1320);

// 2. Check for Uniforms
$hasUniforms = false;
foreach ($items as $item) {
    if (stripos($item['category'], 'Uniform') !== false || stripos($item['category'], 'PPE') !== false) {
        $hasUniforms = true;
        break;
    }
}

// 3. Laundry Management
$isLaundry = (stripos($user_position, 'Laundry') !== false) || (stripos($_SESSION['dept'] ?? '', 'Laundry') !== false);

// Enhanced Management Check
$userRoleProfile = $_SESSION['role_profile'] ?? '';
$userJobLevel = $_SESSION['job_level'] ?? '';
$userPosition = $_SESSION['position_title'] ?? '';

$isManagement = false;
$mgmtKeywords = ['Manager', 'Supervisor', 'Head'];

foreach ($mgmtKeywords as $keyword) {
    if (stripos($userRoleProfile, $keyword) !== false || 
        stripos($userJobLevel, $keyword) !== false || 
        stripos($userPosition, $keyword) !== false) {
        $isManagement = true;
        break;
    }
}

$isLaundryManager = $isLaundry && $isManagement;

$canApprove = false;

if ($hasUniforms) {
    // Strict Uniform Rules
    if ($isSuperApprover || $isLaundryManager) {
        $canApprove = true;
    }
} else {
    // Normal Rules
    if ($isSuperApprover) {
        $canApprove = true;
    } elseif ($isManagement) {
        // Check Department Match
        $departmentMatch = false;
        $userDept = $_SESSION['dept'] ?? '';
        
        // Match User Dept vs Request Dept (Approver field stores request department)
        if (stripos($approverRequired, $userDept) !== false || stripos($userDept, $approverRequired) !== false) {
            $departmentMatch = true;
        }
        
        // Match Approver Depts list
        if (!$departmentMatch) {
            foreach ($approver_departments as $dept) {
                if (stripos($approverRequired, $dept) !== false || stripos($dept, $approverRequired) !== false) {
                    $departmentMatch = true;
                    break;
                }
            }
        }
        
        // Plant Manager Override
        if (stripos($user_position, 'Plant Manager') !== false) {
            $departmentMatch = true;
        }

        if ($departmentMatch) {
            $canApprove = true;
        }
    }
}

// Can approve if: is in approvers DB + has manager/supervisor title + department matches
// $canApprove calculated above

$requestorPhotoId = $req['requestor_photo_id'] ?? $req['employee_id'];

// Status Config
$statusConfig = [
    'Pending' => ['bg-white', 'text-amber-500', 'border-white/20', 'ph-clock'],
    'Approved' => ['bg-white', 'text-emerald-500', 'border-white/20', 'ph-check-circle'],
    'Rejected' => ['bg-white', 'text-red-500', 'border-white/20', 'ph-x-circle']
];
$s = $statusConfig[$req['status']] ?? $statusConfig['Pending'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Request <?php echo $id; ?></title>
    <link rel="icon" href="assets/img/La-Rose-Official-Logo-Revised.jpg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f472b6',
                        'primary-dark': '#ec4899',
                        'pastel-pink': '#fff1f2',
                        'pastel-accent': '#fbcfe8'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* MATCHING PRINT_BATCH CSS EXACTLY */
        @media print {
            @page {
                margin: 0.25cm;
                size: auto;
            }

            body {
                background-color: white;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* Override container to match batch print style */
            .print-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                border-radius: 12px;
                overflow: hidden;
                break-inside: avoid;
            }

            /* Scaling to match batch */
            h1 {
                font-size: 1.25rem !important;
            }

            .text-lg {
                font-size: 0.875rem !important;
            }

            .text-base {
                font-size: 0.75rem !important;
            }

            td,
            th {
                padding-top: 4px !important;
                padding-bottom: 4px !important;
                font-size: 0.7rem !important;
            }

            .header-print {
                padding: 0.75rem 1.5rem !important;
            }

            .info-block {
                padding: 1rem !important;
            }

            .print-section {
                padding: 1rem !important;
            }
        }
    </style>
</head>

<body class="text-slate-800 p-4 md:p-8 min-h-screen flex flex-col items-center bg-pastel-pink overflow-x-hidden">

    <div class="w-full max-w-4xl mb-6 no-print">
        <a href="index.php"
            class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-primary transition-colors bg-white px-6 py-3 rounded-2xl shadow-md hover:shadow-lg">
            <i class="ph-bold ph-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div
        class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden print-container flex-grow border border-pink-100">

        <div class="bg-pink-300 text-white p-8 flex justify-between items-center relative overflow-hidden header-print">
            <div class="relative z-10 flex items-center gap-4">
                <img src="assets/img/La-Rose-Official-Logo-Revised.jpg"
                    class="h-14 w-14 rounded-xl shadow-md bg-white object-cover">
                <div>
                    <h1 class="text-2xl font-black mb-0">Item Request</h1>
                    <p class="text-pink-100 font-mono tracking-widest text-xs opacity-90">ID:
                        <?php echo date('y', strtotime($req['created_at'])) . str_pad($req['request_id'], 4, '0', STR_PAD_LEFT); ?>
                    </p>
                </div>
            </div>
            <div class="relative z-10 text-right">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-extrabold shadow-sm <?php echo "{$s[0]} {$s[1]}"; ?>">
                    <i class="ph-fill <?php echo $s[3]; ?> text-sm"></i> <?php echo strtoupper($req['status']); ?>
                </span>
            </div>
        </div>

        <div class="p-4 md:p-8 print-section info-block">
            <div class="flex flex-col md:flex-row gap-8 items-start mb-6">
                <div class="flex items-center gap-4 min-w-[200px]">
                    <div
                        class="h-16 w-16 bg-slate-50 rounded-2xl relative overflow-hidden flex items-center justify-center border border-pink-100 shadow-sm flex-shrink-0">
                        <div class="absolute inset-0 flex items-center justify-center text-primary font-bold">
                            <?php echo substr($req['requestor_name'], 0, 1); ?>
                        </div>
                        <img src="http://10.2.0.8/lrnph/emp_photos/<?php echo $requestorPhotoId; ?>.jpg"
                            id="reqProfileImg"
                            class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300"
                            onload="this.classList.remove('opacity-0')" 
                            onerror="handleImgError(this, '<?php echo $requestorPhotoId; ?>')" alt="User">
                        <script>
                            // Immediately check if image is cached
                            (function() {
                                var img = document.getElementById('reqProfileImg');
                                if (img.complete && img.naturalHeight > 0) {
                                    img.style.transition = 'none';
                                    img.classList.remove('opacity-0');
                                }
                            })();
                        </script>
                    </div>
                    <div>
                        <span
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Requestor</span>
                        <h3 class="font-bold text-slate-800 text-lg leading-tight">
                            <?php echo htmlspecialchars($req['requestor_name']); ?></h3>
                        <p class="text-xs font-bold text-primary"><?php echo htmlspecialchars($req['department']); ?>
                        </p>
                        <p
                            class="text-[10px] font-mono font-bold text-slate-400 mt-0.5 bg-slate-50 inline-block px-1 rounded">
                            ID: <?php echo htmlspecialchars($req['employee_id']); ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-x-12 gap-y-4 flex-grow w-full border-l border-slate-100 pl-8 md:pl-8">
                    <div>
                        <span class="block text-[10px] font-extrabold text-slate-400 uppercase mb-1">Date Needed</span>
                        <div class="flex items-center gap-2">
                            <i class="ph-duotone ph-calendar text-pink-400"></i>
                            <span
                                class="font-bold text-slate-700 text-sm"><?php echo date('M d, Y', strtotime($req['date_needed'])); ?></span>
                        </div>
                        <span
                            class="text-xs font-semibold text-slate-400 pl-6 block"><?php echo htmlspecialchars($req['time_needed']); ?></span>
                    </div>

                    <div>
                        <span class="block text-[10px] font-extrabold text-slate-400 uppercase mb-1">Area / Date
                            Filed</span>
                        <div class="flex items-center gap-2">
                            <i class="ph-duotone ph-map-pin text-pink-400"></i>
                            <span
                                class="font-bold text-slate-700 text-sm"><?php echo htmlspecialchars($req['assigned_area']); ?></span>
                        </div>
                        <span
                            class="text-xs font-semibold text-slate-400 pl-6 block"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($req['status'] !== 'Pending' && !empty($req['action_by'])): ?>
                <div class="border-t border-slate-100 pt-4 mt-2">
                    <div>
                        <span class="block text-[10px] font-extrabold text-slate-400 uppercase mb-0.5">
                            <span
                                class="w-2 h-2 rounded-full inline-block mr-1 <?php echo $req['status'] === 'Approved' ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                            <?php echo $req['status'] === 'Approved' ? 'Approved By' : 'Rejected By'; ?>
                        </span>
                        <p class="font-bold text-slate-700 text-sm leading-tight">
                            <?php echo htmlspecialchars($req['action_by']); ?>
                        </p>
                        <span
                            class="text-slate-400 font-normal text-[10px]"><?php echo date('M d, Y h:i A', strtotime($req['action_date'])); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'Rejected' && !empty($req['rejection_reason'])): ?>
                <div class="mt-4 bg-red-50 p-3 border border-red-100 rounded-xl flex gap-3 items-start">
                    <i class="ph-fill ph-warning-circle text-red-500 text-lg mt-0.5"></i>
                    <div>
                        <span class="block text-red-900 font-bold text-xs uppercase mb-1">Rejection Reason</span>
                        <p class="text-red-700 text-sm leading-relaxed">
                            <?php echo htmlspecialchars($req['rejection_reason']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'Approved' && !empty($req['rejection_reason'])): ?>
                <div class="mt-4 bg-emerald-50 p-3 border border-emerald-100 rounded-xl flex gap-3 items-start">
                    <i class="ph-fill ph-check-circle text-emerald-500 text-lg mt-0.5"></i>
                    <div>
                        <span class="block text-emerald-900 font-bold text-xs uppercase mb-1">Approval Remarks</span>
                        <p class="text-emerald-700 text-sm leading-relaxed">
                            <?php echo htmlspecialchars($req['rejection_reason']); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="px-4 pb-4 md:px-8 md:pb-8 print-section bg-white">
            <div class="flex justify-between items-end mb-2 border-b border-slate-200 pb-2">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest">Requested Items</h3>
                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded">Count:
                    <?php echo $totalItems; ?></span>
            </div>

            <?php 
            // Check if any item has Services category
            $hasServices = false;
            foreach ($items as $item) {
                if (strcasecmp(trim($item['category']), 'Services') === 0) {
                    $hasServices = true;
                    break;
                }
            }
            ?>

            <!-- Desktop Table -->
            <div class="hidden md:block">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">Code</th>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">Item Name</th>
                            <?php if ($hasServices): ?>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">Items Needed</th>
                            <?php endif; ?>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">Size</th>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">Category</th>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase text-right">Qty</th>
                            <th class="py-2 px-2 text-[10px] font-bold text-slate-500 uppercase">UOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 px-2 text-xs font-mono text-slate-600">
                                    <?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td class="py-3 px-2 text-sm font-bold text-slate-800">
                                    <?php echo htmlspecialchars($item['item_name']); ?></td>
                                <?php if ($hasServices): ?>
                                <td class="py-3 px-2 text-xs text-slate-600">
                                    <?php 
                                    if (strcasecmp(trim($item['category']), 'Services') === 0) {
                                        echo htmlspecialchars($item['items_needed'] ?: '—');
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <?php endif; ?>
                                <td class="py-3 px-2 text-xs text-slate-600">
                                    <?php echo htmlspecialchars($item['size'] ?: 'N/A'); ?></td>
                                <td class="py-3 px-2 text-xs text-slate-600"><?php echo htmlspecialchars($item['category']); ?>
                                </td>
                                <td class="py-3 px-2 text-sm font-bold text-slate-800 text-right">
                                    <?php echo $item['quantity']; ?></td>
                                <td class="py-3 px-2 text-xs font-bold text-slate-500 uppercase">
                                    <?php echo htmlspecialchars($item['uom']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List -->
            <div class="md:hidden space-y-3">
                <?php foreach ($items as $item): 
                    $isService = strcasecmp(trim($item['category']), 'Services') === 0;
                ?>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex flex-col gap-2">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <p class="font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo htmlspecialchars($item['item_code']); ?></p>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-sm font-black text-slate-800 bg-white border border-slate-200 px-2.5 py-1 rounded-lg shadow-sm">
                                <?php echo $item['quantity']; ?> <span class="text-[10px] text-slate-400 font-bold uppercase ml-0.5"><?php echo htmlspecialchars($item['uom']); ?></span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-slate-200/50 mt-1">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Size</p>
                            <p class="text-xs font-bold text-slate-600"><?php echo htmlspecialchars($item['size'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Category</p>
                            <p class="text-xs font-bold text-slate-600"><?php echo htmlspecialchars($item['category']); ?></p>
                        </div>
                        <?php if ($isService): ?>
                        <div class="sm:col-span-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Items Needed</p>
                            <p class="text-xs font-bold text-slate-600"><?php echo htmlspecialchars($item['items_needed'] ?: '—'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100">
                <?php 
                // Logic to decide if we show deduction block
                $showDeduction = false;
                
                // 1. Check Categories (Uniform or PPE often have deductions)
                foreach ($items as $item) {
                    $cat = trim($item['category']);
                    if (strcasecmp($cat, 'Uniform') === 0 || strcasecmp($cat, 'PPE') === 0) {
                        $showDeduction = true;
                        break;
                    }
                }

                // 2. If explicitly authorized or company issued, definitely show it
                if ($req['auth_deduct'] || !empty($req['is_company_issued'])) {
                    $showDeduction = true;
                }
                
                if ($showDeduction): 
                ?>
                <?php 
                // Calculate Total Deduction Amount
                // We need to fetch prices from ItemMaster for the current items in this request
                // Since we don't store historical price in RequestItems, we use current master price (typical for this simple app)
                $totalDeduction = 0;
                foreach ($items as $item) {
                    $stmtPrice = $conn->prepare("SELECT price FROM ItemMaster WHERE item_name = :name");
                    $stmtPrice->execute([':name' => $item['item_name']]);
                    $price = $stmtPrice->fetchColumn();
                    if ($price) {
                        $totalDeduction += ($price * $item['quantity']);
                    }
                }
                ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
                    <!-- Authority -->
                    <div class="bg-slate-50 px-4 py-3 rounded-lg border border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500 uppercase text-xs">Deduction Authority</span>
                        <?php if ($req['auth_deduct']): ?>
                            <span class="text-emerald-600 font-bold text-xs flex items-center gap-1"><i class="ph-bold ph-check"></i> GRANTED</span>
                        <?php else: ?>
                            <span class="text-slate-400 font-bold text-xs flex items-center gap-1"><i class="ph-bold ph-x"></i> NOT GRANTED</span>
                        <?php endif; ?>
                    </div>

                    <!-- Waiver -->
                    <div class="bg-slate-50 px-4 py-3 rounded-lg border border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500 uppercase text-xs">Company Issued</span>
                        <?php if (!empty($req['is_company_issued'])): ?>
                            <span class="text-blue-600 font-bold text-xs flex items-center gap-1"><i class="ph-bold ph-check"></i> YES (WAIVED)</span>
                        <?php else: ?>
                            <span class="text-slate-400 font-bold text-xs flex items-center gap-1"><i class="ph-bold ph-x"></i> NO</span>
                        <?php endif; ?>
                    </div>

                    <!-- Amount -->
                    <div class="bg-slate-50 px-4 py-3 rounded-lg border border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500 uppercase text-xs">Total Amount</span>
                        <?php if (!empty($req['is_company_issued'])): ?>
                             <div class="text-right leading-tight">
                                <span class="block text-green-500 font-black text-sm">PHP <?php echo number_format($totalDeduction, 2); ?></span>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">(WAIVED)</span>
                             </div>
                        <?php else: ?>
                            <span class="text-pink-500 font-black text-sm">PHP <?php echo number_format($totalDeduction, 2); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6 bg-white flex justify-center gap-4 no-print border-t border-slate-100">
            <button onclick="window.print()"
                class="px-6 py-3 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-700 transition-all flex items-center gap-2 shadow-lg shadow-slate-200">
                <i class="ph-bold ph-printer"></i> Print / Save PDF
            </button>
            <?php if ($canApprove): ?>
                <?php if ($req['status'] == 'Pending'): ?>
                    <div class="w-px h-10 bg-slate-200 mx-2"></div>
                    <button onclick="openApproveModal(<?php echo $req['request_id']; ?>)"
                        class="px-6 py-3 rounded-xl bg-emerald-500 text-white font-bold hover:bg-emerald-600 transition-all flex items-center gap-2 shadow-lg shadow-emerald-200"><i
                            class="ph-bold ph-check-circle"></i> Approve</button>
                    <button onclick="openRejectModal(<?php echo $req['request_id']; ?>)"
                        class="px-6 py-3 rounded-xl bg-red-500 text-white font-bold hover:bg-red-600 transition-all flex items-center gap-2 shadow-lg shadow-red-200"><i
                            class="ph-bold ph-x-circle"></i> Reject</button>

                <?php elseif ($req['status'] == 'Rejected'): ?>
                    <div class="w-px h-10 bg-slate-200 mx-2"></div>
                    <button onclick="openUndoModal(<?php echo $req['request_id']; ?>)"
                        class="px-6 py-3 rounded-xl bg-slate-100 text-slate-500 font-bold hover:bg-slate-200 transition-all flex items-center gap-2"><i
                            class="ph-bold ph-arrow-u-up-left"></i> Undo</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="rejectModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 no-print">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-pink-100">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Reject Request</h3>
            <form action="actions/handle_request.php" method="POST"><input type="hidden" name="action" value="reject"><input
                    type="hidden" name="request_id" id="modalReqId"><textarea name="rejection_reason" rows="3"
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

    <div id="approveModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 no-print">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-emerald-100">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Approve Request</h3>
            <form action="actions/handle_request.php" method="POST"><input type="hidden" name="action" value="approve"><input
                    type="hidden" name="request_id" id="approveModalReqId"><textarea name="approval_remarks" rows="3"
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

    <!-- Undo Modal -->
    <div id="undoModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 no-print">
        <div
            class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-100 transition-all border border-slate-200">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i class="ph-fill ph-warning text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Undo Rejection</h3>
            </div>
            <p class="text-sm text-slate-600 mb-6">Are you sure you want to undo this rejection? The request will return to <strong>Pending</strong> status.</p>
            <form action="actions/handle_request.php" method="POST">
                <input type="hidden" name="action" value="undo">
                <input type="hidden" name="request_id" id="undoModalReqId">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUndoModal()"
                        class="px-4 py-2 text-slate-500 font-bold hover:bg-slate-50 rounded-lg text-sm">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-amber-500 text-white font-bold rounded-lg hover:bg-amber-600 shadow-lg text-sm">Confirm Undo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id) { document.getElementById('modalReqId').value = id; document.getElementById('rejectModal').classList.remove('hidden'); document.getElementById('rejectModal').classList.add('flex'); }
        function closeRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); document.getElementById('rejectModal').classList.remove('flex'); }

        function openApproveModal(id) { document.getElementById('approveModalReqId').value = id; document.getElementById('approveModal').classList.remove('hidden'); document.getElementById('approveModal').classList.add('flex'); }
        function closeApproveModal() { document.getElementById('approveModal').classList.add('hidden'); document.getElementById('approveModal').classList.remove('flex'); }

        function openUndoModal(id) { document.getElementById('undoModalReqId').value = id; document.getElementById('undoModal').classList.remove('hidden'); document.getElementById('undoModal').classList.add('flex'); }
        function closeUndoModal() { document.getElementById('undoModal').classList.add('hidden'); document.getElementById('undoModal').classList.remove('flex'); }

        function handleImgError(img, empId) { 
            const currentSrc = img.src.toLowerCase(); 
            const baseUrl = 'http://10.2.0.8/lrnph/emp_photos/' + empId; 
            
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