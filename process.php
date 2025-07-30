<?php
// Отключаем отображение ошибок для рабочей версии
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED);

// Увеличиваем лимиты
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

define('SCRIPT_DIR', __DIR__);
define('TEMP_BASE_DIR', SCRIPT_DIR . '/temp');
// Указываем папку со шрифтами
define('FONT_DIR', SCRIPT_DIR . '/fonts');

// --- Предварительные проверки ---
function run_pre_flight_checks() {
    if (!class_exists('Imagick')) {
        http_response_code(500);
        die('Ошибка сервера: Расширение PHP Imagick не установлено или не включено.');
    }
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('Ошибка сервера: Расширение PHP ZipArchive не установлено.');
    }
    if (!file_exists(TEMP_BASE_DIR) || !is_dir(TEMP_BASE_DIR) || !is_writable(TEMP_BASE_DIR)) {
        http_response_code(500);
        die('Ошибка сервера: Папка "temp" не существует или недоступна для записи.');
    }
}

// --- Главный обработчик ---
function handle_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['images'])) {
        http_response_code(400);
        die('Некорректный запрос или изображения не загружены.');
    }

    $settings = $_POST;
    
    // Отладочная информация
    file_put_contents('debug.log', "DEBUG: Все POST параметры: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents('debug.log', "DEBUG: Все FILES параметры: " . print_r($_FILES, true) . "\n", FILE_APPEND);
    if ($settings['watermarkType'] === 'text' && isset($settings['font']) && !file_exists(FONT_DIR . '/' . $settings['font'])) {
        http_response_code(500);
        die('Ошибка сервера: Файл шрифта не найден.');
    }

    $session_dir = TEMP_BASE_DIR . '/' . uniqid('ws_', true);
    if (!mkdir($session_dir, 0777, true)) {
        http_response_code(500);
        die('Не удалось создать временную директорию.');
    }

    $processed_files = [];
    $files = reArrayFiles($_FILES['images']);

    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) continue;

        try {
            $image = new Imagick($file['tmp_name']);
            // Конвертируем CMYK в RGB для совместимости
            if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }
            
            // Правильно обрабатываем альфа-канал
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            
            // Убеждаемся, что формат поддерживает прозрачность
            $format = strtolower($image->getImageFormat());
            if (in_array($format, ['png', 'gif', 'webp'])) {
                $image->setBackgroundColor(new ImagickPixel('transparent'));
            }
            
            // Применяем водяной знак
            if ($settings['watermarkType'] === 'text') {
                apply_text_watermark($image, $settings);
            } elseif ($settings['watermarkType'] === 'image' && !empty($_FILES['watermark_image'])) {
                apply_image_watermark($image, $settings, $_FILES['watermark_image']['tmp_name']);
            }

            // Санитизация и сохранение
            $original_name = $file['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                continue; // Пропускаем неподдерживаемые форматы
            }
            $sanitized_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $original_name);
            $destination_path = $session_dir . '/' . $sanitized_name;
            
            // Устанавливаем правильный формат для сохранения
            $image->setImageFormat($file_ext);
            
            // Специальная обработка для разных форматов
            if ($file_ext === 'png') {
                $image->setImageCompressionQuality(0); // PNG использует сжатие без потерь
            } elseif ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                $image->setImageCompressionQuality(90);
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            } elseif ($file_ext === 'webp') {
                $image->setImageCompressionQuality(90);
            }
            
            $image->writeImage($destination_path);
            $processed_files[] = $destination_path;
            
        } catch (ImagickException $e) {
            error_log("Imagick Error: " . $e->getMessage());
            continue; // Пропускаем файл, если Imagick не смог его обработать
        } finally {
            if (isset($image)) {
                $image->clear();
            }
        }
    }

    if (count($processed_files) === 1) {
        send_single_file($processed_files[0]);
    } elseif (count($processed_files) > 1) {
        send_zip_archive($processed_files, $session_dir);
    } else {
        http_response_code(400);
        echo 'Ни один файл не был обработан. Возможно, файлы повреждены или имеют неподдерживаемый формат.';
    }

    clean_up($session_dir);
}

