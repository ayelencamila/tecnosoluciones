#!/bin/bash

echo "🧹 Limpiando escenario de demostración CU-09..."
echo ""

# Resetear estados de CC a "Activa"
./vendor/bin/sail artisan db:seed --class=ResetEscenarioCU09Seeder

# Limpiar cola de jobs
echo ""
echo "🗑️ Limpiando cola de jobs..."
./vendor/bin/sail mysql laravel -e "TRUNCATE TABLE jobs;" 2>/dev/null
echo "   ✓ Cola limpia"

# Limpiar auditorías de la demo
echo ""
echo "🗑️ Limpiando auditorías del CU-09..."
./vendor/bin/sail mysql laravel -e "DELETE FROM auditorias WHERE DATE(created_at) = CURDATE() AND accion LIKE '%CC%';" 2>/dev/null
echo "   ✓ Auditorías limpiadas"

# Limpiar notificaciones WhatsApp de la demo
echo ""
echo "🗑️ Limpiando notificaciones de la demo..."
./vendor/bin/sail mysql laravel -e "DELETE FROM notificaciones_whatsapp WHERE DATE(created_at) = CURDATE() AND destinatario LIKE '%99999999%';" 2>/dev/null
echo "   ✓ Notificaciones limpiadas"

echo ""
echo "✅ Escenario listo para nueva demostración!"
echo ""
echo "🚀 Ejecuta ahora: sail artisan db:seed --class=DemoCU09Seeder && sail artisan cc:check-vencimientos"
