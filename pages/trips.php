<?php
/** @var \PDO $db */
require_once '../includes/check_auth.php';
require_once '../models/Trip.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';
require_once '../models/Route.php';

$tripModel = new Trip($db);
$truckModel = new Truck($db);
$driverModel = new Driver($db);
$routeModel = new Route($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'trip_number' => $tripModel->generateTripNumber(),
                    'truck_id' => $_POST['truck_id'],
                    'driver_id' => $_POST['driver_id'],
                    'route_id' => !empty($_POST['route_id']) ? $_POST['route_id'] : null,
                    'client_name' => $_POST['client_name'],
                    'client_phone' => $_POST['client_phone'],
                    'start_location' => $_POST['start_location'],
                    'end_location' => $_POST['end_location'],
                    'start_time' => $_POST['start_time'],
                    'status' => $_POST['status'],
                    'priority' => $_POST['priority'],
                    'notes' => $_POST['notes'],
                    'created_by' => $_SESSION['user_id']
                ];
                $tripModel->create($data);
                break;
            case 'update_status':
                $tripModel->updateStatus($_POST['id'], $_POST['status']);
                break;
        }
        header("Location: trips.php");
        exit();
    }
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search_filter = $_GET['search'] ?? '';

// Get all trips with filters
$trips = $tripModel->getAll();
$trucks = $truckModel->getAll();
$drivers = $driverModel->getAll();
$routes = $routeModel->getAll();

// Apply filters
$filtered_trips = array_filter($trips, function($trip) use ($status_filter, $priority_filter, $search_filter) {
    if ($status_filter !== 'all' && $trip['status'] !== $status_filter) {
        return false;
    }
    if ($priority_filter !== 'all' && $trip['priority'] !== $priority_filter) {
        return false;
    }
    if (!empty($search_filter)) {
        $search = strtolower($search_filter);
        $trip_number = strtolower($trip['trip_number'] ?? '');
        $client_name = strtolower($trip['client_name'] ?? '');
        $truck_plate = strtolower($trip['truck_plate'] ?? '');
        $driver_name = strtolower($trip['driver_name'] ?? '');
        if (strpos($trip_number, $search) === false && 
            strpos($client_name, $search) === false && 
            strpos($truck_plate, $search) === false && 
            strpos($driver_name, $search) === false) {
            return false;
        }
    }
    return true;
});

// Get documents for completed trips
$documents = [];
if (!empty($filtered_trips)) {
    $tripIds = array_column($filtered_trips, 'id');
    $placeholders = implode(',', array_fill(0, count($tripIds), '?'));
    $docSql = "SELECT * FROM documents WHERE entity_type = 'trip' AND entity_id IN ($placeholders) AND document_type = 'delivery'";
    $docStmt = $db->prepare($docSql);
    foreach($tripIds as $idx => $id) {
        $docStmt->bindValue($idx + 1, $id, PDO::PARAM_INT);
    }
    $docStmt->execute();
    $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group documents by trip_id
    $docMap = [];
    foreach($documents as $doc) {
        $docMap[$doc['entity_id']][] = $doc;
    }
}

