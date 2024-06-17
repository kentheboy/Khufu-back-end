php artisan down
echo "Deploy inprogress..."

echo "git checkout ."
git checkout .

echo "git pull"
git pull

echo "composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader

echo "php artisan migrate --force"
php artisan migrate --force

echo "php artisan optimize"
php artisan optimize

echo "sudo php artisan config:cache \n sudo php artisan config:clear"
sudo php artisan config:cache
sudo php artisan config:clear

echo "php artisan view:clear"
php artisan view:clear

echo "php artisan cache:clear"
php artisan cache:clear

php artisan up
echo "Deploy work completed!\n"