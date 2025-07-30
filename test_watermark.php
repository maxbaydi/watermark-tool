<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест установки - Мастер Водяных Знаков</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-item { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .test-item.success { background: #d4edda; }
        .test-item.error { background: #f8d7da; }
        .test-item.warning { background: #fff3cd; }
        .test-item.info { background: #d1ecf1; }
        h1 { color: #333; text-align: center; }
        h2 { color: #666; }
        .code { background: #f8f9fa; padding: 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔍 Диагностика установки - Мастер Водяных Знаков</h1>
    
    <div class="test-section info">
        <h2>📋 Общая информация</h2>
        <div class="test-item">
            <strong>Версия PHP:</strong> <?php echo phpversion(); ?>
        </div>
        <div class="test-item">
            <strong>Версия сервера:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно'; ?>
        </div>
        <div class="test-item">
            <strong>Текущая директория:</strong> <?php echo __DIR__; ?>
        </div>
    </div>

    <div class="test-section">
        <h2>🔧 Проверка расширений PHP</h2>
        
        <?php
        $required_extensions = [
            'imagick' => 'ImageMagick (для обработки изображений)',
            'zip' => 'ZipArchive (для создания архивов)',
            'mbstring' => 'Multibyte String (для работы с UTF-8)',
            'gd' => 'GD (резервный вариант)'
        ];
        
        foreach ($required_extensions as $ext => $description) {
            $loaded = extension_loaded($ext);
            $class = $loaded ? 'success' : 'error';
            $status = $loaded ? '✅ Установлено' : '❌ Не установлено';
            echo "<div class='test-item {$class}'>";
            echo "<strong>{$ext}:</strong> {$status} - {$description}";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📁 Проверка файлов и папок</h2>
        
        <?php
        $required_files = [
            'index.html' => 'Главный интерфейс',
            'process.php' => 'Обработчик изображений',
            'fonts.css.php' => 'Генератор CSS шрифтов',
            'get_fonts.php' => 'API для получения шрифтов',
            'clean_temp.php' => 'Очистка временных файлов'
        ];
        
        foreach ($required_files as $file => $description) {
            $exists = file_exists($file);
            $class = $exists ? 'success' : 'error';
            $status = $exists ? '✅ Найден' : '❌ Не найден';
            echo "<div class='test-item {$class}'>";
            echo "<strong>{$file}:</strong> {$status} - {$description}";
            echo "</div>";
        }
        
        // Проверка папок
        $required_dirs = [
            'fonts' => 'Папка со шрифтами',
            'temp' => 'Временная папка'
        ];
        
        foreach ($required_dirs as $dir => $description) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            $class = $writable ? 'success' : ($exists ? 'warning' : 'error');
            $status = $writable ? '✅ Найдена и доступна для записи' : 
                     ($exists ? '⚠️ Найдена, но недоступна для записи' : '❌ Не найдена');
            echo "<div class='test-item {$class}'>";
            echo "<strong>{$dir}/:</strong> {$status} - {$description}";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔤 Проверка шрифтов</h2>
        
        <?php
        $fonts_dir = __DIR__ . '/fonts';
        if (is_dir($fonts_dir)) {
            $fonts = scandir($fonts_dir);
            $font_files = array_filter($fonts, function($file) {
                return $file !== '.' && $file !== '..' && 
                       in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['ttf', 'otf']);
            });
            
            if (count($font_files) > 0) {
                echo "<div class='test-item success'>";
                echo "<strong>✅ Найдено шрифтов:</strong> " . count($font_files);
                echo "</div>";
                
                foreach (array_slice($font_files, 0, 5) as $font) {
                    echo "<div class='test-item info'>";
                    echo "<strong>📄</strong> {$font}";
                    echo "</div>";
                }
                
                if (count($font_files) > 5) {
                    echo "<div class='test-item info'>";
                    echo "... и еще " . (count($font_files) - 5) . " шрифтов";
                    echo "</div>";
                }
            } else {
                echo "<div class='test-item error'>";
                echo "<strong>❌ Шрифты не найдены</strong> в папке fonts/";
                echo "</div>";
            }
        } else {
            echo "<div class='test-item error'>";
            echo "<strong>❌ Папка fonts/ не найдена</strong>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>⚙️ Проверка ImageMagick</h2>
        
        <?php
        if (class_exists('Imagick')) {
            try {
                $imagick = new Imagick();
                $version = $imagick->getVersion();
                echo "<div class='test-item success'>";
                echo "<strong>✅ ImageMagick работает</strong><br>";
                echo "Версия: " . $version['versionString'];
                echo "</div>";
                
                // Проверяем поддержку форматов
                $formats = $imagick->queryFormats();
                $required_formats = ['JPEG', 'PNG', 'GIF', 'WEBP'];
                $supported_formats = [];
                
                foreach ($required_formats as $format) {
                    if (in_array($format, $formats)) {
                        $supported_formats[] = $format;
                    }
                }
                
                echo "<div class='test-item success'>";
                echo "<strong>✅ Поддерживаемые форматы:</strong> " . implode(', ', $supported_formats);
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='test-item error'>";
                echo "<strong>❌ Ошибка ImageMagick:</strong> " . $e->getMessage();
                echo "</div>";
            }
        } else {
            echo "<div class='test-item error'>";
            echo "<strong>❌ ImageMagick не установлен</strong><br>";
            echo "Установите: <span class='code'>sudo apt install php-imagick</span>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🧪 Тест создания временных файлов</h2>
        
        <?php
        $temp_dir = __DIR__ . '/temp';
        if (is_dir($temp_dir) && is_writable($temp_dir)) {
            $test_file = $temp_dir . '/test_' . uniqid() . '.txt';
            $test_content = 'Тест записи файла: ' . date('Y-m-d H:i:s');
            
            if (file_put_contents($test_file, $test_content)) {
                echo "<div class='test-item success'>";
                echo "<strong>✅ Запись в temp/ работает</strong><br>";
                echo "Создан тестовый файл: " . basename($test_file);
                echo "</div>";
                
                // Удаляем тестовый файл
                unlink($test_file);
                echo "<div class='test-item info'>";
                echo "<strong>🗑️ Тестовый файл удален</strong>";
                echo "</div>";
            } else {
                echo "<div class='test-item error'>";
                echo "<strong>❌ Не удалось создать файл в temp/</strong>";
                echo "</div>";
            }
        } else {
            echo "<div class='test-item error'>";
            echo "<strong>❌ Папка temp/ недоступна для записи</strong><br>";
            echo "Выполните: <span class='code'>chmod 777 temp</span>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🌐 Проверка веб-сервера</h2>
        
        <?php
        $server_info = [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'Неизвестно',
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Неизвестно',
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'Неизвестно',
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'Неизвестно',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'Неизвестно'
        ];
        
        foreach ($server_info as $key => $value) {
            echo "<div class='test-item info'>";
            echo "<strong>{$key}:</strong> {$value}";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📊 Рекомендации</h2>
        
        <?php
        $recommendations = [];
        
        if (!extension_loaded('imagick')) {
            $recommendations[] = "Установите ImageMagick: <span class='code'>sudo apt install php-imagick</span>";
        }
        
        if (!is_dir('temp') || !is_writable('temp')) {
            $recommendations[] = "Создайте папку temp и установите права: <span class='code'>mkdir temp && chmod 777 temp</span>";
        }
        
        if (!is_dir('fonts') || count(scandir('fonts')) <= 2) {
            $recommendations[] = "Добавьте шрифты в папку fonts/";
        }
        
        if (empty($recommendations)) {
            echo "<div class='test-item success'>";
            echo "<strong>🎉 Все готово!</strong> Приложение должно работать корректно.";
            echo "</div>";
        } else {
            echo "<div class='test-item warning'>";
            echo "<strong>⚠️ Требуется настройка:</strong>";
            echo "<ul>";
            foreach ($recommendations as $rec) {
                echo "<li>{$rec}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section info">
        <h2>🔗 Полезные ссылки</h2>
        <div class="test-item">
            <a href="index.html" target="_blank">🏠 Главная страница приложения</a>
        </div>
        <div class="test-item">
            <a href="fonts.css.php" target="_blank">🔤 CSS шрифтов</a>
        </div>
        <div class="test-item">
            <a href="get_fonts.php" target="_blank">📋 API шрифтов</a>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <p><strong>Мастер Водяных Знаков v2.0.0</strong></p>
        <p>Тест выполнен: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>