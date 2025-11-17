#!/bin/bash

echo "🧹 Limpiando escenario de demostración CU-09..."
echo ""

# Resetear estados de CC a "Activa"
./vendor/bin/sail artisan db:seed --class=ResetEscenarioCU09Seeder

# Limpiar cola de jobs
echo ""
echo "🗑️ Limpiando cola de jobs..."
./vendor/bin/sail mysql -e "DELETE FROM laravel.jobs;" > /dev/null 2>&1

# Limpiar auditorías de la demo anterior (opcional)
echo "🗑️ Limpiando auditorías del CU-09..."
./vendor/bin/sail mysql -e "DELETE FROM laravel.auditorias WHERE accion = 'MODIFICAR_ESTADO_CC' AND DATE(created_at) = CURDATE();" > /dev/null 2>&1

echo ""
echo "✅ Escenario listo para nueva demostración!"
echo ""
echo "🚀 Ejecuta ahora: sail artisan cuentas:check-vencidas"
