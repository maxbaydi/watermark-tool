<?php
header('Content-Type: text/html; charset=utf-8');

// Проверяем наличие ImageMagick
if (!class_exists('Imagick')) {
    die('❌ ImageMagick не установлен. Установите: sudo apt install php-imagick');
}

// Создаем тестовое изображение с прозрачностью
function create_test_png() {
    $width = 200;
    $height = 100;
    
    $imagick = new Imagick();
    $imagick->newImage($width, $height, new ImagickPixel('transparent'));
    $imagick->setImageFormat('png');
    
    // Создаем прозрачный круг
    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('rgba(255, 0, 0, 0.7)')); // Красный с прозрачностью
    $draw->circle($width/2, $height/2, $width/2, $height/2);
    
    $imagick->drawImage($draw);
    
    return $imagick;
}

// Тестируем наложение прозрачного водяного знака
function test_watermark_transparency() {
    // Создаем базовое изображение
    $base = new Imagick();
    $base->newImage(400, 300, new ImagickPixel('white'));
    $base->setImageFormat('png');
    
    // Добавляем текст на базовое изображение
    $draw = new ImagickDraw();
    $draw->setFillColor('black');
    $draw->setFontSize(24);
    $draw->setGravity(Imagick::GRAVITY_CENTER);
    $base->annotateImage($draw, 0, 0, 0, 'Тестовое изображение');
    
    // Создаем прозрачный водяной знак
    $watermark = create_test_png();
    
    // Накладываем водяной знак
    $base->compositeImage($watermark, Imagick::COMPOSITE_OVER, 100, 100);
    
    return $base;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест прозрачности - Мастер Водяных Знаков</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-image { max-width: 100%; height: auto; border: 1px solid #ccc; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .code { background: #f8f9fa; padding: 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🧪 Тест прозрачности PNG водяных знаков</h1>
    
    <div class="test-section info">
        <h2>📋 Описание теста</h2>
        <p>Этот тест проверяет корректность обработки прозрачности PNG водяных знаков:</p>
        <ul>
            <li>Создание PNG с прозрачностью</li>
            <li>Наложение прозрачного водяного знака</li>
            <li>Сохранение альфа-канала</li>
            <li>Корректное отображение прозрачности</li>
        </ul>
    </div>

    <div class="grid">
        <div class="test-section">
            <h2>🔤 Тестовый водяной знак</h2>
            <?php
            try {
                $watermark = create_test_png();
                $watermark_data = $watermark->getImageBlob();
                $watermark_base64 = base64_encode($watermark_data);
                
                echo "<img src='data:image/png;base64,{$watermark_base64}' class='test-image' alt='Тестовый водяной знак'>";
                echo "<p><strong>✅ Водяной знак создан успешно</strong></p>";
                echo "<p>Размер: {$watermark->getImageWidth()}x{$watermark->getImageHeight()} пикселей</p>";
                echo "<p>Формат: {$watermark->getImageFormat()}</p>";
                
                // Проверяем наличие альфа-канала
                if ($watermark->getImageAlphaChannel()) {
                    echo "<p><strong>✅ Альфа-канал присутствует</strong></p>";
                } else {
                    echo "<p><strong>⚠️ Альфа-канал отсутствует</strong></p>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<strong>❌ Ошибка создания водяного знака:</strong> " . $e->getMessage();
                echo "</div>";
            }
            ?>
        </div>

        <div class="test-section">
            <h2>🖼️ Результат наложения</h2>
            <?php
            try {
                $result = test_watermark_transparency();
                $result_data = $result->getImageBlob();
                $result_base64 = base64_encode($result_data);
                
                echo "<img src='data:image/png;base64,{$result_base64}' class='test-image' alt='Результат наложения'>";
                echo "<p><strong>✅ Наложение выполнено успешно</strong></p>";
                echo "<p>Размер: {$result->getImageWidth()}x{$result->getImageHeight()} пикселей</p>";
                
                // Проверяем сохранение альфа-канала
                if ($result->getImageAlphaChannel()) {
                    echo "<p><strong>✅ Альфа-канал сохранен</strong></p>";
                } else {
                    echo "<p><strong>⚠️ Альфа-канал потерян</strong></p>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<strong>❌ Ошибка наложения:</strong> " . $e->getMessage();
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <div class="test-section">
        <h2>🔧 Проверка ImageMagick</h2>
        <?php
        try {
            $imagick = new Imagick();
            $version = $imagick->getVersion();
            
            echo "<div class='success'>";
            echo "<strong>✅ ImageMagick работает корректно</strong><br>";
            echo "Версия: " . $version['versionString'];
            echo "</div>";
            
            // Проверяем поддержку PNG
            $formats = $imagick->queryFormats();
            if (in_array('PNG', $formats)) {
                echo "<div class='success'>";
                echo "<strong>✅ Поддержка PNG включена</strong>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<strong>❌ Поддержка PNG отсутствует</strong>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ Ошибка ImageMagick:</strong> " . $e->getMessage();
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📊 Результат теста</h2>
        <?php
        $tests_passed = 0;
        $total_tests = 0;
        
        // Тест 1: Создание водяного знака
        $total_tests++;
        try {
            $watermark = create_test_png();
            if ($watermark->getImageAlphaChannel()) {
                $tests_passed++;
                echo "<div class='success'>✅ Тест 1: Создание прозрачного водяного знака - ПРОЙДЕН</div>";
            } else {
                echo "<div class='error'>❌ Тест 1: Создание прозрачного водяного знака - ПРОВАЛЕН</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Тест 1: Создание прозрачного водяного знака - ОШИБКА: " . $e->getMessage() . "</div>";
        }
        
        // Тест 2: Наложение водяного знака
        $total_tests++;
        try {
            $result = test_watermark_transparency();
            if ($result->getImageAlphaChannel()) {
                $tests_passed++;
                echo "<div class='success'>✅ Тест 2: Наложение прозрачного водяного знака - ПРОЙДЕН</div>";
            } else {
                echo "<div class='error'>❌ Тест 2: Наложение прозрачного водяного знака - ПРОВАЛЕН</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Тест 2: Наложение прозрачного водяного знака - ОШИБКА: " . $e->getMessage() . "</div>";
        }
        
        // Тест 3: Поддержка форматов
        $total_tests++;
        try {
            $imagick = new Imagick();
            $formats = $imagick->queryFormats();
            if (in_array('PNG', $formats)) {
                $tests_passed++;
                echo "<div class='success'>✅ Тест 3: Поддержка PNG - ПРОЙДЕН</div>";
            } else {
                echo "<div class='error'>❌ Тест 3: Поддержка PNG - ПРОВАЛЕН</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Тест 3: Поддержка PNG - ОШИБКА: " . $e->getMessage() . "</div>";
        }
        
        echo "<hr>";
        echo "<div class='info'>";
        echo "<strong>Итоговый результат:</strong> {$tests_passed}/{$total_tests} тестов пройдено";
        if ($tests_passed === $total_tests) {
            echo "<br><span style='color: green; font-weight: bold;'>🎉 Все тесты пройдены! Прозрачность работает корректно.</span>";
        } else {
            echo "<br><span style='color: red; font-weight: bold;'>⚠️ Некоторые тесты не пройдены. Проверьте настройки ImageMagick.</span>";
        }
        echo "</div>";
        ?>
    </div>

    <div class="test-section info">
        <h2>🔗 Полезные ссылки</h2>
        <div>
            <a href="index.html" target="_blank">🏠 Главная страница приложения</a>
        </div>
        <div>
            <a href="test_watermark.php" target="_blank">🔍 Полная диагностика</a>
        </div>
        <div>
            <a href="test_dragging.html" target="_blank">🧪 Тест перетаскивания</a>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <p><strong>Мастер Водяных Знаков v2.0.0</strong></p>
        <p>Тест прозрачности выполнен: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>