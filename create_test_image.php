<?php
// Создание тестового изображения для проверки водяных знаков

if (!class_exists('Imagick')) {
    die('ImageMagick не установлен');
}

try {
    // Создаем новое изображение
    $image = new Imagick();
    $image->newImage(800, 600, new ImagickPixel('#ffffff'));
    $image->setImageFormat('jpg');
    
    // Добавляем градиентный фон
    $gradient = new Imagick();
    $gradient->newPseudoImage(800, 600, 'gradient:#87CEEB-#4682B4');
    $image->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, 0);
    
    // Добавляем текст
    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('#ffffff'));
    $draw->setFontSize(48);
    $draw->setGravity(Imagick::GRAVITY_CENTER);
    $draw->setFont(__DIR__ . '/fonts/arialmt.ttf');
    
    // Добавляем тень для текста
    $shadow = new ImagickDraw();
    $shadow->setFillColor(new ImagickPixel('#00000080'));
    $shadow->setFontSize(48);
    $shadow->setGravity(Imagick::GRAVITY_CENTER);
    $shadow->setFont(__DIR__ . '/fonts/arialmt.ttf');
    
    $image->annotateImage($shadow, 2, 2, 0, 'Тестовое изображение');
    $image->annotateImage($draw, 0, 0, 0, 'Тестовое изображение');
    
    // Добавляем декоративные элементы
    $draw2 = new ImagickDraw();
    $draw2->setFillColor(new ImagickPixel('#ffffff40'));
    $draw2->circle(100, 100, 150, 150);
    $draw2->circle(700, 100, 750, 150);
    $draw2->circle(100, 500, 150, 550);
    $draw2->circle(700, 500, 750, 550);
    $image->drawImage($draw2);
    
    // Сохраняем изображение
    $filename = 'test_image.jpg';
    $image->writeImage($filename);
    $image->destroy();
    
    echo "Тестовое изображение '$filename' успешно создано!\n";
    echo "Размер: 800x600 пикселей\n";
    echo "Формат: JPEG\n";
    echo "Размер файла: " . round(filesize($filename) / 1024, 2) . " KB\n";
    
} catch (Exception $e) {
    echo "Ошибка при создании изображения: " . $e->getMessage() . "\n";
}
?>