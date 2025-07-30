# 📋 Инструкция по установке - Мастер Водяных Знаков

## 🎯 Обзор приложения

**Мастер Водяных Знаков** - это веб-инструмент для наложения водяных знаков на изображения с поддержкой:
- Текстовых и графических водяных знаков
- Интерактивного перетаскивания
- Поддержки CMYK и WebP форматов
- Пакетной обработки изображений

## 🔧 Системные требования

### Обязательные компоненты:
- **PHP 7.4+** с расширениями:
  - `imagick` (ImageMagick)
  - `zip` (ZipArchive)
  - `mbstring` (для UTF-8)
- **Веб-сервер** (Apache, Nginx или встроенный PHP сервер)
- **Папка temp** с правами на запись (777)

### Рекомендуемые компоненты:
- **GD** (резервный вариант обработки изображений)
- **Модуль mod_rewrite** (для Apache)

## 📦 Установка

### 1. Установка PHP и расширений

#### Ubuntu/Debian:
```bash
# Обновление пакетов
sudo apt update

# Установка PHP и расширений
sudo apt install php php-imagick php-zip php-mbstring php-gd

# Проверка установки
php -m | grep -E "(imagick|zip|mbstring|gd)"
```

#### CentOS/RHEL:
```bash
# Установка PHP и расширений
sudo yum install php php-imagick php-zip php-mbstring php-gd

# Проверка установки
php -m | grep -E "(imagick|zip|mbstring|gd)"
```

#### Windows (XAMPP/WAMP):
1. Скачайте и установите XAMPP или WAMP
2. Найдите файл `php.ini`
3. Раскомментируйте строки:
   ```ini
   extension=imagick
   extension=zip
   extension=mbstring
   extension=gd
   ```

### 2. Настройка проекта

```bash
# 1. Скопируйте файлы в папку веб-сервера
cp -r watermark-master /var/www/html/

# 2. Создайте папку temp и установите права
mkdir -p temp
chmod 777 temp

# 3. Проверьте права на папку fonts
chmod 755 fonts
```

### 3. Настройка веб-сервера

#### Apache:
```apache
<VirtualHost *:80>
    ServerName watermark.local
    DocumentRoot /var/www/html/watermark-master
    
    <Directory /var/www/html/watermark-master>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx:
```nginx
server {
    listen 80;
    server_name watermark.local;
    root /var/www/html/watermark-master;
    index index.html index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 4. Проверка установки

1. **Откройте диагностический файл:**
   ```
   http://your-domain/test_watermark.php
   ```

2. **Проверьте все компоненты:**
   - ✅ ImageMagick установлен и работает
   - ✅ Папка temp доступна для записи
   - ✅ Шрифты загружаются корректно
   - ✅ Поддержка форматов PNG, JPG, WebP

## 🧪 Тестирование

### Основные тесты:

1. **Полная диагностика:**
   ```
   http://your-domain/test_watermark.php
   ```

2. **Тест перетаскивания:**
   ```
   http://your-domain/test_dragging.html
   ```

3. **Тест прозрачности:**
   ```
   http://your-domain/test_transparency.php
   ```

4. **Главное приложение:**
   ```
   http://your-domain/index.html
   ```

## 🔍 Устранение неполадок

### Ошибка "ImageMagick не установлен"
```bash
# Ubuntu/Debian
sudo apt install php-imagick
sudo systemctl restart apache2

# Проверка
php -m | grep imagick
```

### Ошибка "Папка temp недоступна"
```bash
# Создание папки и установка прав
mkdir -p temp
chmod 777 temp
chown www-data:www-data temp  # для Apache
```

### Ошибка "Шрифты не загружаются"
```bash
# Проверка папки fonts
ls -la fonts/

# Установка прав
chmod 755 fonts
chmod 644 fonts/*.ttf fonts/*.otf
```

### Ошибка "Файл не найден"
```bash
# Проверка структуры файлов
ls -la *.php *.html

# Проверка прав доступа
chmod 644 *.php *.html
```

### Проблемы с памятью
```bash
# Увеличение лимитов в php.ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

## 📁 Структура файлов

```
watermark-master/
├── index.html              # Главный интерфейс
├── process.php             # Обработчик изображений
├── fonts.css.php           # Генератор CSS шрифтов
├── get_fonts.php           # API для получения шрифтов
├── clean_temp.php          # Очистка временных файлов
├── test_watermark.php      # Диагностический файл
├── test_dragging.html      # Тест перетаскивания
├── test_transparency.php   # Тест прозрачности
├── fonts/                  # Папка со шрифтами
│   ├── arialmt.ttf
│   ├── RobotoCondensed-Regular.ttf
│   └── ...
├── temp/                   # Временная папка (создается автоматически)
├── README.md               # Документация
├── CHANGELOG.md            # Журнал изменений
└── INSTALL.md              # Этот файл
```

## 🔒 Безопасность

### Рекомендации:
1. **Ограничьте доступ к папке temp:**
   ```apache
   <Directory "/var/www/html/watermark-master/temp">
       Deny from all
   </Directory>
   ```

2. **Настройте лимиты загрузки:**
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   max_file_uploads = 10
   ```

3. **Используйте HTTPS:**
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

## 🚀 Производительность

### Оптимизация:
1. **Кэширование шрифтов:**
   ```apache
   <FilesMatch "\.(ttf|otf)$">
       ExpiresActive On
       ExpiresDefault "access plus 1 year"
   </FilesMatch>
   ```

2. **Сжатие изображений:**
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE image/png image/jpeg image/webp
   </IfModule>
   ```

3. **Очистка временных файлов:**
   ```bash
   # Добавьте в cron
   0 */6 * * * find /var/www/html/watermark-master/temp -type f -mtime +1 -delete
   ```

## 📞 Поддержка

### Полезные команды:
```bash
# Проверка PHP модулей
php -m

# Проверка версии ImageMagick
php -r "echo (new Imagick())->getVersion()['versionString'];"

# Проверка прав доступа
ls -la temp/ fonts/

# Очистка временных файлов
rm -rf temp/*
```

### Логи ошибок:
- **Apache:** `/var/log/apache2/error.log`
- **Nginx:** `/var/log/nginx/error.log`
- **PHP:** `/var/log/php_errors.log`

## ✅ Чек-лист установки

- [ ] PHP 7.4+ установлен
- [ ] Расширение imagick установлено
- [ ] Расширение zip установлено
- [ ] Расширение mbstring установлено
- [ ] Папка temp создана с правами 777
- [ ] Папка fonts содержит шрифты
- [ ] Веб-сервер настроен
- [ ] test_watermark.php показывает все ✅
- [ ] Главное приложение работает
- [ ] Загрузка и обработка изображений работает

## 🎉 Готово!

После выполнения всех шагов приложение готово к использованию!

**Главная страница:** `http://your-domain/index.html`
**Диагностика:** `http://your-domain/test_watermark.php`

---

*Мастер Водяных Знаков v2.0.0*
*Дата обновления: 30 июля 2025*