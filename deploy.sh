php artisan down
echo "Deploy inprogress...\n"
git checkout develop
git pull origin develop
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan view:clear
php artisan cache:clear
php artisan up
echo "Deploy work completed!\n"