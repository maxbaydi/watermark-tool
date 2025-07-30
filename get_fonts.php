<?php
header('Content-Type: application/json; charset=utf-8');

$fonts_dir = __DIR__ . '/fonts';
$fonts = [];

if (is_dir($fonts_dir)) {
    $files = scandir($fonts_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['ttf', 'otf'])) {
                // Получаем красивое название шрифта из имени файла
                $name = pathinfo($file, PATHINFO_FILENAME);
                
                // Специальные случаи для конкретных файлов
                $name_map = [
                    'arial_bolditalicmt' => 'Arial Bold Italic',
                    'arialmt' => 'Arial',
                    'BarlowCondensed-Medium' => 'Barlow Condensed Medium',
                    'Impacted2.0' => 'Impacted 2.0',
                    'kwl_988_for_ice_cream_2014_by_zachary13265_dk22qjl' => 'KWL 988 For Ice Cream',
                    'PowerStationSolidRus-Regular' => 'PowerStation Solid Rus',
                    'RobotoCondensed-Bold' => 'Roboto Condensed Bold',
                    'RobotoCondensed-BoldItalic' => 'Roboto Condensed Bold Italic',
                    'RobotoCondensed-Italic' => 'Roboto Condensed Italic',
                    'RobotoCondensed-Light' => 'Roboto Condensed Light',
                    'RobotoCondensed-LightItalic' => 'Roboto Condensed Light Italic',
                    'RobotoCondensed-Regular' => 'Roboto Condensed Regular',
                    'VKSansDisplayDemiBoldFaux.v100' => 'VK Sans Display DemiBold'
                ];
                
                if (isset($name_map[$name])) {
                    $name = $name_map[$name];
                } else {
                    // Общая обработка для неизвестных файлов
                    $name = str_replace(['_', '-'], ' ', $name);
                    $name = preg_replace('/\.v\d+$/', '', $name); // Убираем версии
                    $name = preg_replace('/by [a-zA-Z0-9]+/', '', $name); // Убираем "by author"
                    $name = preg_replace('/[a-zA-Z0-9]{8,}/', '', $name); // Убираем длинные хеши
                    $name = trim($name);
                    $name = ucwords($name);
                }
                
                $fonts[] = [
                    'file' => $file,
                    'name' => $name
                ];
            }
        }
    }
}

// Сортируем по названию
usort($fonts, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

echo json_encode($fonts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?> 