#!/bin/bash

echo "🧹 Limpiando demo CU-09..."

./vendor/bin/sail artisan db:seed --class=ResetEscenarioCU09Seeder

./vendor/bin/sail mysql laravel -e "TRUNCATE TABLE jobs;" 2>/dev/null
./vendor/bin/sail mysql laravel -e "DELETE FROM auditorias WHERE DATE(created_at) = CURDATE();" 2>/dev/null
./vendor/bin/sail mysql laravel -e "DELETE FROM notificaciones_whatsapp WHERE DATE(created_at) = CURDATE();" 2>/dev/null

echo ""
echo "✅ Listo! Ejecuta: ./vendor/bin/sail artisan db:seed --class=DemoCU09Seeder"
