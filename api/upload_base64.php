<?php
// api/upload_base64.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit();
}

// Get JSON input - with better error handling
$json = file_get_contents('php://input');
if (empty($json)) {
    echo json_encode(['success' => false, 'message' => 'No JSON data received']);
    exit();
}

$data = json_decode($json, true);
if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit();
}

$image_base64 = isset($data['image']) ? $data['image'] : '';
$filename = isset($data['filename']) ? $data['filename'] : 'document.jpg';
$entity_type = isset($data['entity_type']) ? $data['entity_type'] : '';
$entity_id = isset($data['entity_id']) ? (int)$data['entity_id'] : 0;
$document_type = isset($data['document_type']) ? $data['document_type'] : 'other';
$company_id = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : 0;
$uploaded_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (empty($image_base64)) {
    echo json_encode(['success' => false, 'message' => 'No image data provided']);
    exit();
}

if (empty($entity_type) || $entity_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid entity type or ID']);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Create upload directory
    $uploadDir = '../uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate filename
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (empty($fileExt)) {
        $fileExt = 'jpg';
    }
    $newFilename = time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = $uploadDir . $newFilename;
    
    // Decode base64 and save file
    $imageData = base64_decode($image_base64);
    if ($imageData === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid base64 data']);
        exit();
    }
    
    // Save the file
    if (file_put_contents($filePath, $imageData) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
    }
    
    // Get file size
    $fileSize = filesize($filePath);
    
    // Determine mime type
    $mime_type = 'image/' . $fileExt;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filePath);
        finfo_close($finfo);
    }
    
    // Insert into documents table
    $sql = "INSERT INTO documents (
                company_id, 
                entity_type, 
                entity_id, 
                document_type, 
                file_name, 
                file_path, 
                file_size, 
                mime_type, 
                uploaded_by, 
                uploaded_at
            ) VALUES (
                :company_id, 
                :entity_type, 
                :entity_id, 
                :document_type, 
                :file_name, 
                :file_path, 
                :file_size, 
                :mime_type, 
                :uploaded_by, 
                CURRENT_TIMESTAMP
            ) RETURNING id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':entity_type', $entity_type);
    $stmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);
    $stmt->bindParam(':document_type', $document_type);
    $stmt->bindParam(':file_name', $filename);
    $stmt->bindParam(':file_path', $filePath);
    $stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
    $stmt->bindParam(':mime_type', $mime_type);
    $stmt->bindParam(':uploaded_by', $uploaded_by, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $docId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        // If entity is fuel, also update fuel_records with receipt_image
        if ($entity_type === 'fuel') {
            $updateFuelSql = "UPDATE fuel_records SET receipt_image = :file_path WHERE id = :entity_id";
            $updateStmt = $db->prepare($updateFuelSql);
            $updateStmt->bindParam(':file_path', $filePath);
            $updateStmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);
            $updateStmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'file' => [
                'id' => $docId,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize
            ]
        ]);
    } else {
        // Delete the file if database insert failed
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => false, 'message' => 'Failed to save document record']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>