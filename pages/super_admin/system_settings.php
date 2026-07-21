<?php
// pages/super_admin/system_settings.php
require_once '../../includes/check_auth.php';

// Only super_admin can access this page
if ($_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../dashboard.php');
    exit();
}

// Load maintenance config
$maintenanceConfigFile = '../../config/maintenance.php';
if (file_exists($maintenanceConfigFile)) {
    $maintenanceConfig = include $maintenanceConfigFile;
} else {
    $maintenanceConfig = [
        'enabled' => false,
        'message' => 'System is currently under maintenance. Please check back later.',
        'allowed_ips' => ['127.0.0.1'],
        'allowed_roles' => ['super_admin', 'owner'],
        'estimated_end' => null
    ];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Clear Cache
        if ($_POST['action'] === 'clear_cache') {
            // Clear template cache
            $cacheDirs = ['../../cache/', '../../compiled/', '../../tmp/'];
            $clearedCount = 0;
            
            foreach ($cacheDirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file) && !str_contains($file, '.htaccess')) {
                            if (unlink($file)) {
                                $clearedCount++;
                            }
                        }
                    }
                }
            }
            
            // Clear OPcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Clear APC cache
            if (function_exists('apc_clear_cache')) {
                apc_clear_cache();
            }
            
            // Clear session cache
            unset($_SESSION['cache']);
            unset($_SESSION['templates']);
            
            $_SESSION['flash_success'] = "All cache cleared successfully! ($clearedCount files removed)";
            header("Location: system_settings.php");
            exit();
        }
        
        // Maintenance Mode
        if ($_POST['action'] === 'maintenance_mode') {
            $mode = $_POST['maintenance_mode'] ?? 'off';
            $message = $_POST['maintenance_message'] ?? 'System is currently under maintenance. Please check back later.';
            $estimated_end = $_POST['estimated_end'] ?? null;
            
            // Update config
            $config = [
                'enabled' => ($mode === 'on'),
                'message' => $message,
                'allowed_ips' => ['127.0.0.1', '::1'],
                'allowed_roles' => ['super_admin', 'owner'],
                'started_at' => ($mode === 'on') ? date('Y-m-d H:i:s') : null,
                'estimated_end' => $estimated_end
            ];
            
            // Save config
            $content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
            if (file_put_contents($maintenanceConfigFile, $content)) {
                $_SESSION['flash_success'] = "Maintenance mode " . ($mode === 'on' ? 'enabled' : 'disabled');
            } else {
                $_SESSION['flash_error'] = "Failed to update maintenance settings. Check file permissions.";
            }
            
            header("Location: system_settings.php");
            exit();
        }
        
        // ============================================================
        // DATABASE BACKUP - PHP Method (No external commands)
        // ============================================================
        if ($_POST['action'] === 'backup_database') {
            try {
                $backupDir = '../../backups/';
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0777, true);
                }
                
                $date = date('Y-m-d_H-i-s');
                $backupFile = $backupDir . "backup_{$date}.sql";
                
                // Get database connection
                $database = new Database();
                $db = $database->getConnection();
                
                // Get all tables
                $tablesSql = "SELECT table_name FROM information_schema.tables 
                              WHERE table_schema = 'public' 
                              AND table_type = 'BASE TABLE'
                              ORDER BY table_name";
                $tablesStmt = $db->prepare($tablesSql);
                $tablesStmt->execute();
                $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Check if schema_only
                $schemaOnly = isset($_POST['schema_only']) && $_POST['schema_only'] === 'on';
                
                // Start building backup content
                $backupContent = "-- ============================================================\n";
                $backupContent .= "-- DATABASE BACKUP - " . date('Y-m-d H:i:s') . "\n";
                $backupContent .= "-- Database: truck_fleet_db\n";
                $backupContent .= "-- Schema only: " . ($schemaOnly ? 'YES' : 'NO') . "\n";
                $backupContent .= "-- ============================================================\n\n";
                
                // Disable triggers for clean restore
                $backupContent .= "SET session_replication_role = 'replica';\n\n";
                
                foreach ($tables as $table) {
                    // Get table structure
                    $structureSql = "SELECT 
                                        column_name, 
                                        data_type, 
                                        is_nullable, 
                                        column_default,
                                        character_maximum_length
                                    FROM information_schema.columns 
                                    WHERE table_name = :table 
                                    AND table_schema = 'public'
                                    ORDER BY ordinal_position";
                    $structStmt = $db->prepare($structureSql);
                    $structStmt->bindParam(':table', $table);
                    $structStmt->execute();
                    $columns = $structStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Build CREATE TABLE statement
                    $backupContent .= "-- Table: {$table}\n";
                    $backupContent .= "DROP TABLE IF EXISTS {$table} CASCADE;\n";
                    $backupContent .= "CREATE TABLE {$table} (\n";
                    
                    $colDefs = [];
                    $primaryKeys = [];
                    foreach ($columns as $col) {
                        $def = "    {$col['column_name']} {$col['data_type']}";
                        if ($col['character_maximum_length'] && in_array($col['data_type'], ['character varying', 'varchar'])) {
                            $def .= "({$col['character_maximum_length']})";
                        }
                        if ($col['is_nullable'] === 'NO') {
                            $def .= " NOT NULL";
                        }
                        if ($col['column_default']) {
                            $def .= " DEFAULT {$col['column_default']}";
                        }
                        $colDefs[] = $def;
                        
                        // Check for primary key (simplified)
                        if (strpos($col['column_default'] ?? '', 'nextval') !== false) {
                            $primaryKeys[] = $col['column_name'];
                        }
                    }
                    
                    $backupContent .= implode(",\n", $colDefs);
                    
                    if (!empty($primaryKeys)) {
                        $backupContent .= ",\n    PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
                    }
                    
                    $backupContent .= "\n);\n\n";
                    
                    // If not schema only, get data
                    if (!$schemaOnly) {
                        $dataSql = "SELECT * FROM {$table}";
                        $dataStmt = $db->prepare($dataSql);
                        $dataStmt->execute();
                        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($rows)) {
                            $backupContent .= "-- Data for {$table}\n";
                            foreach ($rows as $row) {
                                $columns_str = implode(', ', array_keys($row));
                                $values = [];
                                foreach ($row as $key => $value) {
                                    if ($value === null) {
                                        $values[] = 'NULL';
                                    } else {
                                        // Escape and quote strings
                                        if (is_numeric($value) && !is_string($value)) {
                                            $values[] = $value;
                                        } else {
                                            $escaped = str_replace("'", "''", $value);
                                            $values[] = "'" . $escaped . "'";
                                        }
                                    }
                                }
                                $values_str = implode(', ', $values);
                                $backupContent .= "INSERT INTO {$table} ({$columns_str}) VALUES ({$values_str});\n";
                            }
                            $backupContent .= "\n";
                        }
                    }
                    
                    // Add sequence reset
                    $seqSql = "SELECT pg_get_serial_sequence('{$table}', 'id') as seq_name";
                    $seqStmt = $db->prepare($seqSql);
                    $seqStmt->execute();
                    $seq = $seqStmt->fetch(PDO::FETCH_ASSOC);
                    if ($seq && $seq['seq_name']) {
                        $backupContent .= "SELECT setval('{$seq['seq_name']}', COALESCE((SELECT MAX(id) FROM {$table}), 1), true);\n\n";
                    }
                }
                
                // Re-enable triggers
                $backupContent .= "SET session_replication_role = 'origin';\n\n";
                $backupContent .= "-- ============================================================\n";
                $backupContent .= "-- BACKUP COMPLETED AT " . date('Y-m-d H:i:s') . "\n";
                $backupContent .= "-- ============================================================\n";
                
                // Save to file
                if (file_put_contents($backupFile, $backupContent)) {
                    // Create backup info
                    $backupInfo = [
                        'filename' => basename($backupFile),
                        'filepath' => $backupFile,
                        'size' => filesize($backupFile),
                        'created_at' => date('Y-m-d H:i:s'),
                        'schema_only' => $schemaOnly
                    ];
                    
                    $_SESSION['last_backup'] = $backupInfo;
                    $_SESSION['flash_success'] = "Database backup created successfully! File: " . basename($backupFile) . " (" . number_format(filesize($backupFile) / 1024, 1) . " KB)";
                } else {
                    $_SESSION['flash_error'] = "Failed to write backup file. Check folder permissions.";
                }
                
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Backup error: " . $e->getMessage();
            }
            
            header("Location: system_settings.php");
            exit();
        }
        
        // ============================================================
        // DATABASE RESTORE
        // ============================================================
        if ($_POST['action'] === 'restore_database') {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = "Please select a valid backup file to restore.";
                header("Location: system_settings.php");
                exit();
            }
            
            try {
                $uploadedFile = $_FILES['backup_file'];
                $backupDir = '../../backups/';
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0777, true);
                }
                
                $restoreFile = $backupDir . 'restore_' . time() . '.sql';
                
                // Move uploaded file
                if (!move_uploaded_file($uploadedFile['tmp_name'], $restoreFile)) {
                    $_SESSION['flash_error'] = "Failed to upload backup file.";
                    header("Location: system_settings.php");
                    exit();
                }
                
                // Read the backup file content
                $sqlContent = file_get_contents($restoreFile);
                
                if (empty($sqlContent)) {
                    $_SESSION['flash_error'] = "Backup file is empty or could not be read.";
                    header("Location: system_settings.php");
                    exit();
                }
                
                // Get database connection
                $database = new Database();
                $db = $database->getConnection();
                
                // Split SQL into individual statements
                $statements = explode(";\n", $sqlContent);
                $executed = 0;
                $errors = [];
                
                // Begin transaction
                $db->beginTransaction();
                
                try {
                    foreach ($statements as $sql) {
                        $sql = trim($sql);
                        if (!empty($sql) && !str_starts_with($sql, '--')) {
                            try {
                                $db->exec($sql);
                                $executed++;
                            } catch (PDOException $e) {
                                $errors[] = "Error: " . $e->getMessage() . "\nSQL: " . substr($sql, 0, 200);
                            }
                        }
                    }
                    
                    if (empty($errors)) {
                        $db->commit();
                        $_SESSION['flash_success'] = "Database restored successfully! $executed statements executed.";
                    } else {
                        $db->rollBack();
                        $_SESSION['flash_error'] = "Restore failed with errors:\n" . implode("\n", array_slice($errors, 0, 5));
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['flash_error'] = "Restore error: " . $e->getMessage();
                }
                
                // Clean up restore file
                if (file_exists($restoreFile)) {
                    unlink($restoreFile);
                }
                
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Restore error: " . $e->getMessage();
            }
            
            header("Location: system_settings.php");
            exit();
        }
        
        // ============================================================
        // DOWNLOAD BACKUP
        // ============================================================
        if ($_POST['action'] === 'download_backup') {
            $backupFile = $_POST['backup_file'] ?? '';
            $backupPath = '../../backups/' . $backupFile;
            
            if (empty($backupFile) || !file_exists($backupPath)) {
                $_SESSION['flash_error'] = "Backup file not found.";
                header("Location: system_settings.php");
                exit();
            }
            
            // Force download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $backupFile . '"');
            header('Content-Length: ' . filesize($backupPath));
            readfile($backupPath);
            exit();
        }
        
        // ============================================================
        // DELETE BACKUP
        // ============================================================
        if ($_POST['action'] === 'delete_backup') {
            $backupFile = $_POST['backup_file'] ?? '';
            $backupPath = '../../backups/' . $backupFile;
            
            if (!empty($backupFile) && file_exists($backupPath)) {
                if (unlink($backupPath)) {
                    $_SESSION['flash_success'] = "Backup file deleted successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to delete backup file.";
                }
            } else {
                $_SESSION['flash_error'] = "Backup file not found.";
            }
            
            header("Location: system_settings.php");
            exit();
        }
    }
}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get current maintenance status for display
$maintenance_status = ($maintenanceConfig['enabled'] ?? false) ? 'Enabled' : 'Disabled';
$maintenance_badge_color = ($maintenanceConfig['enabled'] ?? false) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';

