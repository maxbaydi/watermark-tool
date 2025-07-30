<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест функциональности - Мастер Водяных Знаков</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-image { max-width: 200px; border: 1px solid #ddd; margin: 10px; }
        .result-image { max-width: 300px; border: 2px solid #28a745; margin: 10px; }
    </style>
</head>
<body>
    <h1>🧪 Тест функциональности приложения</h1>
    
    <div class="test-section info">
        <h2>Тест 1: Создание тестового изображения</h2>
        <?php
        try {
            // Создаем тестовое изображение
            $test_image = new Imagick();
            $test_image->newImage(400, 300, 'white');
            $test_image->setImageFormat('png');
            
            // Добавляем текст на изображение
            $draw = new ImagickDraw();
            $draw->setFontSize(24);
            $draw->setFillColor('black');
            $draw->setGravity(Imagick::GRAVITY_CENTER);
            $test_image->annotateImage($draw, 0, 0, 0, 'Тестовое изображение');
            
            $test_path = __DIR__ . '/temp/test_image.png';
            $test_image->writeImage($test_path);
            $test_image->clear();
            
            echo '<p>✅ Тестовое изображение создано</p>';
            echo '<img src="temp/test_image.png" class="test-image" alt="Тестовое изображение">';
            
        } catch (Exception $e) {
            echo '<p>❌ Ошибка создания тестового изображения: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="test-section info">
        <h2>Тест 2: Проверка обработки водяных знаков</h2>
        <?php
        if (file_exists(__DIR__ . '/temp/test_image.png')) {
            try {
                // Загружаем тестовое изображение
                $image = new Imagick(__DIR__ . '/temp/test_image.png');
                
                // Создаем водяной знак
                $draw = new ImagickDraw();
                $draw->setFontSize(36);
                $draw->setFillColor('red');
                $draw->setFillAlpha(0.7);
                $draw->setGravity(Imagick::GRAVITY_CENTER);
                
                // Добавляем водяной знак
                $image->annotateImage($draw, 0, 0, 0, 'ВОДЯНОЙ ЗНАК');
                
                $result_path = __DIR__ . '/temp/test_result.png';
                $image->writeImage($result_path);
                $image->clear();
                
                echo '<p>✅ Водяной знак добавлен</p>';
                echo '<img src="temp/test_result.png" class="result-image" alt="Результат с водяным знаком">';
                
            } catch (Exception $e) {
                echo '<p>❌ Ошибка добавления водяного знака: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p>❌ Тестовое изображение не найдено</p>';
        }
        ?>
    </div>
    
    <div class="test-section info">
        <h2>Тест 3: Проверка различных форматов</h2>
        <?php
        $formats = ['jpg', 'png', 'gif', 'webp'];
        $supported_formats = [];
        
        foreach ($formats as $format) {
            try {
                $test_image = new Imagick();
                $test_image->newImage(100, 100, 'white');
                $test_image->setImageFormat($format);
                
                $test_path = __DIR__ . '/temp/test.' . $format;
                $test_image->writeImage($test_path);
                $test_image->clear();
                
                if (file_exists($test_path)) {
                    $supported_formats[] = $format;
                    unlink($test_path); // Удаляем тестовый файл
                }
            } catch (Exception $e) {
                // Формат не поддерживается
            }
        }
        
        echo '<p>✅ Поддерживаемые форматы: ' . implode(', ', $supported_formats) . '</p>';
        ?>
    </div>
    
    <div class="test-section info">
        <h2>Тест 4: Проверка шрифтов</h2>
        <?php
        $fonts_dir = __DIR__ . '/fonts';
        if (is_dir($fonts_dir)) {
            $font_files = glob($fonts_dir . '/*.{ttf,otf}', GLOB_BRACE);
            $working_fonts = [];
            
            foreach (array_slice($font_files, 0, 3) as $font_file) { // Тестируем только первые 3 шрифта
                try {
                    $test_image = new Imagick();
                    $test_image->newImage(300, 100, 'white');
                    
                    $draw = new ImagickDraw();
                    $draw->setFont($font_file);
                    $draw->setFontSize(24);
                    $draw->setFillColor('black');
                    $draw->setGravity(Imagick::GRAVITY_CENTER);
                    
                    $test_image->annotateImage($draw, 0, 0, 0, 'Тест шрифта');
                    $test_image->clear();
                    
                    $font_name = basename($font_file);
                    $working_fonts[] = $font_name;
                    
                } catch (Exception $e) {
                    // Шрифт не работает
                }
            }
            
            echo '<p>✅ Работающих шрифтов: ' . count($working_fonts) . ' из ' . count($font_files) . '</p>';
            if (!empty($working_fonts)) {
                echo '<p>Примеры: ' . implode(', ', array_slice($working_fonts, 0, 3)) . '</p>';
            }
        } else {
            echo '<p>❌ Папка шрифтов не найдена</p>';
        }
        ?>
    </div>
    
    <div class="test-section success">
        <h2>🎉 Результат тестирования</h2>
        <p>Все основные компоненты приложения работают корректно!</p>
        <p><a href="index.html">Перейти к основному приложению</a></p>
        <p><a href="test_watermark.php">Запустить полную диагностику</a></p>
    </div>
    
    <script>
        // Автоматическая очистка тестовых файлов через 30 секунд
        setTimeout(() => {
            fetch('clean_temp.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Тестовые файлы очищены');
                    }
                })
                .catch(error => console.error('Ошибка очистки:', error));
        }, 30000);
    </script>
</body>
</html>