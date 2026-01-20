# 📧 Sistema de Email para Solicitudes de Cotización

##  Archivos Creados

### 1. **Job de Email** (`app/Jobs/EnviarSolicitudCotizacionEmail.php`)
- Envía emails asíncronos con Magic Link
- Reintentos automáticos: 3 intentos (1min, 5min, 15min)
- Tracking de envío y errores
- Soporte para recordatorios

### 2. **Mailable** (`app/Mail/SolicitudCotizacionMail.php`)
- Email profesional con diseño moderno
- Muestra productos solicitados en tabla
- Botón de CTA para responder
- Información de vencimiento
- Compatible con recordatorios

### 3. **Vista de Email** (`resources/views/emails/solicitud-cotizacion.blade.php`)
- Diseño responsive y profesional
- Gradientes modernos (púrpura/azul)
- Tabla de productos
- Botón destacado con Magic Link
- Compatible con clientes de email (Gmail, Outlook, etc.)

### 4. **Commands**
- `app/Console/Commands/CerrarCotizacionesVencidas.php`: Cierra cotizaciones vencidas
- `app/Console/Commands/EnviarRecordatoriosCotizacion.php`: Envía recordatorios automáticos

### 5. **Migraciones**
- `2026_01_19_000002_add_email_tracking_to_cotizaciones_proveedores.php`
  * `enviado_email` (boolean)
  * `fecha_envio_email` (datetime)
  * `error_envio_email` (text)

- `2026_01_19_000003_add_fecha_recordatorio_to_cotizaciones_proveedores.php`
  * `fecha_recordatorio` (datetime)

### 6. **Scheduler Configurado** (`routes/console.php`)
- **00:00** - Cerrar cotizaciones vencidas
- **09:00** - Enviar recordatorios automáticos (Email + WhatsApp)

---

##  Cómo Probar el Sistema

### 1️ Asegurar que las colas están corriendo

```bash
# Terminal 1: Iniciar worker de colas
./vendor/bin/sail artisan queue:work --tries=3 --timeout=60

# O en background con supervisor (producción)
./vendor/bin/sail artisan queue:listen
```

### 2️ Crear una Solicitud de Cotización Manual

```bash
# Opción A: Desde la UI web
# 1. Ir a /solicitudes-cotizacion/crear
# 2. Agregar productos
# 3. Seleccionar proveedores (asegurar que tengan EMAIL válido)
# 4. Enviar

# Opción B: Desde Tinker (testing)
./vendor/bin/sail artisan tinker
```

```php
// En Tinker:
use App\Models\SolicitudCotizacion;
use App\Models\Proveedor;
use App\Jobs\EnviarSolicitudCotizacionEmail;

// Buscar una solicitud existente
$solicitud = SolicitudCotizacion::with('cotizacionesProveedores')->first();

// Buscar una cotización de proveedor
$cotizacion = $solicitud->cotizacionesProveedores->first();

// Despachar el Job manualmente
EnviarSolicitudCotizacionEmail::dispatch($cotizacion);

// Ver en el log si se encola
exit;
```

### 3️ Ver el Email en Mailpit

```bash
# Mailpit ya está corriendo con Sail, acceder en:
# http://localhost:8025

# Verás:
# - Asunto: "📋 Solicitud de Cotización #XXX"
# - De: info@tecnosoluciones.com (según .env)
# - Para: email del proveedor
# - Contenido: Email HTML con productos y botón
```

### 4️ Testear Recordatorios

```bash
# Enviar recordatorios manualmente
./vendor/bin/sail artisan cotizaciones:enviar-recordatorios --canal=email

# Ver resultados
# - Revisa Mailpit
# - Revisa logs: storage/logs/laravel.log
```

### 5️ Testear Cierre Automático

```bash
# Cerrar cotizaciones vencidas manualmente (sin confirmación)
./vendor/bin/sail artisan cotizaciones:cerrar-vencidas --force

# Ver en la DB:
echo "SELECT codigo_solicitud, fecha_vencimiento, estado_id FROM solicitudes_cotizacion WHERE estado_id = (SELECT id FROM estados_solicitudes WHERE nombre = 'Cerrada');" | ./vendor/bin/sail mysql
```

---

##  Flujo Completo

### Envío Inicial

1. Usuario crea solicitud manual o sistema detecta bajo stock
2. Se crea `SolicitudCotizacion` con `CotizacionProveedor` para cada proveedor
3. `SolicitudCotizacionService::enviarSolicitud()` despacha:
   - `EnviarSolicitudCotizacionEmail::dispatch($cotizacion)` ✉️
   - `EnviarSolicitudCotizacionWhatsApp::dispatch($cotizacion)` 📱
4. Jobs se ejecutan en background (queue worker)
5. Se actualiza `enviado_email = true` y `fecha_envio_email`

### Recordatorios (Día 3 y Día 5)

1. **09:00** - Cron ejecuta `cotizaciones:enviar-recordatorios`
2. Command busca cotizaciones sin respuesta
3. Aplica reglas:
   - Día 3 después de envío + quedan 3-4 días = Primer recordatorio
   - Día 5 después de envío + quedan 1-2 días = Segundo recordatorio
4. Despacha Jobs con `esRecordatorio: true`
5. Actualiza `fecha_recordatorio`

### Cierre Automático

