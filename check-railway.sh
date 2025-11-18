#!/bin/bash
# Check Railway deployment status and get environment variables

cd backend/install

echo "=== Railway Project Status ==="
railway status

echo ""
echo "=== Checking PUSHER Configuration ==="
railway run bash -c 'php artisan tinker --execute="echo \"PUSHER_APP_KEY: \" . config(\"broadcasting.connections.pusher.key\"); echo PHP_EOL; echo \"PUSHER_APP_CLUSTER: \" . config(\"broadcasting.connections.pusher.options.cluster\"); echo PHP_EOL; echo \"PUSHER_APP_ID: \" . config(\"broadcasting.connections.pusher.app_id\");"'

echo ""
echo "=== Checking STRIPE Configuration ==="
railway run bash -c 'php artisan tinker --execute="echo \"STRIPE_KEY: \" . (config(\"services.stripe.key\") ? \"SET\" : \"NOT SET\"); echo PHP_EOL; echo \"STRIPE_SECRET: \" . (config(\"services.stripe.secret\") ? \"SET\" : \"NOT SET\");"'

echo ""
echo "=== Checking DATABASE ==="
railway run bash -c 'php artisan tinker --execute="echo \"DB Connection: \" . config(\"database.default\"); echo PHP_EOL; echo \"DB Host: \" . config(\"database.connections.pgsql.host\");"'

echo ""
echo "=== Checking Recent Errors in Laravel Logs ==="
railway run bash -c 'tail -100 storage/logs/laravel.log | grep -i "error\|exception" | tail -20' || echo "No recent errors found"

echo ""
echo "=== Checking Master API Response ==="
railway run bash -c 'php artisan tinker --execute="echo json_encode(app(\"App\\\\Http\\\\Controllers\\\\API\\\\MasterController\")->index()->getData(), JSON_PRETTY_PRINT);"' | grep -A5 "pusher"
