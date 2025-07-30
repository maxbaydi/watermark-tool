<?php
// Тест установки и работы компонентов для Мастера Водяных Знаков

echo "<!DOCTYPE html>\n";
echo "<html lang='ru'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Тест компонентов - Мастер Водяных Знаков</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; }\n";
echo "        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }\n";
echo "        .success { background: #d4edda; color: #155724; }\n";
echo "        .error { background: #f8d7da; color: #721c24; }\n";
echo "        .warning { background: #fff3cd; color: #856404; }\n";
echo "        h1 { color: #333; }\n";
echo "        h2 { color: #666; font-size: 18px; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <h1>Тест компонентов - Мастер Водяных Знаков</h1>\n";

// 1. Проверка версии PHP
echo "    <div class='test'>\n";
echo "        <h2>1. Версия PHP</h2>\n";
$phpVersion = phpversion();
$phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
echo "        <p>Текущая версия: <strong>$phpVersion</strong></p>\n";
if ($phpVersionOk) {
    echo "        <p class='success'>✓ PHP версия соответствует требованиям (7.4+)</p>\n";
} else {
    echo "        <p class='error'>✗ Требуется PHP 7.4 или выше</p>\n";
}
echo "    </div>\n";

// 2. Проверка ImageMagick
echo "    <div class='test'>\n";
echo "        <h2>2. ImageMagick</h2>\n";
if (class_exists('Imagick')) {
    echo "        <p class='success'>✓ Расширение Imagick установлено</p>\n";
    $imagick = new Imagick();
    $version = $imagick->getVersion();
    echo "        <p>Версия: <strong>" . $version['versionString'] . "</strong></p>\n";
    
    // Проверка поддерживаемых форматов
    $formats = Imagick::queryFormats();
    $requiredFormats = ['JPEG', 'PNG', 'GIF', 'WEBP'];
    $supportedFormats = [];
    $missingFormats = [];
    
    foreach ($requiredFormats as $format) {
        if (in_array($format, $formats)) {
            $supportedFormats[] = $format;
        } else {
            $missingFormats[] = $format;
        }
    }
    
    echo "        <p>Поддерживаемые форматы: <strong>" . implode(', ', $supportedFormats) . "</strong></p>\n";
    if (!empty($missingFormats)) {
        echo "        <p class='warning'>⚠ Отсутствуют форматы: " . implode(', ', $missingFormats) . "</p>\n";
    }
} else {
    echo "        <p class='error'>✗ Расширение Imagick НЕ установлено</p>\n";
    echo "        <p>Установите через: <code>sudo apt install php-imagick</code></p>\n";
}
echo "    </div>\n";

// 3. Проверка ZipArchive
echo "    <div class='test'>\n";
echo "        <h2>3. ZipArchive</h2>\n";
if (class_exists('ZipArchive')) {
    echo "        <p class='success'>✓ Расширение ZipArchive установлено</p>\n";
} else {
    echo "        <p class='error'>✗ Расширение ZipArchive НЕ установлено</p>\n";
    echo "        <p>Установите через: <code>sudo apt install php-zip</code></p>\n";
}
echo "    </div>\n";

// 4. Проверка папки temp
echo "    <div class='test'>\n";
echo "        <h2>4. Папка temp</h2>\n";
$tempDir = __DIR__ . '/temp';
if (file_exists($tempDir)) {
    echo "        <p class='success'>✓ Папка temp существует</p>\n";
    if (is_writable($tempDir)) {
        echo "        <p class='success'>✓ Папка temp доступна для записи</p>\n";
        $perms = substr(sprintf('%o', fileperms($tempDir)), -4);
        echo "        <p>Права доступа: <strong>$perms</strong></p>\n";
    } else {
        echo "        <p class='error'>✗ Папка temp НЕ доступна для записи</p>\n";
        echo "        <p>Выполните: <code>chmod 777 temp</code></p>\n";
    }
} else {
    echo "        <p class='error'>✗ Папка temp НЕ существует</p>\n";
    echo "        <p>Выполните: <code>mkdir temp && chmod 777 temp</code></p>\n";
}
echo "    </div>\n";