// Get status counts for filter badges
$statusCounts = [];
foreach($trips as $trip) {
    $statusCounts[$trip['status']] = ($statusCounts[$trip['status']] ?? 0) + 1;
}
$totalTrips = count($trips);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; }
        
        .trip-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #dbe2ea;
    box-shadow: 
        0 2px 6px rgba(15, 23, 42, 0.06),
        0 8px 20px rgba(15, 23, 42, 0.04);
    transition: all 0.25s ease;
    overflow: hidden;
    position: relative;
}
        .trip-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #3b82f6;
}
        
        .trip-card:hover {
    transform: translateY(-2px);
    border-color: #bfdbfe;
    box-shadow: 
        0 4px 10px rgba(15, 23, 42, 0.08),
        0 12px 28px rgba(59, 130, 246, 0.08);
}

        .trip-card > div:first-child {
    padding-bottom: 2px;
}
.trip-card .trip-number {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
        
        .card-divider {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 16px 0;
}
        
        .card-divider-light {
    border: none;
    border-top: 1px solid #eef2f7;
    margin: 14px 0;
}
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid transparent;
        }
        
        .status-planned {
            background: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }

        .status-ongoing {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-completed {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .status-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        
        .priority-high {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .priority-medium {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .priority-low {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }
        
        .status-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-right: 3px;
        }

        .status-dot-planned {
            background: #6366f1;
        }

        .status-dot-ongoing {
            background: #3b82f6;
        }

        .status-dot-completed {
            background: #10b981;
        }

        .status-dot-cancelled {
            background: #ef4444;
        }
        
        .info-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        
        .route-box {
            background: linear-gradient(
                135deg,
                #eff6ff 0%,
                #f8fafc 100%
            );
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid #bfdbfe;
            box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.04);
        }


        /* Route content */

        .route-simple {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .route-simple .from,
        .route-simple .to {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* Arrow */

        .route-simple .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .route-simple .arrow i {
            color: #3b82f6;
        }
        
        .doc-thumb {
    width: 48px;
    height: 48px;
    border-radius: 9px;
    background: #f8fafc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dbe2ea;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 17px;
    color: #64748b;
    overflow: hidden;
}

.doc-thumb:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);
}

.doc-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
        
        .filter-btn {
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid transparent;
            background: #f1f5f9;
            color: #64748b;
        }
        .filter-btn:hover { background: #e5e9f0; }
        .filter-btn.active {
            background: #3b82f6;
            color: white;
        }
        .filter-btn .count {
            display: inline-block;
            margin-left: 4px;
            padding: 0 6px;
            border-radius: 9999px;
            font-size: 0.6rem;
            background: rgba(255,255,255,0.2);
        }
        .filter-btn.active .count { background: rgba(255,255,255,0.25); }
        
        .dropdown-select {
    border: 1px solid #dbe2ea;
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    cursor: pointer;
    padding: 7px 10px;
    border-radius: 8px;
    outline: none;
    transition: all 0.2s ease;
}

.dropdown-select:hover {
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
}
        .track-btn {
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 7px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .track-btn:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.25);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .info-grid > div {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            min-height: 65px;
            transition: all 0.2s ease;
        }

        .info-grid > div:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .notes-text {
    color: #475569;
    font-size: 0.8rem;
    font-weight: 500;
    max-width: 350px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
        
        .doc-count-badge {
    font-size: 0.65rem;
    font-weight: 700;
    color: #1e40af;
    background: #dbeafe;
    border: 1px solid #bfdbfe;
    padding: 2px 8px;
    border-radius: 9999px;
}
        
        @media (max-width: 768px) {

    .info-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .route-simple {
        flex-wrap: wrap;
    }

    .route-simple .from,
    .route-simple .to {
        max-width: 120px;
        font-size: 0.8rem;
    }

    .route-simple .track-btn {
        width: 100%;
        justify-content: center;
        margin-top: 6px;
    }
}
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Trips</h2>
                <p class="text-gray-500 text-sm">Manage and track all trips</p>
            </div>
            <button onclick="openModal('addTripModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition flex items-center gap-2 text-sm font-medium shadow-sm shadow-blue-100">
                <i class="ri-add-line"></i> New Trip
            </button>
        </div>
        
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-500 mr-1">Status:</span>
                <a href="?status=all<?php echo $search_filter ? '&search='.urlencode($search_filter) : ''; ?><?php echo $priority_filter != 'all' ? '&priority='.urlencode($priority_filter) : ''; ?>" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $totalTrips; ?></span>
                </a>
                <a href="?status=planned<?php echo $search_filter ? '&search='.urlencode($search_filter) : ''; ?><?php echo $priority_filter != 'all' ? '&priority='.urlencode($priority_filter) : ''; ?>" class="filter-btn <?php echo $status_filter == 'planned' ? 'active' : ''; ?>">
                    <span class="status-dot status-dot-planned"></span> Planned <span class="count"><?php echo $statusCounts['planned'] ?? 0; ?></span>
                </a>
                <a href="?status=ongoing<?php echo $search_filter ? '&search='.urlencode($search_filter) : ''; ?><?php echo $priority_filter != 'all' ? '&priority='.urlencode($priority_filter) : ''; ?>" class="filter-btn <?php echo $status_filter == 'ongoing' ? 'active' : ''; ?>">
                    <span class="status-dot status-dot-ongoing"></span> Ongoing <span class="count"><?php echo $statusCounts['ongoing'] ?? 0; ?></span>
                </a>
                <a href="?status=completed<?php echo $search_filter ? '&search='.urlencode($search_filter) : ''; ?><?php echo $priority_filter != 'all' ? '&priority='.urlencode($priority_filter) : ''; ?>" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    <span class="status-dot status-dot-completed"></span> Completed <span class="count"><?php echo $statusCounts['completed'] ?? 0; ?></span>
                </a>
                <a href="?status=cancelled<?php echo $search_filter ? '&search='.urlencode($search_filter) : ''; ?><?php echo $priority_filter != 'all' ? '&priority='.urlencode($priority_filter) : ''; ?>" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <span class="status-dot status-dot-cancelled"></span> Cancelled <span class="count"><?php echo $statusCounts['cancelled'] ?? 0; ?></span>
                </a>
                
                <span class="text-sm font-medium text-gray-500 ml-3 mr-1">Priority:</span>
                <select name="priority" class="px-3 py-1.5 border rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500" onchange="window.location.href='?status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>&priority='+this.value">
                    <option value="all" <?php echo $priority_filter == 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
                
                <div class="flex-1"></div>
                
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="hidden" name="priority" value="<?php echo $priority_filter; ?>">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Search trips..." class="pl-8 pr-3 py-1.5 border rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 w-44">
                        <i class="ri-search-line absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">Go</button>
                    <?php if($status_filter != 'all' || $priority_filter != 'all' || !empty($search_filter)): ?>
                    <a href="trips.php" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Trip Cards -->
        <div class="space-y-3">
            <?php if(empty($filtered_trips)): ?>
                <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-500 border border-gray-200">
                    <i class="ri-truck-line text-5xl mb-3 block text-gray-300"></i>
                    <p class="text-lg font-medium">No trips found</p>
                    <p class="text-sm text-gray-400">Try adjusting your filters or create a new trip</p>
                </div>
            <?php else: ?>
                <?php foreach($filtered_trips as $trip): 
                    $hasDocuments = isset($docMap[$trip['id']]);
                    $docList = $hasDocuments ? $docMap[$trip['id']] : [];
                ?>
                <div class="trip-card p-5">
                    <!-- Header Row -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="trip-number"><?php echo htmlspecialchars($trip['trip_number']); ?></span>
                        
                        <span class="status-badge status-<?php echo $trip['status']; ?>">
                            <span class="status-dot status-dot-<?php echo $trip['status']; ?>"></span>
                            <?php echo ucfirst($trip['status']); ?>
                        </span>
                        
                        <span class="status-badge priority-<?php echo $trip['priority']; ?>">
                            <?php echo ucfirst($trip['priority']); ?>
                        </span>
                        
                        <div class="flex-1"></div>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $trip['id']; ?>">
                            <select name="status" onchange="this.form.submit()" class="dropdown-select status-<?php echo $trip['status']; ?>" style="color: <?php 
                                echo $trip['status'] == 'planned' ? '#3730a3' : 
                                    ($trip['status'] == 'ongoing' ? '#1e40af' : 
                                    ($trip['status'] == 'completed' ? '#065f46' : '#991b1b')); 
                            ?>;">
                                <option value="planned" <?php echo $trip['status'] == 'planned' ? 'selected' : ''; ?>>Planned</option>
                                <option value="ongoing" <?php echo $trip['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $trip['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $trip['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <!-- Info Grid: Truck, Driver, Client -->
                    <div class="info-grid">
                        <div>
                            <div class="info-label">🚛 Truck</div>
                            <div class="info-value"><?php echo htmlspecialchars($trip['truck_plate'] ?? 'Not assigned'); ?></div>
                        </div>
                        <div>
                            <div class="info-label">👤 Driver</div>
                            <div class="info-value"><?php echo htmlspecialchars($trip['driver_name'] ?? 'Not assigned'); ?></div>
                        </div>
                        <div>
                            <div class="info-label">🏢 Client</div>
                            <div class="info-value"><?php echo htmlspecialchars($trip['client_name'] ?? 'No client'); ?></div>
                            <?php if(!empty($trip['client_phone'])): ?>
                            <div class="text-xs text-gray-500 font-medium mt-0.5"><?php echo htmlspecialchars($trip['client_phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <!-- Route Section -->
                    <div class="route-box">
                        <div class="route-simple">
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-wide mr-1">📍</span>
                            <span class="from"><?php echo htmlspecialchars($trip['start_location'] ?? 'Unknown'); ?></span>
                            <span class="arrow"><i class="ri-arrow-right-line"></i></span>
                            <span class="to"><?php echo htmlspecialchars($trip['end_location'] ?? 'Unknown'); ?></span>
                            
                            <div class="flex-1"></div>
                            
                            <span class="text-xs text-gray-500 font-medium flex items-center gap-1.5">
                                <i class="ri-time-line"></i>
                                <?php echo $trip['start_time'] ? date('d M Y, H:i', strtotime($trip['start_time'])) : 'N/A'; ?>
                            </span>
                            
                            <a href="tracking.php?truck_id=<?php echo $trip['truck_id']; ?>" class="track-btn">
                                <i class="ri-map-pin-line"></i> Track
                            </a>
                        </div>
                    </div>
                    
                    <!-- Notes & Documents -->
<?php if (!empty($trip['notes']) || $trip['status'] === 'completed'): ?>

    <hr class="card-divider-light">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

        <!-- NOTES BOX -->
        <?php if (!empty($trip['notes'])): ?>

            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-3">

                <div class="flex items-center gap-2 mb-2">
                    <span class="text-gray-500 text-xs font-bold uppercase tracking-wide">
                        📝 Notes
                    </span>
                </div>

                <div class="notes-text">
                    <?php echo htmlspecialchars($trip['notes']); ?>
                </div>

            </div>

        <?php endif; ?>


        <!-- DOCUMENTS BOX -->
        <?php if ($trip['status'] === 'completed'): ?>

            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-3">

                <div class="flex items-center gap-2 mb-2">

                    <span class="text-gray-500 text-xs font-bold uppercase tracking-wide">
                        📎 Delivery Documents
                    </span>

                    <?php if ($hasDocuments): ?>
                        <span class="doc-count-badge">
                            <?php echo count($docList); ?>
                        </span>
                    <?php endif; ?>

                </div>


                <!-- Document Files -->
                <?php if ($hasDocuments): ?>

                    <div class="flex flex-wrap items-center gap-2">

                        <?php foreach ($docList as $doc): ?>

                            <?php
                            $fileExt = strtolower(
                                pathinfo($doc['file_name'], PATHINFO_EXTENSION)
                            );

                            $isImage = in_array(
                                $fileExt,
                                ['jpg', 'jpeg', 'png', 'gif', 'webp']
                            );

                            $fileSize = round(
                                $doc['file_size'] / 1024,
                                1
                            );
                            ?>

                            <a
                                href="<?php echo htmlspecialchars($doc['file_path']); ?>"
                                target="_blank"
                                class="doc-thumb"
                                title="<?php echo htmlspecialchars($doc['file_name']); ?> (<?php echo $fileSize; ?> KB)"
                            >

                                <?php if ($isImage): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($doc['file_path']); ?>"
                                        alt="Document"
                                    />

                                <?php else: ?>

                                    <i class="ri-file-line"></i>

                                <?php endif; ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <span class="text-xs text-gray-400 font-medium">
                        No documents available
                    </span>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="mt-4 flex justify-between items-center text-sm text-gray-400">
            <span>Showing <?php echo count($filtered_trips); ?> of <?php echo count($trips); ?> trips</span>
            <span>Last updated: <?php echo date('d M Y, H:i'); ?></span>
        </div>
    </div>
</main>

<!-- Add Trip Modal -->
<div id="addTripModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Create New Trip</h3>
            <button onclick="closeModal('addTripModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Truck *</label>
                    <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Truck</option>
                        <?php foreach($trucks as $truck): ?>
                        <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number'] . ' - ' . $truck['model']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Driver *</label>
                    <select name="driver_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Driver</option>
                        <?php foreach($drivers as $driver): ?>
                        <option value="<?php echo $driver['id']; ?>"><?php echo $driver['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Route</label>
                    <select name="route_id" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Route</option>
                        <?php foreach($routes as $route): ?>
                        <option value="<?php echo $route['id']; ?>"><?php echo $route['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
                    <input type="datetime-local" name="start_time" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                    <input type="text" name="client_name" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Phone</label>
                    <input type="text" name="client_phone" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Location</label>
                    <input type="text" name="start_location" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Location</label>
                    <input type="text" name="end_location" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addTripModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Create Trip</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

// Close modal on backdrop click
document.getElementById('addTripModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
        this.classList.remove('flex');
    }
});
</script>
</body>
</html>