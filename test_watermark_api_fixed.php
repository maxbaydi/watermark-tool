<?php
// Исправленный тест API наложения водяных знаков

// Проверяем наличие тестового изображения
if (!file_exists('test_image.jpg')) {
    die("Сначала создайте тестовое изображение с помощью create_test_image.php\n");
}

// Параметры для теста
$api_url = 'http://localhost:8080/process.php';
$test_image = __DIR__ . '/test_image.jpg';

// Тест 1: Текстовый водяной знак
echo "=== Тест 1: Текстовый водяной знак ===\n";

// Создаем временный файл для отправки через форму
$boundary = uniqid();
$delimiter = '-------------' . $boundary;

$postData = [
    'watermarkType' => 'text',
    'text' => 'WATERMARK TEST',
    'font' => 'arialmt.ttf',
    'fontSize' => '48',
    'textColor' => '#FF0000',
    'opacity' => '50',
    'x' => '200',
    'y' => '300',
    'previewWidth' => '800',
    'previewHeight' => '600'
];

// Формируем multipart/form-data вручную для правильной отправки массива файлов
$data = '';

// Добавляем POST параметры
foreach ($postData as $name => $value) {
    $data .= "--{$delimiter}\r\n";
    $data .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
    $data .= "{$value}\r\n";
}

// Добавляем файл как массив
$data .= "--{$delimiter}\r\n";
$data .= "Content-Disposition: form-data; name=\"images[]\"; filename=\"test_image.jpg\"\r\n";
$data .= "Content-Type: image/jpeg\r\n\r\n";
$data .= file_get_contents($test_image) . "\r\n";
$data .= "--{$delimiter}--\r\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: multipart/form-data; boundary={$delimiter}",
    "Content-Length: " . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "HTTP код: $httpCode\n";

if ($httpCode == 200) {
    // Извлекаем заголовки
    $headerLines = explode("\r\n", $headers);
    $contentType = '';
    $contentDisposition = '';
    
    foreach ($headerLines as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $contentType = trim(substr($header, 13));
        }
        if (stripos($header, 'content-disposition:') === 0) {
            $contentDisposition = trim(substr($header, 20));
        }
    }
    
    echo "Content-Type: $contentType\n";
    echo "Content-Disposition: $contentDisposition\n";
    
    // Сохраняем результат
    if (strpos($contentType, 'image/') === 0) {
        $outputFile = 'test_result_text.jpg';
        file_put_contents($outputFile, $body);
        echo "Результат сохранен в: $outputFile\n";
        echo "Размер файла: " . round(filesize($outputFile) / 1024, 2) . " KB\n";
    } else {
        echo "Неожиданный тип контента\n";
        echo "Тело ответа: " . substr($body, 0, 200) . "...\n";
    }
} else {
    echo "Ошибка: $body\n";
}

echo "\n";

// Тест 2: Создаем водяной знак-изображение
echo "=== Создание водяного знака-изображения ===\n";

if (!file_exists('test_watermark.png')) {
    try {
        $watermark = new Imagick();
        $watermark->newImage(200, 100, new ImagickPixel('transparent'));
        $watermark->setImageFormat('png');
        
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel('#0000FF'));
        $draw->setFontSize(24);
        $draw->setFont(__DIR__ . '/fonts/arialmt.ttf');
        $draw->setGravity(Imagick::GRAVITY_CENTER);
        
        $watermark->annotateImage($draw, 0, 0, 0, 'LOGO');
        
        // Добавляем рамку
        $draw2 = new ImagickDraw();
        $draw2->setStrokeColor(new ImagickPixel('#0000FF'));
        $draw2->setStrokeWidth(3);
        $draw2->setFillColor(new ImagickPixel('transparent'));
        $draw2->rectangle(5, 5, 195, 95);
        $watermark->drawImage($draw2);
        
        $watermark->writeImage('test_watermark.png');
        echo "Водяной знак создан: test_watermark.png\n";
        
    } catch (Exception $e) {
        die("Ошибка создания водяного знака: " . $e->getMessage() . "\n");
    }
} else {
    echo "Водяной знак уже существует: test_watermark.png\n";
}

// Тест 3: Изображение как водяной знак
echo "\n=== Тест 2: Изображение как водяной знак ===\n";

$postData2 = [
    'watermarkType' => 'image',
    'watermarkSize' => '0.3',  // Размер в процентах от ширины изображения (30%)
    'watermarkOpacity' => '0.7',  // Прозрачность от 0 до 1
    'watermark_x' => '0.5',  // Координата X в процентах (центр)
    'watermark_y' => '0.5',  // Координата Y в процентах (центр)
    'previewWidth' => '800',
    'previewHeight' => '600'
];

// Формируем multipart/form-data для второго теста
$data2 = '';

// Добавляем POST параметры
foreach ($postData2 as $name => $value) {
    $data2 .= "--{$delimiter}\r\n";
    $data2 .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
    $data2 .= "{$value}\r\n";
}

// Добавляем основное изображение как массив
$data2 .= "--{$delimiter}\r\n";
$data2 .= "Content-Disposition: form-data; name=\"images[]\"; filename=\"test_image.jpg\"\r\n";
$data2 .= "Content-Type: image/jpeg\r\n\r\n";
$data2 .= file_get_contents($test_image) . "\r\n";

// Добавляем водяной знак
$data2 .= "--{$delimiter}\r\n";
$data2 .= "Content-Disposition: form-data; name=\"watermark_image\"; filename=\"test_watermark.png\"\r\n";
$data2 .= "Content-Type: image/png\r\n\r\n";
$data2 .= file_get_contents('test_watermark.png') . "\r\n";
$data2 .= "--{$delimiter}--\r\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $api_url);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $data2);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "Content-Type: multipart/form-data; boundary={$delimiter}",
    "Content-Length: " . strlen($data2)
]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HEADER, true);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$headerSize2 = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);
$body2 = substr($response2, $headerSize2);

curl_close($ch2);

echo "HTTP код: $httpCode2\n";

if ($httpCode2 == 200) {
    $outputFile2 = 'test_result_image.jpg';
    file_put_contents($outputFile2, $body2);
    echo "Результат сохранен в: $outputFile2\n";
    echo "Размер файла: " . round(filesize($outputFile2) / 1024, 2) . " KB\n";
} else {
    echo "Ошибка: $body2\n";
}

echo "\n=== Тесты завершены ===\n";
echo "Проверьте созданные файлы:\n";
echo "- test_result_text.jpg (текстовый водяной знак)\n";
echo "- test_result_image.jpg (изображение как водяной знак)\n";
?>