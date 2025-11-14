web: cd backend/install && php artisan serve --host=0.0.0.0 --port=$PORT
worker: cd backend/install && php artisan queue:work redis --sleep=3 --tries=3
