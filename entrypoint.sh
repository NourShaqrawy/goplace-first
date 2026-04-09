#!/bin/bash

# تأكيد الصلاحيات داخل الحاوية أثناء التشغيل
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# تنظيف الكاشات وتشغيل الهجرات
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# php artisan migrate
# php artisan mi:f --seed
php artisan storage:link 



# تشغيل Apache في المقدمة
exec apache2-foreground
