<?php
require_once 'config.php';

// Отключаем отображение ошибок для продакшена
ini_set('display_errors', PRODUCTION_MODE ? 0 : 1);
ini_set('display_startup_errors', PRODUCTION_MODE ? 0 : 1);
error_reporting(PRODUCTION_MODE ? 0 : E_ALL);

// Увеличиваем лимиты
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

/**
 * Логирование ошибок
 */
function log_error($message, $context = []) {
    if (!PRODUCTION_MODE || ENABLE_DEBUG) {
        $log_dir = dirname(LOG_FILE);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0777, true);
        }
        
        $log_entry = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $log_entry .= ' - Context: ' . json_encode($context);
        }
        $log_entry .= PHP_EOL;
        
        @file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Валидация входных данных
 */
function validate_input($settings) {
    $errors = [];
    
    // Проверка типа водяного знака
    if (!in_array($settings['watermarkType'], ['text', 'image'])) {
        $errors[] = 'Неверный тип водяного знака';
    }
    
    // Валидация координат
    $watermark_x = floatval($settings['watermark_x'] ?? 0.5);
    $watermark_y = floatval($settings['watermark_y'] ?? 0.5);
    
    if ($watermark_x < 0 || $watermark_x > 1) {
        $errors[] = 'Координата X должна быть в диапазоне 0-1';
    }
    
    if ($watermark_y < 0 || $watermark_y > 1) {
        $errors[] = 'Координата Y должна быть в диапазоне 0-1';
    }
    
    // Валидация параметров для текстового водяного знака
    if ($settings['watermarkType'] === 'text') {
        if (empty($settings['text'])) {
            $errors[] = 'Текст водяного знака не может быть пустым';
        }
        
        $fontSizePercent = floatval($settings['fontSizePercent'] ?? 0);
        if ($fontSizePercent < 0.5 || $fontSizePercent > 10) {
            $errors[] = 'Размер шрифта должен быть от 0.5% до 10% ширины изображения';
        }
        
        $textOpacity = floatval($settings['textOpacity'] ?? DEFAULT_TEXT_OPACITY);
        if ($textOpacity < 0 || $textOpacity > 1) {
            $errors[] = 'Прозрачность текста должна быть от 0 до 1';
        }
        
        if (!in_array($settings['textColor'] ?? DEFAULT_TEXT_COLOR, ['white', 'black'])) {
            $errors[] = 'Неверный цвет текста';
        }
    }
    
    // Валидация параметров для водяного знака-изображения
    if ($settings['watermarkType'] === 'image') {
        $watermarkSizePercent = floatval($settings['watermarkSizePercent'] ?? 0);
        if ($watermarkSizePercent < 5 || $watermarkSizePercent > 100) {
            $errors[] = 'Размер водяного знака должен быть от 5% до 100% ширины изображения';
        }
        
        $watermarkOpacity = floatval($settings['watermarkOpacity'] ?? DEFAULT_WATERMARK_OPACITY);
        if ($watermarkOpacity < 0 || $watermarkOpacity > 1) {
            $errors[] = 'Прозрачность водяного знака должна быть от 0 до 1';
        }
    }
    
    return $errors;
}

/**
 * Предварительные проверки системы
 */
function run_pre_flight_checks() {
    if (!class_exists('Imagick')) {
        http_response_code(500);
        die(json_encode(['error' => 'Расширение PHP Imagick не установлено или не включено.']));
    }
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die(json_encode(['error' => 'Расширение PHP ZipArchive не установлено.']));
    }
    if (!file_exists(TEMP_BASE_DIR) || !is_dir(TEMP_BASE_DIR) || !is_writable(TEMP_BASE_DIR)) {
        http_response_code(500);
        die(json_encode(['error' => 'Папка "temp" не существует или недоступна для записи.']));
    }
}

/**
 * Главный обработчик запросов
 */
