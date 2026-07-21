<?php
// api/upload_document.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

error_log('=== UPLOAD DEBUG ===');
error_log('POST: ' . print_r($_POST, true));
error_log('FILES: ' . print_r($_FILES, true));
error_log('SESSION: ' . print_r($_SESSION, true));
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication - support both session and token
$user_id = null;
$company_id = null;

// Check session first
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $company_id = $_SESSION['company_id'] ?? 0;
} else {
    // Check Authorization header for token
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        
        // You can validate the token here
        // For now, let's try to find the user by token (if you store tokens)
        // Or you can just accept the token as is for now
        // For simplicity, let's check if the token exists in a session table or just accept it
        
        // Since we don't have a token system yet, let's try to get user from session
        // But since session is empty, we'll return unauthorized
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - No session or token found']);
        exit();
    }
}

// If we get here, we have a valid user
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Invalid user']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['document'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded - $_FILES["document"] not set']);
    exit();
}

if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Upload error code: ' . $_FILES['document']['error'];
    switch ($_FILES['document']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_msg = 'File exceeds upload_max_filesize directive in php.ini';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_msg = 'File exceeds MAX_FILE_SIZE directive in HTML form';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_msg = 'File was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_msg = 'No file was uploaded';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_msg = 'Missing temporary folder';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_msg = 'Failed to write file to disk';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_msg = 'File upload stopped by extension';
            break;
    }
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit();
}

// Get POST data
$entity_type = isset($_POST['entity_type']) ? $_POST['entity_type'] : '';
$entity_id = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : 0;
$document_type = isset($_POST['document_type']) ? $_POST['document_type'] : 'other';

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
    
    $file = $_FILES['document'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    
    if (!in_array($fileExt, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)]);
        exit();
    }
    
    $fileName = time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
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
    $stmt->bindParam(':file_name', $file['name']);
    $stmt->bindParam(':file_path', $filePath);
    $stmt->bindParam(':file_size', $file['size'], PDO::PARAM_INT);
    $stmt->bindParam(':mime_type', $file['type']);
    $stmt->bindParam(':uploaded_by', $user_id, PDO::PARAM_INT);
    
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
                'file_name' => $file['name'],
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'mime_type' => $file['type']
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