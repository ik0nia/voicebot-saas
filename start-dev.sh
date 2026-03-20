#!/bin/bash
pkill -f vite 2>/dev/null
pkill -f "artisan serve" 2>/dev/null
cd /var/www/voicebot-saas
npm run build
php artisan config:clear
php artisan view:clear
php artisan cache:clear
nohup php artisan serve --host=0.0.0.0 --port=8001 > /tmp/laravel-dev.log 2>&1 &
echo "Dev server pornit pe http://185.104.181.113:8001"