function handle_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['images'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Некорректный запрос или изображения не загружены.']));
    }

    $settings = $_POST;
    // Логируем все параметры для отладки передачи с клиента
    log_error('Получены параметры формы', $settings);

    // Валидация входных данных
    $validation_errors = validate_input($settings);
    if (!empty($validation_errors)) {
        http_response_code(400);
        die(json_encode(['error' => 'Ошибки валидации', 'details' => $validation_errors]));
    }

    // Проверка файла шрифта для текстового водяного знака
    if ($settings['watermarkType'] === 'text' && !empty($settings['font'])) {
        $font_path = get_font_path($settings['font']);
        if (!file_exists($font_path)) {
            log_error('Шрифт не найден', ['font' => $settings['font'], 'font_path' => $font_path]);
            http_response_code(400);
            die(json_encode(['error' => 'Выбранный шрифт не найден.']));
        }
    }

    // Создание временной директории
    $session_dir = TEMP_BASE_DIR . '/' . uniqid('ws_', true);
    if (!mkdir($session_dir, 0777, true)) {
        http_response_code(500);
        die(json_encode(['error' => 'Не удалось создать временную директорию.']));
    }

    $processed_files = [];
    $errors = [];
    $files = reArrayFiles($_FILES['images']);

    // Проверка количества файлов
    if (count($files) > MAX_FILES_COUNT) {
        clean_up($session_dir);
        http_response_code(400);
        die(json_encode(['error' => 'Превышено максимальное количество файлов: ' . MAX_FILES_COUNT]));
    }

    foreach ($files as $index => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Ошибка загрузки файла {$file['name']}";
            continue;
        }
        
        // Проверка размера файла
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = "Файл {$file['name']} превышает максимальный размер";
            continue;
        }

        try {
            $result = process_image($file, $settings, $session_dir);
            if ($result) {
                $processed_files[] = $result;
            }
        } catch (Exception $e) {
            log_error("Ошибка обработки файла {$file['name']}: " . $e->getMessage());
            $errors[] = "Ошибка обработки файла {$file['name']}";
        }
    }

    // Отправка результата
    if (count($processed_files) === 1) {
        send_single_file($processed_files[0]);
    } elseif (count($processed_files) > 1) {
        send_zip_archive($processed_files, $session_dir);
    } else {
        http_response_code(400);
        echo json_encode([
            'error' => 'Ни один файл не был обработан успешно.',
            'details' => $errors
        ]);
    }

    // Очистка временных файлов
    clean_up($session_dir);
}

/**
 * Обработка одного изображения
 */
function process_image($file, $settings, $session_dir) {
    $image = null;
    
    try {
        // Проверка типа файла
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ALLOWED_IMAGE_FORMATS)) {
            throw new Exception("Неподдерживаемый формат файла");
        }
        
        $image = new Imagick($file['tmp_name']);
        
        // Проверка размеров изображения
        if ($image->getImageWidth() > MAX_IMAGE_DIMENSION || 
            $image->getImageHeight() > MAX_IMAGE_DIMENSION) {
            throw new Exception("Изображение слишком большое");
        }
        
        // Конвертация CMYK в RGB
        if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        
        // Настройка альфа-канала
        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        
        // Установка прозрачного фона для форматов с поддержкой прозрачности
        if (in_array($file_ext, ['png', 'gif', 'webp'])) {
            $image->setBackgroundColor(new ImagickPixel('transparent'));
        }
        
        // Применение водяного знака
        if ($settings['watermarkType'] === 'text') {
            apply_text_watermark($image, $settings);
        } elseif ($settings['watermarkType'] === 'image' && !empty($_FILES['watermark_image'])) {
            apply_image_watermark($image, $settings, $_FILES['watermark_image']['tmp_name']);
        }

        // Подготовка имени файла
        $sanitized_name = sanitize_filename($file['name']);
        $destination_path = $session_dir . '/' . $sanitized_name;
        
        // Установка формата и качества
        $image->setImageFormat($file_ext);
        set_image_quality($image, $file_ext);
        
        // Сохранение файла
        $image->writeImage($destination_path);
        
        return $destination_path;
        
    } finally {
        if ($image !== null) {
            $image->clear();
            $image->destroy();
        }
    }
}

/**
 * Применение текстового водяного знака
 */
