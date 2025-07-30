<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Очистка временной директории
 */
function clean_temp_directory() {
    if (!is_dir(TEMP_BASE_DIR)) {
        return ['success' => false, 'message' => 'Папка temp не существует'];
    }

    $cleaned_count = 0;
    $errors = [];
    $total_size_freed = 0;
    
    try {
        $items = scandir(TEMP_BASE_DIR);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = TEMP_BASE_DIR . '/' . $item;
            
            // Удаляем только папки сессий (начинающиеся с ws_)
            if (is_dir($item_path) && strpos($item, 'ws_') === 0) {
                $dir_size = get_directory_size($item_path);
                if (delete_directory($item_path)) {
                    $cleaned_count++;
                    $total_size_freed += $dir_size;
                } else {
                    $errors[] = "Не удалось удалить папку: $item";
                }
            }
            // Также удаляем старые файлы
            elseif (is_file($item_path)) {
                $file_age = time() - filemtime($item_path);
                if ($file_age > TEMP_FILE_MAX_AGE) {
                    $file_size = filesize($item_path);
                    if (@unlink($item_path)) {
                        $cleaned_count++;
                        $total_size_freed += $file_size;
                    } else {
                        $errors[] = "Не удалось удалить файл: $item";
                    }
                }
            }
        }
        
        return [
            'success' => true, 
            'cleaned_count' => $cleaned_count,
            'size_freed' => format_bytes($total_size_freed),
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        log_error('Ошибка при очистке временных файлов: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка при очистке: ' . $e->getMessage()];
    }
}

/**
 * Рекурсивное удаление директории
 */
function delete_directory($dir) {
    if (!is_dir($dir)) return false;
    
    try {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        
        return @rmdir($dir);
    } catch (Exception $e) {
        log_error('Ошибка при удалении директории: ' . $e->getMessage());
        return false;
    }
}

/**
 * Получение размера директории
 */
function get_directory_size($dir) {
    $size = 0;
    
    try {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::LEAVES_ONLY);
        
        foreach($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (Exception $e) {
        log_error('Ошибка при подсчете размера директории: ' . $e->getMessage());
    }
    
    return $size;
}

/**
 * Форматирование размера в читаемый вид
 */
function format_bytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Получение статистики временной папки
 */
function get_temp_stats() {
    $stats = [
        'session_dirs' => [],
        'files' => [],
        'total_size' => 0,
        'old_items_count' => 0
    ];
    
    if (!is_dir(TEMP_BASE_DIR)) {
        return $stats;
    }
    
    try {
        $items = scandir(TEMP_BASE_DIR);
        $current_time = time();
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = TEMP_BASE_DIR . '/' . $item;
            $item_age = $current_time - filemtime($item_path);
            $is_old = $item_age > TEMP_FILE_MAX_AGE;
            
            if (is_dir($item_path) && strpos($item, 'ws_') === 0) {
                $dir_size = get_directory_size($item_path);
                $stats['session_dirs'][] = [
                    'name' => $item,
                    'created' => date('Y-m-d H:i:s', filemtime($item_path)),
                    'age' => format_time_ago($item_age),
                    'size' => format_bytes($dir_size),
                    'is_old' => $is_old
                ];
                $stats['total_size'] += $dir_size;
                if ($is_old) $stats['old_items_count']++;
                
            } elseif (is_file($item_path)) {
                $file_size = filesize($item_path);
                $stats['files'][] = [
                    'name' => $item,
                    'size' => format_bytes($file_size),
                    'modified' => date('Y-m-d H:i:s', filemtime($item_path)),
                    'age' => format_time_ago($item_age),
                    'is_old' => $is_old
                ];
                $stats['total_size'] += $file_size;
                if ($is_old) $stats['old_items_count']++;
            }
        }
    } catch (Exception $e) {
        log_error('Ошибка при получении статистики: ' . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Форматирование времени в читаемый вид
 */
function format_time_ago($seconds) {
    if ($seconds < 60) {
        return $seconds . ' сек. назад';
    } elseif ($seconds < 3600) {
        return round($seconds / 60) . ' мин. назад';
    } elseif ($seconds < 86400) {
        return round($seconds / 3600) . ' ч. назад';
    } else {
        return round($seconds / 86400) . ' дн. назад';
    }
}

/**
 * Логирование ошибок
 */
function log_error($message) {
    if (!PRODUCTION_MODE || ENABLE_DEBUG) {
        $log_dir = dirname(LOG_FILE);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0777, true);
        }
        
        $log_entry = date('Y-m-d H:i:s') . ' [CLEANER] - ' . $message . PHP_EOL;
        @file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Очистка временных файлов
    $result = clean_temp_directory();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Получение информации о временной папке
    $stats = get_temp_stats();
    
    $response = [
        'success' => true,
        'stats' => [
            'total_sessions' => count($stats['session_dirs']),
            'total_files' => count($stats['files']),
            'total_size' => format_bytes($stats['total_size']),
            'old_items' => $stats['old_items_count']
        ],
        'session_dirs' => $stats['session_dirs'],
        'files' => $stats['files'],
        'max_age' => TEMP_FILE_MAX_AGE . ' секунд'
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
}
?>