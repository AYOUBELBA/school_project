<?php
require_once '../includes/check_auth.php';
$db = db();

$company_id = $_SESSION['company_id'];
$user_role = $_SESSION['user_role'] ?? '';

// Get filters
$entity_type = $_GET['type'] ?? 'all';
$entity_id = $_GET['id'] ?? '';
$document_type = $_GET['doc_type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    if ($_SESSION['user_role'] !== 'owner' && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'super_admin') {
        $_SESSION['flash_error'] = "Only owners, admins, and super admins can upload documents";
        header("Location: documents.php");
        exit();
    }
    
    $uploadDir = '../uploads/documents/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $file = $_FILES['document'];
    $fileName = time() . '_' . $file['name'];
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $insertSql = "INSERT INTO documents (company_id, entity_type, entity_id, document_type, file_name, file_path, file_size, mime_type, uploaded_by, uploaded_at) 
                      VALUES (:company_id, :entity_type, :entity_id, :document_type, :file_name, :file_path, :file_size, :mime_type, :uploaded_by, CURRENT_TIMESTAMP)";
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->bindParam(':company_id', $company_id);
        $insertStmt->bindParam(':entity_type', $_POST['entity_type']);
        $insertStmt->bindParam(':entity_id', $_POST['entity_id']);
        $insertStmt->bindParam(':document_type', $_POST['document_type']);
        $insertStmt->bindParam(':file_name', $file['name']);
        $insertStmt->bindParam(':file_path', $filePath);
        $insertStmt->bindParam(':file_size', $file['size']);
        $insertStmt->bindParam(':mime_type', $file['type']);
        $insertStmt->bindParam(':uploaded_by', $_SESSION['user_id']);
        $insertStmt->execute();
        
        $_SESSION['flash_success'] = "Document uploaded successfully";
    } else {
        $_SESSION['flash_error'] = "Failed to upload document";
    }
    header("Location: documents.php");
    exit();
}

// Handle delete (only owners, admins, and super admins can delete)
if (isset($_GET['delete']) && ($user_role === 'owner' || $user_role === 'admin' || $user_role === 'super_admin')) {
    $docId = $_GET['delete'];
    
    // Get file path to delete physical file
    $getFileSql = "SELECT file_path FROM documents WHERE id = :id AND company_id = :company_id";
    $getFileStmt = $db->prepare($getFileSql);
    $getFileStmt->bindParam(':id', $docId);
    $getFileStmt->bindParam(':company_id', $company_id);
    $getFileStmt->execute();
    $file = $getFileStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file && file_exists($file['file_path'])) {
        unlink($file['file_path']); // Delete physical file
    }
    
    $delSql = "DELETE FROM documents WHERE id = :id AND company_id = :company_id";
    $delStmt = $db->prepare($delSql);
    $delStmt->bindParam(':id', $docId);
    $delStmt->bindParam(':company_id', $company_id);
    $delStmt->execute();
    
    $_SESSION['flash_success'] = "Document deleted successfully";
    header("Location: documents.php");
    exit();
}

// Build query with filters
$docSql = "SELECT d.*, u.username as uploaded_by_name 
           FROM documents d
           LEFT JOIN users u ON d.uploaded_by = u.id
           WHERE d.company_id = :company_id";
$params = [':company_id' => $company_id];

if ($entity_type !== 'all') {
    $docSql .= " AND d.entity_type = :entity_type";
    $params[':entity_type'] = $entity_type;
}

if (!empty($entity_id)) {
    $docSql .= " AND d.entity_id = :entity_id";
    $params[':entity_id'] = $entity_id;
}

if ($document_type !== 'all') {
    $docSql .= " AND d.document_type = :document_type";
    $params[':document_type'] = $document_type;
}

if (!empty($search)) {
    $docSql .= " AND (d.file_name ILIKE :search OR d.description ILIKE :search)";
    $params[':search'] = "%$search%";
}

$docSql .= " ORDER BY d.uploaded_at DESC";