function apply_text_watermark(&$image, $settings) {
    $draw = new ImagickDraw();
    
    try {
        // Выбор шрифта
        $font_path = get_font_path($settings['font'] ?? '');
        if (!file_exists($font_path)) {
            log_error('Не найден файл шрифта для annotateImage', ['font' => $settings['font'], 'font_path' => $font_path]);
            throw new Exception('Файл шрифта не найден');
        }
        putenv('GDFONTPATH=' . FONT_DIR);
        $draw->setFont($font_path);
        $draw->setTextEncoding('UTF-8');

        // Получение и валидация параметров
        $textOpacity = floatval($settings['textOpacity'] ?? DEFAULT_TEXT_OPACITY);
        $textColor = $settings['textColor'] ?? DEFAULT_TEXT_COLOR;
        $text = $settings['text'] ?? '';

        // Размер шрифта в процентах от ширины изображения
        $fontSizePercent = floatval($settings['fontSizePercent'] ?? 0);
        if ($fontSizePercent <= 0) {
            // Fallback: используем старый способ
            $fontSize = intval($settings['fontSize'] ?? DEFAULT_FONT_SIZE);
            $fontSizePercent = ($fontSize / $image->getImageWidth()) * 100;
        }

        // Рассчитываем размер шрифта в пикселях
        $fontSize = intval(($fontSizePercent / 100) * $image->getImageWidth());

        // Настройка параметров рисования
        $draw->setFontSize($fontSize);
        $draw->setFillColor($textColor);
        $draw->setFillAlpha($textOpacity);

        // Жирность шрифта
        if (isset($settings['fontWeight']) && $settings['fontWeight'] === 'on') {
            $draw->setFontWeight(700);
        }

        // Гравитация для центрирования
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        // Позиционирование
        $position = calculate_watermark_position($image, $settings);

        // Тень текста
        if (isset($settings['textShadow']) && $settings['textShadow'] === 'on') {
            $shadowDraw = clone $draw;
            $shadowDraw->setFillColor('black');
            $shadowDraw->setFillAlpha($textOpacity * 0.5);
            $image->annotateImage($shadowDraw, $position['x'] + 2, $position['y'] + 2, 0, $text);
            $shadowDraw->clear();
            $shadowDraw->destroy();
        }

        // Наложение основного текста
        $image->annotateImage($draw, $position['x'], $position['y'], 0, $text);

    } finally {
        $draw->clear();
        $draw->destroy();
    }
}

/**
 * Применение водяного знака-изображения
 */
function apply_image_watermark(&$image, $settings, $watermark_path) {
    // Проверка файла водяного знака
    if (!file_exists($watermark_path)) {
        throw new Exception("Файл водяного знака не найден");
    }
    
    $watermark = null;
    
    try {
    $watermark = new Imagick($watermark_path);
    
        // Настройка прозрачности
    $watermark->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
    $watermark->setBackgroundColor(new ImagickPixel('transparent'));
    
        // Размер водяного знака в процентах от ширины изображения
        $watermarkSizePercent = floatval($settings['watermarkSizePercent'] ?? 0);
        if ($watermarkSizePercent <= 0) {
            // Fallback: используем старый способ
            $watermarkSize = floatval($settings['watermarkSize'] ?? DEFAULT_WATERMARK_SIZE);
            $watermarkSizePercent = $watermarkSize * 100;
        }
        
        // Масштабирование водяного знака
        $new_width = ($watermarkSizePercent / 100) * $image->getImageWidth();
        $watermark->scaleImage($new_width, 0);
        
        // Применение прозрачности
        $opacity = floatval($settings['watermarkOpacity'] ?? DEFAULT_WATERMARK_OPACITY);
    if ($opacity < 1.0) {
        $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
    }
    
        // Позиционирование
        $position = calculate_watermark_position($image, $settings, $watermark);
        
        // Наложение водяного знака
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $position['x'], $position['y']);
        
    } finally {
        if ($watermark !== null) {
            $watermark->clear();
            $watermark->destroy();
        }
    }
}

/**
 * Расчет позиции водяного знака
 */
