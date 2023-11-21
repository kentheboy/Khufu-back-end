php artisan down

echo -E "\033[1mDeploy inprogress...\033[0m"

echo -E "\033[1mgit checkout develop\033[0m"
git checkout develop

echo -E "\033[1mgit pull origin develop\033[0m"
git pull origin develop

echo -E "\033[1mcomposer install\033[0m"
composer install --no-dev --optimize-autoloader

echo -E "\033[1mphp artisan migrate --force\033[0m"
php artisan migrate --force

echo -E "\033[1mphp artisan config:cache\033[0m"
php artisan config:cache

echo -E "\033[1mphp artisan view:clear\033[0m"
php artisan view:clear

echo -E "\033[1mphp artisan cache:clear\033[0m"
php artisan cache:clear

php artisan up

echo -E "\033[1mDeploy work completed!\033[0m"