<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Диагностика - Мастер Водяных Знаков</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Диагностика приложения "Мастер Водяных Знаков"</h1>
    
    <?php
    $tests = [];
    
    // Тест 1: Версия PHP
    $tests['php_version'] = [
        'name' => 'Версия PHP',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error',
        'message' => 'PHP ' . PHP_VERSION . ' - ' . (version_compare(PHP_VERSION, '7.4.0', '>=') ? '✅ Поддерживается' : '❌ Требуется PHP 7.4+')
    ];
    
    // Тест 2: Расширение Imagick
    $tests['imagick'] = [
        'name' => 'Расширение Imagick',
        'status' => class_exists('Imagick') ? 'success' : 'error',
        'message' => class_exists('Imagick') ? '✅ Установлено' : '❌ Не установлено'
    ];
    
    // Тест 3: Расширение ZipArchive
    $tests['zip'] = [
        'name' => 'Расширение ZipArchive',
        'status' => class_exists('ZipArchive') ? 'success' : 'error',
        'message' => class_exists('ZipArchive') ? '✅ Установлено' : '❌ Не установлено'
    ];
    
    // Тест 4: Папка temp
    $temp_dir = __DIR__ . '/temp';
    $tests['temp_dir'] = [
        'name' => 'Папка temp',
        'status' => (is_dir($temp_dir) && is_writable($temp_dir)) ? 'success' : 'error',
        'message' => (is_dir($temp_dir) && is_writable($temp_dir)) ? '✅ Существует и доступна для записи' : '❌ Не существует или недоступна для записи'
    ];
    
    // Тест 5: Папка fonts
    $fonts_dir = __DIR__ . '/fonts';
    $tests['fonts_dir'] = [
        'name' => 'Папка fonts',
        'status' => is_dir($fonts_dir) ? 'success' : 'error',
        'message' => is_dir($fonts_dir) ? '✅ Существует' : '❌ Не существует'
    ];
    
    // Тест 6: Шрифты в папке fonts
    if (is_dir($fonts_dir)) {
        $font_files = glob($fonts_dir . '/*.{ttf,otf}', GLOB_BRACE);
        $tests['font_files'] = [
            'name' => 'Файлы шрифтов',
            'status' => count($font_files) > 0 ? 'success' : 'warning',
            'message' => count($font_files) > 0 ? '✅ Найдено ' . count($font_files) . ' шрифтов' : '⚠️ Шрифты не найдены'
        ];
    }
    
    // Тест 7: Права на запись в temp
    if (is_dir($temp_dir)) {
        $test_file = $temp_dir . '/test_write.tmp';
        $write_test = @file_put_contents($test_file, 'test');
        if ($write_test !== false) {
            unlink($test_file);
            $tests['write_permission'] = [
                'name' => 'Права на запись в temp',
                'status' => 'success',
                'message' => '✅ Запись работает'
            ];
        } else {
            $tests['write_permission'] = [
                'name' => 'Права на запись в temp',
                'status' => 'error',
                'message' => '❌ Нет прав на запись'
            ];
        }
    }
    
    // Тест 8: Проверка ImageMagick
    if (class_exists('Imagick')) {
        try {
            $imagick = new Imagick();
            $formats = $imagick->queryFormats();
            $tests['imagick_formats'] = [
                'name' => 'Поддерживаемые форматы ImageMagick',
                'status' => 'info',
                'message' => '✅ Поддерживается ' . count($formats) . ' форматов'
            ];
        } catch (Exception $e) {
            $tests['imagick_formats'] = [
                'name' => 'Поддерживаемые форматы ImageMagick',
                'status' => 'error',
                'message' => '❌ Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    // Тест 9: Проверка основных файлов
    $required_files = ['index.html', 'process.php', 'fonts.css.php', 'get_fonts.php'];
    $missing_files = [];
    foreach ($required_files as $file) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $missing_files[] = $file;
        }
    }
    $tests['required_files'] = [
        'name' => 'Основные файлы приложения',
        'status' => empty($missing_files) ? 'success' : 'error',
        'message' => empty($missing_files) ? '✅ Все файлы на месте' : '❌ Отсутствуют: ' . implode(', ', $missing_files)
    ];
    
    // Вывод результатов
    foreach ($tests as $test) {
        echo '<div class="test-section ' . $test['status'] . '">';
        echo '<h3>' . $test['name'] . '</h3>';
        echo '<p>' . $test['message'] . '</p>';
        echo '</div>';
    }
    
    // Общая оценка
    $success_count = 0;
    $total_count = count($tests);
    foreach ($tests as $test) {
        if ($test['status'] === 'success' || $test['status'] === 'info') $success_count++;
    }
    
    echo '<div class="test-section ' . ($success_count === $total_count ? 'success' : 'warning') . '">';
    echo '<h2>📊 Общий результат</h2>';
    echo '<p>Пройдено тестов: ' . $success_count . ' из ' . $total_count . '</p>';
    if ($success_count === $total_count) {
        echo '<p>🎉 Все тесты пройдены! Приложение готово к работе.</p>';
    } else {
        echo '<p>⚠️ Некоторые тесты не пройдены. Проверьте настройки.</p>';
    }
    echo '</div>';
    
    // Дополнительная информация
    echo '<div class="test-section info">';
    echo '<h2>ℹ️ Дополнительная информация</h2>';
    echo '<pre>';
    echo "PHP версия: " . PHP_VERSION . "\n";
    echo "Операционная система: " . PHP_OS . "\n";
    echo "Временная зона: " . date_default_timezone_get() . "\n";
    echo "Максимальный размер загружаемого файла: " . ini_get('upload_max_filesize') . "\n";
    echo "Максимальное время выполнения: " . ini_get('max_execution_time') . " сек\n";
    echo "Лимит памяти: " . ini_get('memory_limit') . "\n";
    echo '</pre>';
    echo '</div>';
    ?>
    
    <div class="test-section info">
        <h2>🔗 Ссылки</h2>
        <p><a href="index.html">Открыть основное приложение</a></p>
        <p><a href="clean_temp.php">Очистить временные файлы</a></p>
    </div>
</body>
</html>