function calculate_watermark_position($image, $settings, $watermark = null) {
    // Новые параметры из формы
    $centerX_preview = isset($settings['watermark_center_x_preview']) ? floatval($settings['watermark_center_x_preview']) : null;
    $centerY_preview = isset($settings['watermark_center_y_preview']) ? floatval($settings['watermark_center_y_preview']) : null;
    $preview_width = isset($settings['preview_width']) ? floatval($settings['preview_width']) : null;
    $preview_height = isset($settings['preview_height']) ? floatval($settings['preview_height']) : null;
    $original_width = $image->getImageWidth();
    $original_height = $image->getImageHeight();

    // Если есть все параметры — пересчитываем координаты центра в оригинале
    if ($centerX_preview !== null && $centerY_preview !== null && $preview_width && $preview_height) {
        $centerX_orig = $centerX_preview / $preview_width * $original_width;
        $centerY_orig = $centerY_preview / $preview_height * $original_height;
    } else {
        // Fallback: используем относительные координаты (0-1)
        $watermark_x = floatval($settings['watermark_x'] ?? 0.5);
        $watermark_y = floatval($settings['watermark_y'] ?? 0.5);
        $centerX_orig = $watermark_x * $original_width;
        $centerY_orig = $watermark_y * $original_height;
    }

    if ($watermark !== null) {
        // Для изображения-водяного знака: корректируем на половину размера (в оригинале)
        $wm_width_orig = $watermark->getImageWidth();
        $wm_height_orig = $watermark->getImageHeight();
        // Центр -> левый верхний угол
        $x = $centerX_orig - $wm_width_orig / 2;
        $y = $centerY_orig - $wm_height_orig / 2;
        return ['x' => $x, 'y' => $y];
    } else {
        // Для текста: возвращаем центр (гравитация CENTER)
        return ['x' => $centerX_orig, 'y' => $centerY_orig];
    }
}

/**
 * Получение пути к шрифту
 */
function get_font_path($font_name) {
    if (empty($font_name)) {
        // Возвращаем первый доступный шрифт
        $fonts = glob(FONT_DIR . '/*.{ttf,otf}', GLOB_BRACE);
        if (!empty($fonts)) return $fonts[0];
        return '';
    }
    $candidate = FONT_DIR . '/' . basename($font_name);
    if (file_exists($candidate)) return $candidate;
    // Попытка найти по частичному совпадению (например, если расширение не указано)
    $matches = glob(FONT_DIR . '/' . pathinfo($font_name, PATHINFO_FILENAME) . '*');
    if (!empty($matches)) return $matches[0];
    return '';
}

/**
 * Установка качества изображения
 */
function set_image_quality(&$image, $format) {
    switch ($format) {
        case 'jpg':
        case 'jpeg':
            $image->setImageCompressionQuality(JPEG_QUALITY);
            $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            break;
        case 'png':
            $image->setImageCompressionQuality(PNG_COMPRESSION);
            break;
        case 'webp':
            $image->setImageCompressionQuality(WEBP_QUALITY);
            break;
    }
}

/**
 * Санитизация имени файла с поддержкой UTF-8
 */
function sanitize_filename($filename) {
    // Разделяем имя и расширение
    $pathinfo = pathinfo($filename);
    $name = $pathinfo['filename'];
    $ext = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
    
    // Заменяем опасные символы, но сохраняем unicode
    $name = preg_replace('/[<>:"\/\\|?*]/', '_', $name);
    
    // Ограничиваем длину имени
    if (mb_strlen($name) > 200) {
        $name = mb_substr($name, 0, 200);
    }
    
    // Добавляем префикс к обработанному файлу
    return 'watermarked_' . $name . $ext;
}

/**
 * Переформатирование массива файлов
 */
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

/**
 * Установка заголовков для скачивания файла
 */
function send_file_headers($filename) {
    header('Content-Description: File Transfer');
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'zip' => 'application/zip'
    ];
    
    $content_type = $mime_types[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
}

/**
 * Отправка одного файла
 */
function send_single_file($path) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $filename = basename($path);
    send_file_headers($filename);
    header('Content-Length: ' . filesize($path));
    
    readfile($path);
    exit;
}

/**
 * Отправка ZIP архива
 */
function send_zip_archive($files, $session_dir) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $zip_path = $session_dir . '/watermarked_images.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        die(json_encode(['error' => 'Не удалось создать ZIP-архив.']));
    }
    
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();
    
    send_file_headers('watermarked_images.zip');
    header('Content-Length: ' . filesize($zip_path));
    
    readfile($zip_path);
    exit;
}

/**
 * Очистка временной директории
 */
function clean_up($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    
    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }
    
    @rmdir($dir);
}

// Запуск обработки
run_pre_flight_checks();
handle_request();
?>