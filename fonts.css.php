<?php
header('Content-Type: text/css; charset=utf-8');

$fonts_dir = __DIR__ . '/fonts';
$fonts = [];

if (is_dir($fonts_dir)) {
    $files = scandir($fonts_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['ttf', 'otf'])) {
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
                    $name = str_replace(['_', '-'], ' ', $name);
                    $name = preg_replace('/\.v\d+$/', '', $name);
                    $name = preg_replace('/by [a-zA-Z0-9]+/', '', $name);
                    $name = preg_replace('/[a-zA-Z0-9]{8,}/', '', $name);
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

// Генерируем CSS для каждого шрифта
foreach ($fonts as $font) {
    $fontFamily = str_replace(' ', '', $font['name']); // Убираем пробелы для CSS
    $fontUrl = 'fonts/' . $font['file'];
    $fontFormat = strtolower(pathinfo($font['file'], PATHINFO_EXTENSION)) === 'otf' ? 'opentype' : 'truetype';
    
    echo "@font-face {\n";
    echo "    font-family: '{$font['name']}';\n";
    echo "    src: url('{$fontUrl}') format('{$fontFormat}');\n";
    echo "    font-weight: normal;\n";
    echo "    font-style: normal;\n";
    echo "}\n\n";
}
?>