function apply_text_watermark(&$image, $settings) {
    // Отладочная информация
    file_put_contents('debug.log', "DEBUG: Настройки водяного знака: " . print_r($settings, true) . "\n", FILE_APPEND);
    
    $draw = new ImagickDraw();
    
    // Проверяем, выбран ли шрифт
    if (empty($settings['font'])) {
        file_put_contents('debug.log', "DEBUG: Шрифт не выбран, используем fallback\n", FILE_APPEND);
        // Если шрифт не выбран, используем первый доступный
        $fonts_dir = FONT_DIR;
        $files = scandir($fonts_dir);
        $default_font = 'arialmt.ttf'; // Fallback
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['ttf', 'otf'])) {
                    $default_font = $file;
                    break;
                }
            }
        }
        $font_path = FONT_DIR . '/' . $default_font;
    } else {
        $font_path = FONT_DIR . '/' . $settings['font'];
        file_put_contents('debug.log', "DEBUG: Выбранный шрифт: " . $settings['font'] . "\n", FILE_APPEND);
        file_put_contents('debug.log', "DEBUG: Полный путь к шрифту: " . $font_path . "\n", FILE_APPEND);
    }
    
    // Проверяем существование файла шрифта
    if (!file_exists($font_path)) {
        file_put_contents('debug.log', "ОШИБКА: Файл шрифта не найден: " . $font_path . "\n", FILE_APPEND);
        // Используем системный шрифт
        $draw->setFont('Arial');
    } else {
        file_put_contents('debug.log', "УСПЕХ: Файл шрифта найден: " . $font_path . "\n", FILE_APPEND);
        $draw->setFont($font_path);
        
        // Устанавливаем кодировку для правильного отображения спецсимволов
        $draw->setTextEncoding('UTF-8');
    }
    
    // Отладочная информация для размера шрифта
    file_put_contents('debug.log', "DEBUG: Размер шрифта из настроек: " . (isset($settings['fontSize']) ? $settings['fontSize'] : 'НЕ УСТАНОВЛЕН') . "\n", FILE_APPEND);
    
    // Масштабируем размер шрифта относительно оригинального изображения
    $previewWidth = isset($settings['preview_width']) ? intval($settings['preview_width']) : 0;
    $previewHeight = isset($settings['preview_height']) ? intval($settings['preview_height']) : 0;
    $originalWidth = isset($settings['original_width']) ? intval($settings['original_width']) : 0;
    $originalHeight = isset($settings['original_height']) ? intval($settings['original_height']) : 0;
    
    $baseFontSize = isset($settings['fontSize']) ? intval($settings['fontSize']) : 48;
    
    // Рассчитываем масштаб
    $scale = 1.0;
    if ($previewWidth > 0 && $originalWidth > 0) {
        $scale = $originalWidth / $previewWidth;
    }
    
    $fontSize = intval($baseFontSize * $scale);
    
    file_put_contents('debug.log', "DEBUG: Размер превью: {$previewWidth}x{$previewHeight}\n", FILE_APPEND);
    file_put_contents('debug.log', "DEBUG: Размер оригинала: {$originalWidth}x{$originalHeight}\n", FILE_APPEND);
    file_put_contents('debug.log', "DEBUG: Масштаб: {$scale}\n", FILE_APPEND);
    file_put_contents('debug.log', "DEBUG: Базовый размер шрифта: {$baseFontSize}\n", FILE_APPEND);
    file_put_contents('debug.log', "DEBUG: Применяемый размер шрифта: {$fontSize}\n", FILE_APPEND);
    
    $draw->setFontSize($fontSize);
    file_put_contents('debug.log', "Установлен размер шрифта: " . $fontSize . "\n", FILE_APPEND);
    $draw->setFillColor($settings['textColor']);
    file_put_contents('debug.log', "Установлен цвет: " . $settings['textColor'] . "\n", FILE_APPEND);
    $draw->setFillAlpha(floatval($settings['textOpacity']));
    file_put_contents('debug.log', "Установлена прозрачность: " . $settings['textOpacity'] . "\n", FILE_APPEND);
    
    // Устанавливаем жирность шрифта
    if (isset($settings['fontWeight']) && $settings['fontWeight'] === 'on') {
        $draw->setFontWeight(700); // 700 - bold
    }

    // Используем координаты перетаскивания
    if (isset($settings['watermark_x']) && isset($settings['watermark_y'])) {
        $centerX = floatval($settings['watermark_x']) * $image->getImageWidth();
        $centerY = floatval($settings['watermark_y']) * $image->getImageHeight();
        
        // Получаем размеры текста для центрирования
        $metrics = $image->queryFontMetrics($draw, $settings['text']);
        $textWidth = $metrics['textWidth'];
        $textHeight = $metrics['ascender'] + $metrics['descender'];
        
        // Рассчитываем координаты левого верхнего угла текста для центрирования
        $x = $centerX - ($textWidth / 2);
        $y = $centerY + ($textHeight / 2) - $metrics['descender'];
        
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);

        // Добавляем тень
        if (isset($settings['textShadow']) && $settings['textShadow'] === 'on') {
            $shadowDraw = clone $draw;
            $shadowDraw->setFillColor('black');
            $shadowDraw->setFillAlpha(floatval($settings['textOpacity']) * 0.5);
            $image->annotateImage($shadowDraw, $x + 2, $y + 2, 0, $text);
        }

        // Обрабатываем текст с правильной кодировкой для спецсимволов
        $text = $settings['text'];
        // Убеждаемся, что текст в UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        file_put_contents('debug.log', "DEBUG: Текст для наложения: " . $text . "\n", FILE_APPEND);
        
        $image->annotateImage($draw, $x, $y, 0, $text);
    }
}