$docStmt = $db->prepare($docSql);
foreach($params as $key => $value) {
    $docStmt->bindValue($key, $value);
}
$docStmt->execute();
$documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct entity types for filter
$typeSql = "SELECT DISTINCT entity_type FROM documents WHERE company_id = :company_id";
$typeStmt = $db->prepare($typeSql);
$typeStmt->bindParam(':company_id', $company_id);
$typeStmt->execute();
$entityTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get entity name for display
$entityNames = [
    'truck' => 'Truck',
    'driver' => 'Driver',
    'trip' => 'Trip',
    'fuel' => 'Fuel Record'
];

// Get trucks, drivers, and fuel records for entity ID dropdown
$trucks = [];
$drivers = [];
$fuelRecords = [];

// Get trucks
$truckSql = "SELECT id, plate_number FROM trucks WHERE company_id = :company_id AND status = 'active' ORDER BY plate_number";
$truckStmt = $db->prepare($truckSql);
$truckStmt->bindParam(':company_id', $company_id);
$truckStmt->execute();
$trucks = $truckStmt->fetchAll(PDO::FETCH_ASSOC);

// Get drivers - FIXED: using full_name instead of name
$driverSql = "SELECT id, full_name FROM drivers WHERE company_id = :company_id AND status = 'available' ORDER BY full_name";
$driverStmt = $db->prepare($driverSql);
$driverStmt->bindParam(':company_id', $company_id);
$driverStmt->execute();
$drivers = $driverStmt->fetchAll(PDO::FETCH_ASSOC);

// Get fuel records
$fuelSql = "SELECT f.id, t.plate_number, f.invoice_number, f.fuel_date 
            FROM fuel_records f 
            JOIN trucks t ON f.truck_id = t.id 
            WHERE t.company_id = :company_id 
            ORDER BY f.fuel_date DESC LIMIT 50";
