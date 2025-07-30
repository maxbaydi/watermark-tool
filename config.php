<?php
/**
 * Конфигурационный файл приложения
 */

// Основные пути
define('SCRIPT_DIR', __DIR__);
define('TEMP_BASE_DIR', SCRIPT_DIR . '/temp');
define('FONT_DIR', SCRIPT_DIR . '/fonts');

// Ограничения
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('MAX_FILES_COUNT', 20); // Максимальное количество файлов за раз
define('MAX_IMAGE_DIMENSION', 5000); // Максимальный размер изображения в пикселях
define('TEMP_FILE_MAX_AGE', 3600); // Время жизни временных файлов (1 час)

// Настройки обработки изображений
define('JPEG_QUALITY', 90);
define('PNG_COMPRESSION', 9);
define('WEBP_QUALITY', 90);

// Поддерживаемые форматы
define('ALLOWED_IMAGE_FORMATS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_WATERMARK_FORMATS', ['png']);

// Настройки безопасности
define('ENABLE_DEBUG', false); // Отключить в продакшене
define('LOG_FILE', SCRIPT_DIR . '/logs/app.log'); // Путь к файлу логов

// Настройки по умолчанию для водяных знаков
define('DEFAULT_FONT_SIZE', 48);
define('DEFAULT_TEXT_OPACITY', 0.7);
define('DEFAULT_TEXT_COLOR', 'white');
define('DEFAULT_WATERMARK_SIZE', 0.25);
define('DEFAULT_WATERMARK_OPACITY', 0.8);

// Настройки превью
define('PREVIEW_MAX_WIDTH', 800);
define('PREVIEW_MAX_HEIGHT', 600);
define('PREVIEW_QUALITY', 85);

// Режим работы
define('PRODUCTION_MODE', true); 