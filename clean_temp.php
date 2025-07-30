<?php
header('Content-Type: application/json; charset=utf-8');

define('TEMP_BASE_DIR', __DIR__ . '/temp');

function clean_temp_directory() {
    if (!is_dir(TEMP_BASE_DIR)) {
        return ['success' => false, 'message' => 'Папка temp не существует'];
    }

    $cleaned_count = 0;
    $errors = [];
    
    try {
        $items = scandir(TEMP_BASE_DIR);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = TEMP_BASE_DIR . '/' . $item;
            
            // Удаляем только папки сессий (начинающиеся с ws_)
            if (is_dir($item_path) && strpos($item, 'ws_') === 0) {
                if (delete_directory($item_path)) {
                    $cleaned_count++;
                } else {
                    $errors[] = "Не удалось удалить папку: $item";
                }
            }
            // Также удаляем старые файлы (старше 1 часа)
            elseif (is_file($item_path)) {
                $file_age = time() - filemtime($item_path);
                if ($file_age > 3600) { // 1 час = 3600 секунд
                    if (unlink($item_path)) {
                        $cleaned_count++;
                    } else {
                        $errors[] = "Не удалось удалить файл: $item";
                    }
                }
            }
        }
        
        return [
            'success' => true, 
            'cleaned_count' => $cleaned_count,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Ошибка при очистке: ' . $e->getMessage()];
    }
}

function delete_directory($dir) {
    if (!is_dir($dir)) return false;
    
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    
    foreach($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    
    return rmdir($dir);
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = clean_temp_directory();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    // GET запрос - показываем информацию о папке temp
    $temp_info = [];
    
    if (is_dir(TEMP_BASE_DIR)) {
        $items = scandir(TEMP_BASE_DIR);
        $session_dirs = [];
        $files = [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = TEMP_BASE_DIR . '/' . $item;
            
            if (is_dir($item_path) && strpos($item, 'ws_') === 0) {
                $session_dirs[] = [
                    'name' => $item,
                    'created' => date('Y-m-d H:i:s', filemtime($item_path)),
                    'size' => get_directory_size($item_path)
                ];
            } elseif (is_file($item_path)) {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($item_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($item_path))
                ];
            }
        }
        
        $temp_info = [
            'success' => true,
            'session_dirs' => $session_dirs,
            'files' => $files,
            'total_sessions' => count($session_dirs),
            'total_files' => count($files)
        ];
    } else {
        $temp_info = ['success' => false, 'message' => 'Папка temp не существует'];
    }
    
    echo json_encode($temp_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function get_directory_size($dir) {
    $size = 0;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    
    foreach($files as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    
    return $size;
}
?> 