// Get list of backup files
$backupDir = '../../backups/';
$backupFiles = [];
if (is_dir($backupDir)) {
    $files = glob($backupDir . '*.sql');
    foreach ($files as $file) {
        // Skip restore files
        if (strpos(basename($file), 'restore_') === 0) {
            continue;
        }
        $backupFiles[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'modified' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    // Sort by date descending (newest first)
    usort($backupFiles, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
}
?>

<!-- Rest of the HTML remains the same as before -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Super Admin</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
            <p class="text-gray-500">Manage system-wide configurations and maintenance</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Maintenance:</span>
            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $maintenance_badge_color; ?>">
                <?php echo $maintenance_status; ?>
            </span>
        </div>
    </div>
    
    <?php if($flash_success): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
        <i class="ri-checkbox-circle-line text-xl"></i>
        <?php echo nl2br(htmlspecialchars($flash_success)); ?>
    </div>
    <?php endif; ?>
    
    <?php if($flash_error): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2">
        <i class="ri-error-warning-line text-xl"></i>
        <?php echo nl2br(htmlspecialchars($flash_error)); ?>
    </div>
    <?php endif; ?>
    
    <!-- Maintenance Mode -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-tools-line text-yellow-600"></i>
            Maintenance Mode
        </h3>
        <p class="text-sm text-gray-500 mb-4">When enabled, all users except admins will see the maintenance page.</p>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="maintenance_mode">
            
            <div class="flex items-center gap-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="on" 
                           <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'checked' : ''; ?>
                           class="sr-only peer" onchange="toggleMaintenanceFields(this.checked)">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer 
                                peer-checked:after:translate-x-full peer-checked:after:border-white 
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                after:bg-white after:border-gray-300 after:border after:rounded-full 
                                after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'Maintenance is ENABLED' : 'Maintenance is DISABLED'; ?>
                    </span>
                </label>
            </div>
            
            <div id="maintenanceFields" class="<?php echo ($maintenanceConfig['enabled'] ?? false) ? '' : 'hidden'; ?> space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Message</label>
                    <textarea name="maintenance_message" rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ><?php echo htmlspecialchars($maintenanceConfig['message'] ?? 'System is currently under maintenance. Please check back later.'); ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Completion</label>
                    <input type="datetime-local" name="estimated_end" 
                           value="<?php echo $maintenanceConfig['estimated_end'] ?? ''; ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="text-xs text-gray-500 bg-blue-50 p-3 rounded-lg">
                    <i class="ri-information-line"></i>
                    <strong>Who can bypass maintenance:</strong>
                    <ul class="list-disc list-inside mt-1">
                        <li>Super Admin (you)</li>
                        <li>Company Owners</li>
                        <li>Localhost (127.0.0.1)</li>
                    </ul>
                </div>
            </div>
            
            <button type="submit" class="px-4 py-2 <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'; ?> text-white rounded-lg transition flex items-center gap-2">
                <i class="ri-<?php echo ($maintenanceConfig['enabled'] ?? false) ? 'close-circle' : 'check-circle'; ?>-line"></i>
                <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'Disable' : 'Enable'; ?> Maintenance
            </button>
        </form>
    </div>
    
    <!-- Cache Management -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-refresh-line text-purple-600"></i>
            Cache Management
        </h3>
        <p class="text-sm text-gray-500 mb-4">Clear all system caches to resolve issues or apply changes immediately.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="clear_cache">
            
            <div class="flex items-center gap-4 flex-wrap">
                <button type="submit" onclick="return confirm('Clear all caches? This may temporarily slow down the system.')" 
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition flex items-center gap-2">
                    <i class="ri-refresh-line"></i> Clear All Cache
                </button>
                <span class="text-sm text-gray-500">Clears template, session, OPcache, and APC caches</span>
            </div>
        </form>
    </div>
    
    <!-- Database Backup & Restore -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-database-2-line text-green-600"></i>
            Database Backup & Restore
        </h3>
        <p class="text-sm text-gray-500 mb-4">Create backups or restore from existing backup files.</p>
        
        <!-- Backup Form -->
        <div class="border-b border-gray-200 pb-4 mb-4">
            <form method="POST" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="action" value="backup_database">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Backup Options</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="schema_only" value="on" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Schema only (no data)</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                    <i class="ri-download-line"></i> Create Backup
                </button>
            </form>
        </div>
        
        <!-- Restore Form -->
        <div class="border-b border-gray-200 pb-4 mb-4">
            <form method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="action" value="restore_database">
                
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Restore from Backup</label>
                    <input type="file" name="backup_file" accept=".sql" required 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                
                <button type="submit" onclick="return confirm('⚠️ WARNING: This will overwrite the entire database! Are you sure?')" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                    <i class="ri-upload-line"></i> Restore Database
                </button>
            </form>
        </div>
        
        <!-- Existing Backups List -->
        <?php if(!empty($backupFiles)): ?>
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Available Backups</h4>
            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Filename</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Modified</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($backupFiles as $backup): ?>
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs"><?php echo htmlspecialchars($backup['filename']); ?></td>
                            <td class="px-4 py-2"><?php echo number_format($backup['size'] / 1024, 1); ?> KB</td>
                            <td class="px-4 py-2 text-gray-500"><?php echo $backup['modified']; ?></td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="download_backup">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            <i class="ri-download-line"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" onclick="return confirm('Delete this backup file?')" class="text-red-600 hover:text-red-800 text-xs font-medium">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- System Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-information-line text-blue-600"></i>
            System Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">PHP Version</p>
                <p class="font-medium"><?php echo phpversion(); ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Server Software</p>
                <p class="font-medium"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Document Root</p>
                <p class="font-medium text-sm"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Current User</p>
                <p class="font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></p>
            </div>
        </div>
    </div>
</main>

<script>
function toggleMaintenanceFields(checked) {
    const fields = document.getElementById('maintenanceFields');
    if (fields) {
        fields.classList.toggle('hidden', !checked);
    }
    const label = document.querySelector('span.ml-3');
    if (label) {
        label.textContent = checked ? 'Maintenance is ENABLED' : 'Maintenance is DISABLED';
    }
    // Update button text
    const button = document.querySelector('button[type="submit"]');
    if (button) {
        if (checked) {
            button.innerHTML = '<i class="ri-close-circle-line"></i> Disable Maintenance';
            button.className = 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2';
        } else {
            button.innerHTML = '<i class="ri-check-circle-line"></i> Enable Maintenance';
            button.className = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center gap-2';
        }
    }
}
</script>
</body>
</html>