web: cd "Ready eCommerce-Admin with Customer Website/install" && php artisan serve --host=0.0.0.0 --port=$PORT
worker: cd "Ready eCommerce-Admin with Customer Website/install" && php artisan queue:work redis --sleep=3 --tries=3