// 5. Проверка шрифтов
echo "    <div class='test'>\n";
echo "        <h2>5. Шрифты</h2>\n";
$fontDir = __DIR__ . '/fonts';
if (file_exists($fontDir) && is_dir($fontDir)) {
    echo "        <p class='success'>✓ Папка fonts существует</p>\n";
    $fonts = glob($fontDir . '/*.{ttf,otf}', GLOB_BRACE);
    if (!empty($fonts)) {
        echo "        <p class='success'>✓ Найдено шрифтов: " . count($fonts) . "</p>\n";
        echo "        <ul>\n";
        foreach ($fonts as $font) {
            echo "            <li>" . basename($font) . "</li>\n";
        }
        echo "        </ul>\n";
    } else {
        echo "        <p class='warning'>⚠ Шрифты не найдены в папке fonts</p>\n";
    }
} else {
    echo "        <p class='error'>✗ Папка fonts не найдена</p>\n";
}
echo "    </div>\n";

// 6. Тест создания изображения с водяным знаком
echo "    <div class='test'>\n";
echo "        <h2>6. Тест создания водяного знака</h2>\n";
if (class_exists('Imagick')) {
    try {
        // Создаем тестовое изображение
        $image = new Imagick();
        $image->newImage(400, 300, new ImagickPixel('#f0f0f0'));
        $image->setImageFormat('png');
        
        // Добавляем текст
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel('#333333'));
        $draw->setFontSize(20);
        $draw->setGravity(Imagick::GRAVITY_CENTER);
        $image->annotateImage($draw, 0, 0, 0, 'Тестовое изображение');
        
        // Сохраняем
        $testFile = $tempDir . '/test_image.png';
        $image->writeImage($testFile);
        
        if (file_exists($testFile)) {
            echo "        <p class='success'>✓ Тестовое изображение успешно создано</p>\n";
            echo "        <p>Размер файла: " . round(filesize($testFile) / 1024, 2) . " KB</p>\n";
            unlink($testFile); // Удаляем тестовый файл
        } else {
            echo "        <p class='error'>✗ Не удалось создать тестовое изображение</p>\n";
        }
        
        $image->destroy();
    } catch (Exception $e) {
        echo "        <p class='error'>✗ Ошибка: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "        <p class='warning'>⚠ Пропущено (ImageMagick не установлен)</p>\n";
}
echo "    </div>\n";

// 7. Проверка памяти и лимитов
echo "    <div class='test'>\n";
echo "        <h2>7. Лимиты PHP</h2>\n";
echo "        <p>Memory limit: <strong>" . ini_get('memory_limit') . "</strong></p>\n";
echo "        <p>Max execution time: <strong>" . ini_get('max_execution_time') . " сек</strong></p>\n";
echo "        <p>Upload max filesize: <strong>" . ini_get('upload_max_filesize') . "</strong></p>\n";
echo "        <p>Post max size: <strong>" . ini_get('post_max_size') . "</strong></p>\n";
echo "        <p>Max file uploads: <strong>" . ini_get('max_file_uploads') . "</strong></p>\n";
echo "    </div>\n";

// Итоговый статус
echo "    <div class='test'>\n";
echo "        <h2>Итоговый статус</h2>\n";
$allOk = $phpVersionOk && class_exists('Imagick') && class_exists('ZipArchive') && 
         file_exists($tempDir) && is_writable($tempDir);
if ($allOk) {
    echo "        <p class='success'><strong>✓ Все компоненты установлены и настроены правильно!</strong></p>\n";
    echo "        <p>Приложение готово к работе. <a href='index.html'>Перейти к приложению</a></p>\n";
} else {
    echo "        <p class='error'><strong>✗ Обнаружены проблемы, требующие исправления</strong></p>\n";
    echo "        <p>Исправьте указанные выше проблемы перед использованием приложения.</p>\n";
}
echo "    </div>\n";

echo "</body>\n";
echo "</html>\n";
?>