$fuelStmt = $db->prepare($fuelSql);
$fuelStmt->bindParam(':company_id', $company_id);
$fuelStmt->execute();
$fuelRecords = $fuelStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .entity-badge {
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        .entity-badge-fuel { background: #fef3c7; color: #92400e; }
        .entity-badge-truck { background: #dbeafe; color: #1e40af; }
        .entity-badge-driver { background: #d1fae5; color: #065f46; }
        .entity-badge-trip { background: #e0e7ff; color: #3730a3; }
        .entity-badge-other { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Document Management</h2>
                <p class="text-gray-500">Upload and manage documents for trucks, drivers, trips, and fuel records</p>
            </div>
            <?php if($user_role === 'owner' || $user_role === 'admin' || $user_role === 'super_admin'): ?>
            <button onclick="openModal('uploadModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-upload-line"></i> Upload Document
            </button>
            <?php endif; ?>
        </div>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
            <i class="ri-checkbox-circle-line text-xl"></i>
            <?php echo $flash_success; ?>
        </div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2">
            <i class="ri-error-warning-line text-xl"></i>
            <?php echo $flash_error; ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Filter Buttons -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-600 mr-2"><i class="ri-filter-line"></i> Quick Filters:</span>
                <a href="documents.php?type=all" class="px-3 py-1.5 rounded-full text-sm transition <?php echo $entity_type == 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    All Documents
                </a>
                <a href="documents.php?type=fuel&doc_type=receipt" class="px-3 py-1.5 rounded-full text-sm transition <?php echo $entity_type == 'fuel' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <i class="ri-gas-station-line"></i> Fuel Receipts
                </a>
                <a href="documents.php?type=truck" class="px-3 py-1.5 rounded-full text-sm transition <?php echo $entity_type == 'truck' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <i class="ri-truck-line"></i> Trucks
                </a>
                <a href="documents.php?type=driver" class="px-3 py-1.5 rounded-full text-sm transition <?php echo $entity_type == 'driver' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <i class="ri-user-line"></i> Drivers
                </a>
                <a href="documents.php?type=trip" class="px-3 py-1.5 rounded-full text-sm transition <?php echo $entity_type == 'trip' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <i class="ri-route-line"></i> Trips
                </a>
            </div>
        </div>
        
        <!-- Advanced Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                    <select name="type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="all" <?php echo $entity_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach($entityTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $entity_type == $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type) . ' Records'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity ID / Reference</label>
                    <select name="id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All IDs</option>
                        <?php if($entity_type === 'truck' || $entity_type === 'all'): ?>
                            <?php foreach($trucks as $truck): ?>
                            <option value="<?php echo $truck['id']; ?>" <?php echo $entity_id == $truck['id'] ? 'selected' : ''; ?>>
                                Truck #<?php echo $truck['id']; ?> - <?php echo $truck['plate_number']; ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if($entity_type === 'driver' || $entity_type === 'all'): ?>
                            <?php foreach($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>" <?php echo $entity_id == $driver['id'] ? 'selected' : ''; ?>>
                                Driver #<?php echo $driver['id']; ?> - <?php echo $driver['full_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if($entity_type === 'fuel' || $entity_type === 'all'): ?>
                            <?php foreach($fuelRecords as $fuel): ?>
                            <option value="<?php echo $fuel['id']; ?>" <?php echo $entity_id == $fuel['id'] ? 'selected' : ''; ?>>
                                Fuel #<?php echo $fuel['id']; ?> - <?php echo $fuel['plate_number']; ?> (<?php echo date('Y-m-d', strtotime($fuel['fuel_date'])); ?>)
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                    <select name="doc_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $document_type == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="license" <?php echo $document_type == 'license' ? 'selected' : ''; ?>>License</option>
                        <option value="insurance" <?php echo $document_type == 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                        <option value="receipt" <?php echo $document_type == 'receipt' ? 'selected' : ''; ?>>Receipt</option>
                        <option value="registration" <?php echo $document_type == 'registration' ? 'selected' : ''; ?>>Registration</option>
                        <option value="invoice" <?php echo $document_type == 'invoice' ? 'selected' : ''; ?>>Invoice</option>
                        <option value="other" <?php echo $document_type == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search files..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 w-full transition flex items-center justify-center gap-2">
                        <i class="ri-search-line"></i> Filter
                    </button>
                    <a href="documents.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 w-full text-center transition">
                        <i class="ri-refresh-line"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Document List -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">
                    <i class="ri-file-line mr-2"></i> Documents
                    <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($documents); ?> files)</span>
                </h3>
                <div class="flex items-center gap-4">
                    <?php if($user_role === 'owner' || $user_role === 'admin' || $user_role === 'super_admin'): ?>
                    <span class="text-xs text-gray-500"><i class="ri-lock-line"></i> You can manage all documents</span>
                    <?php else: ?>
                    <span class="text-xs text-gray-500"><i class="ri-eye-line"></i> View only</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if(empty($documents)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="ri-folder-line text-5xl mb-3 block"></i>
                <p class="text-lg font-medium">No documents found</p>
                <p class="text-sm mt-1">Try adjusting your search criteria or upload a new document</p>
                <?php if($user_role === 'owner' || $user_role === 'admin' || $user_role === 'super_admin'): ?>
                <button onclick="openModal('uploadModal')" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                    <i class="ri-upload-line"></i> Upload Document
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach($documents as $doc): 
                    // Get additional info for fuel records
                    $additionalInfo = '';
                    if($doc['entity_type'] === 'fuel') {
                        $infoSql = "SELECT t.plate_number, f.invoice_number, f.fuel_date 
                                   FROM fuel_records f 
                                   JOIN trucks t ON f.truck_id = t.id 
                                   WHERE f.id = :fuel_id";
                        $infoStmt = $db->prepare($infoSql);
                        $infoStmt->bindParam(':fuel_id', $doc['entity_id']);
                        $infoStmt->execute();
                        $fuelInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
                        if($fuelInfo) {
                            $additionalInfo = 'Truck: ' . $fuelInfo['plate_number'] . ' | Invoice: ' . $fuelInfo['invoice_number'] . ' | Date: ' . date('Y-m-d', strtotime($fuelInfo['fuel_date']));
                        }
                    }
                ?>
                <div class="p-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 <?php 
                            echo $doc['document_type'] === 'invoice' ? 'bg-green-100' : 
                                ($doc['document_type'] === 'license' ? 'bg-blue-100' : 
                                ($doc['document_type'] === 'insurance' ? 'bg-purple-100' : 
                                ($doc['document_type'] === 'receipt' ? 'bg-orange-100' : 'bg-gray-100'))); ?>">
                            <i class="ri-file-text-line <?php 
                                echo $doc['document_type'] === 'invoice' ? 'text-green-600' : 
                                    ($doc['document_type'] === 'license' ? 'text-blue-600' : 
                                    ($doc['document_type'] === 'insurance' ? 'text-purple-600' : 
                                    ($doc['document_type'] === 'receipt' ? 'text-orange-600' : 'text-gray-600'))); ?> text-xl">
                            </i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                <span class="entity-badge <?php 
                                    echo 'entity-badge-' . ($doc['entity_type'] === 'fuel' ? 'fuel' : 
                                        ($doc['entity_type'] === 'truck' ? 'truck' : 
                                        ($doc['entity_type'] === 'driver' ? 'driver' : 
                                        ($doc['entity_type'] === 'trip' ? 'trip' : 'other')))); ?>">
                                    <?php echo ucfirst($doc['entity_type']); ?>
                                </span>
                                <span class="text-xs text-gray-400">#<?php echo $doc['entity_id']; ?></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span class="capitalize font-medium"><?php echo $doc['document_type']; ?></span> • 
                                <?php echo $entityNames[$doc['entity_type']] ?? ucfirst($doc['entity_type']); ?>
                                <?php if($additionalInfo): ?>
                                • <span class="text-gray-600"><?php echo $additionalInfo; ?></span>
                                <?php endif; ?>
                                • <?php echo round($doc['file_size'] / 1024, 1); ?> KB • 
                                Uploaded <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>
                                <?php if($doc['uploaded_by_name']): ?>
                                by <span class="font-medium text-gray-600"><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-1 flex-shrink-0 ml-4">
                        <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50 transition" title="View">
                            <i class="ri-eye-line"></i>
                        </a>
                        <a href="<?php echo $doc['file_path']; ?>" download class="text-green-600 hover:text-green-800 px-2 py-1 rounded hover:bg-green-50 transition" title="Download">
                            <i class="ri-download-line"></i>
                        </a>
                        <?php if($user_role === 'owner' || $user_role === 'admin' || $user_role === 'super_admin'): ?>
                        <a href="?delete=<?php echo $doc['id']; ?>" onclick="return confirm('Delete this document? This action cannot be undone.')" class="text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50 transition" title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Document count summary -->
            <div class="p-3 bg-gray-50 border-t text-sm text-gray-500">
                Showing <?php echo count($documents); ?> document(s)
                <?php if($entity_type !== 'all'): ?>
                for <span class="font-medium"><?php echo ucfirst($entity_type); ?></span>
                <?php endif; ?>
                <?php if(!empty($entity_id)): ?>
                with ID <span class="font-medium">#<?php echo htmlspecialchars($entity_id); ?></span>
                <?php endif; ?>
                <?php if($document_type !== 'all'): ?>
                of type <span class="font-medium"><?php echo ucfirst($document_type); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="ri-upload-line text-blue-600"></i> Upload Document
            </h3>
            <button onclick="closeModal('uploadModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                    <select name="entity_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="truck">Truck</option>
                        <option value="driver">Driver</option>
                        <option value="trip">Trip</option>
                        <option value="fuel">Fuel Record</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity ID</label>
                    <input type="number" name="entity_id" placeholder="Enter ID number" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Enter the ID of the truck, driver, trip, or fuel record</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                    <select name="document_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="license">License</option>
                        <option value="insurance">Insurance</option>
                        <option value="receipt">Receipt</option>
                        <option value="registration">Registration</option>
                        <option value="invoice">Invoice</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">File</label>
                    <input type="file" name="document" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Supported: PDF, JPG, PNG, DOC, XLS</p>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <i class="ri-upload-line"></i> Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Auto-submit form when entity type changes
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            this.closest('form').submit();
        });
    }
});
</script>
</body>
</html>