function apply_image_watermark(&$image, $settings, $watermark_path) {
    $watermark = new Imagick($watermark_path);
    
    // Правильно обрабатываем прозрачность PNG
    $watermark->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
    $watermark->setBackgroundColor(new ImagickPixel('transparent'));
    
    $main_width = $image->getImageWidth();
    $main_height = $image->getImageHeight();
    
    $new_wm_width = $main_width * floatval($settings['watermarkSize']);
    $watermark->scaleImage($new_wm_width, 0);
    
    // Применяем прозрачность через альфа-канал
    $opacity = floatval($settings['watermarkOpacity']);
    if ($opacity < 1.0) {
        $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
    }
    
    // Используем координаты перетаскивания
    if (isset($settings['watermark_x']) && isset($settings['watermark_y'])) {
        $centerX = floatval($settings['watermark_x']) * $main_width;
        $centerY = floatval($settings['watermark_y']) * $main_height;
        
        // Получаем размеры водяного знака для центрирования
        $wm_width = $watermark->getImageWidth();
        $wm_height = $watermark->getImageHeight();
        
        // Рассчитываем координаты левого верхнего угла для центрирования
        $x = $centerX - ($wm_width / 2);
        $y = $centerY - ($wm_height / 2);
        
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
    }

    $watermark->clear();
}

function reArrayFiles(&$file_post) {
    $file_ary = [];
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
}

function send_file_headers($filename) {
    header('Content-Description: File Transfer');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'zip' => 'application/zip'];
    $content_type = $mime_types[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
}

function send_single_file($path) {
    if (ob_get_level()) ob_end_clean();
    $filename = basename($path);
    send_file_headers($filename);
    header('Content-Length: ' . filesize($path));
    flush();
    readfile($path);
    exit;
}

function send_zip_archive($files, $session_dir) {
    if (ob_get_level()) ob_end_clean();
    $zip_path = $session_dir . '/watermarked_images.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        die("Не удалось создать ZIP-архив.");
    }
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    send_file_headers('watermarked_images.zip');
    header('Content-Length: ' . filesize($zip_path));
    flush();
    readfile($zip_path);
    exit;
}

function clean_up($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){ rmdir($file->getRealPath()); } else { unlink($file->getRealPath()); }
    }
    rmdir($dir);
}

// --- Запускаем скрипт ---
run_pre_flight_checks();
handle_request();