1. **00:00** - Cron ejecuta `cotizaciones:cerrar-vencidas --force`
2. Command busca solicitudes con `fecha_vencimiento < NOW()`
3. Marca estado como "Cerrada"
4. Marca cotizaciones pendientes como "No Respondió"

---

##  Monitoreo

### Ver Jobs en Cola

```bash
# Ver trabajos pendientes
./vendor/bin/sail artisan queue:monitor

# Ver trabajos fallidos
./vendor/bin/sail artisan queue:failed

# Reintentar trabajos fallidos
./vendor/bin/sail artisan queue:retry all
```

### Ver Logs

```bash
# Logs de Laravel (incluye envíos y errores)
tail -f storage/logs/laravel.log | grep -E "(Email|WhatsApp|Cotización)"

# Ver solo recordatorios
tail -f storage/logs/laravel.log | grep "Recordatorio"
```

### Verificar en DB

```sql
-- Ver cotizaciones con tracking de email
SELECT 
    cp.id,
    p.razon_social,
    sc.codigo_solicitud,
    cp.enviado_email,
    cp.fecha_envio_email,
    cp.fecha_recordatorio,
    cp.estado_envio
FROM cotizaciones_proveedores cp
JOIN proveedores p ON cp.proveedor_id = p.id
JOIN solicitudes_cotizacion sc ON cp.solicitud_id = sc.id
WHERE cp.enviado_email = 1
ORDER BY cp.fecha_envio_email DESC;
```

---

## 🛠️ Troubleshooting

### ❌ Los emails no se envían

**Problema:** Jobs despachados pero no llegan a Mailpit

**Solución:**
```bash
# 1. Verificar que el worker esté corriendo
ps aux | grep "queue:work"

# 2. Si no está, iniciarlo
./vendor/bin/sail artisan queue:work

# 3. Verificar configuración de mail
./vendor/bin/sail artisan tinker
config('mail.mailers.smtp.host');  // Debe ser 'mailpit'
config('mail.mailers.smtp.port');  // Debe ser 1025
```

### ❌ Proveedor sin email

**Problema:** Log muestra "Proveedor sin email"

**Solución:**
```bash
# Agregar email al proveedor en la DB
echo "UPDATE proveedores SET email = 'proveedor@example.com' WHERE id = 1;" | ./vendor/bin/sail mysql
```

### ❌ Error "Class SolicitudCotizacionMail not found"

**Problema:** Composer no encuentra las clases nuevas

**Solución:**
```bash
# Regenerar autoload
./vendor/bin/sail composer dump-autoload

# Limpiar cache de Laravel
./vendor/bin/sail artisan optimize:clear
```

### ❌ Recordatorios no se envían

**Problema:** Command no encuentra cotizaciones

**Solución:**
```bash
# Debug: Ver qué cotizaciones califica
./vendor/bin/sail artisan tinker
```

```php
use App\Models\CotizacionProveedor;

$cotizaciones = CotizacionProveedor::whereIn('estado_envio', ['Enviado'])
    ->whereHas('solicitud', function($q) {
        $q->where('fecha_vencimiento', '>', now());
    })
    ->whereNull('fecha_recordatorio')
    ->get();

$cotizaciones->each(function($c) {
    $diasDesdeEnvio = now()->diffInDays($c->fecha_envio);
    $diasParaVencer = now()->diffInDays($c->solicitud->fecha_vencimiento, false);
    echo "Proveedor: {$c->proveedor->razon_social} | Días desde envío: {$diasDesdeEnvio} | Días para vencer: {$diasParaVencer}\n";
});
```

---

## 🚀 Poner en Producción

### 1. Configurar variables de entorno

```env
# .env en producción
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com  # O tu proveedor
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@tecnosoluciones.com
MAIL_FROM_NAME="TecnoSoluciones"

QUEUE_CONNECTION=database
```

### 2. Configurar Supervisor para Queue Workers

```ini
# /etc/supervisor/conf.d/tecnosoluciones-worker.conf
[program:tecnosoluciones-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Recargar supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tecnosoluciones-worker:*
```

### 3. Configurar Cron

```bash
# Editar crontab
crontab -e

# Agregar esta línea (ejecuta el scheduler cada minuto)
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## ✨ Características Implementadas

✅ Envío dual (Email + WhatsApp) con un solo comando
✅ Emails profesionales con diseño moderno
✅ Magic Link integrado (sin necesidad de login)
✅ Reintentos automáticos (3 intentos con backoff)
✅ Tracking completo de envíos (email, whatsapp, recordatorios)
✅ Recordatorios automáticos (día 3 y día 5)
✅ Cierre automático de cotizaciones vencidas
✅ Commands para gestión manual cuando sea necesario
✅ Logs detallados para monitoreo
✅ Compatible con Mailpit (desarrollo) y SMTP real (producción)

---

## 📝 Notas Importantes

1. **Ambos canales funcionan independientemente**: Si falla WhatsApp, el email igual se envía
2. **Sin dependencias de comandos manuales**: Todo es automático via Scheduler
3. **Graceful degradation**: Si un proveedor no tiene email, solo se envía WhatsApp
4. **Idempotencia**: Recordatorios no se duplican gracias a `fecha_recordatorio`
5. **Testeable**: Mailpit permite ver emails sin enviarlos realmente
