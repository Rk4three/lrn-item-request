<?php
// print_batch.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// --- REUSE FILTER LOGIC ---
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
if (!empty($_GET['approver_filter']) && $_GET['approver_filter'] !== 'All') {
    $whereClauses[] = "EXISTS (SELECT 1 FROM RequestItems ri WHERE ri.request_id = r.request_id AND ri.approver = :app_filter)";
    $params[':app_filter'] = $_GET['approver_filter'];
}

$sqlWhere = "";
if (count($whereClauses) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $whereClauses);
}

// FETCH ALL
$sql = "SELECT r.* FROM Requests r $sqlWhere ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($requests)) {
    die("No records found to export.");
}

// Status Config for consistency
$statusConfig = [
    'Pending' => ['bg-white', 'text-amber-500', 'border-white/20', 'ph-clock'],
    'Approved' => ['bg-white', 'text-emerald-500', 'border-white/20', 'ph-check-circle'],
    'Rejected' => ['bg-white', 'text-red-500', 'border-white/20', 'ph-x-circle']
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Export Requests</title>
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
            background: #eee;
            padding: 20px;
        }

        .page-container {
            background: white;
            width: 21cm;
            margin: 0 auto 20px;
            overflow: hidden;
            position: relative;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #fce7f3;
        }

        @media print {
            @page {
                margin: 0.25cm;
                size: auto;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page-container {
                width: 100%;
                margin: 0;
                box-shadow: none;
                border: 1px solid #ddd;
                border-radius: 12px;
                page-break-after: always;
                break-inside: avoid;
            }

            .page-container:last-child {
                page-break-after: auto;
            }

            /* Scaling */
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

<body>

    <div class="fixed top-4 right-4 no-print z-50 flex gap-2">
        <button onclick="window.print()"
            class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all flex items-center gap-2">
            <i class="ph-bold ph-printer"></i> Save as PDF
        </button>
        <button onclick="window.close()"
            class="bg-gray-600 text-white px-6 py-3 rounded-lg font-bold shadow-lg hover:bg-gray-700 transition-all">
            Close
        </button>
    </div>

    <?php foreach ($requests as $req):
        $stmtI = $conn->prepare("SELECT * FROM RequestItems WHERE request_id = :id");
        $stmtI->execute([':id' => $req['request_id']]);
        $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);
        $totalItems = count($items);
        $requestorPhotoId = $req['requestor_photo_id'] ?? $req['employee_id'];
        $s = $statusConfig[$req['status']] ?? $statusConfig['Pending'];
        ?>

        <div class="page-container overflow-hidden">

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

            <div class="p-8 info-block print-section">
                <div class="flex flex-col md:flex-row gap-8 items-start mb-6">
                    <div class="flex items-center gap-4 min-w-[200px]">
                        <div
                            class="h-16 w-16 bg-slate-50 rounded-2xl relative overflow-hidden flex items-center justify-center border border-pink-100 shadow-sm flex-shrink-0">
                            <div class="absolute inset-0 flex items-center justify-center text-primary font-bold">
                                <?php echo substr($req['requestor_name'], 0, 1); ?>
                            </div>
                            <img src="/assets/emp_photos/<?php echo $requestorPhotoId; ?>.jpg"
                                class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300"
                                onload="this.classList.remove('opacity-0')" onerror="this.style.display='none'" alt="User">
                        </div>
                        <div>
                            <span
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Requestor</span>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight">
                                <?php echo htmlspecialchars($req['requestor_name']); ?>
                            </h3>
                            <p class="text-xs font-bold text-primary"><?php echo htmlspecialchars($req['department']); ?>
                            </p>
                            <p
                                class="text-[10px] font-mono font-bold text-slate-400 mt-0.5 bg-slate-50 inline-block px-1 rounded">
                                ID: <?php echo htmlspecialchars($req['employee_id']); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-12 gap-y-4 flex-grow w-full border-l border-slate-100 pl-8">
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
                                <?php echo htmlspecialchars($req['rejection_reason']); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="px-8 pb-8 print-section bg-white">
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
                                    <?php echo htmlspecialchars($item['item_code']); ?>
                                </td>
                                <td class="py-3 px-2 text-sm font-bold text-slate-800">
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                </td>
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
                                    <?php echo htmlspecialchars($item['size'] ?: 'N/A'); ?>
                                </td>
                                <td class="py-3 px-2 text-xs text-slate-600"><?php echo htmlspecialchars($item['category']); ?>
                                </td>
                                <td class="py-3 px-2 text-sm font-bold text-slate-800 text-right">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="py-3 px-2 text-xs font-bold text-slate-500 uppercase">
                                    <?php echo htmlspecialchars($item['uom']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="text-xs bg-slate-50 px-4 py-3 rounded-lg border border-slate-100 inline-block w-full">
                        <span class="font-bold text-slate-500 uppercase mr-2">PPE Deduction:</span>
                        <?php echo $req['auth_deduct'] ? '<span class="text-slate-700 font-bold">AUTHORIZED</span>' : '<span class="text-slate-400 font-bold">NOT AUTHORIZED</span>'; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        window.onload = function () { setTimeout(function () { window.print(); }, 800); };
    </script>
</body>

</html>