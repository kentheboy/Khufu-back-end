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

echo "php artisan config:cache \n php artisan config:clear"
php artisan config:cache
php artisan config:clear

echo "php artisan view:clear"
php artisan view:clear

echo "php artisan cache:clear"
php artisan cache:clear

php artisan up
echo "Deploy work completed!\n"