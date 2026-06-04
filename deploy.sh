git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
pnpm install --production

php artisan optimize
npm run build