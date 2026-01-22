# Módulo de Compras - Estado de Implementación

##  Resumen Ejecutivo

El módulo de compras está **completamente implementado** según los requerimientos. Incluye:

- ✅ **CU-20**: Solicitud de Cotización (automático + manual)
- ✅ **CU-21**: Evaluación y Selección de Ofertas  
- ✅ **CU-22**: Generación de Orden de Compra
- ✅ **CU-23**: Recepción de Mercadería
- ✅ **CU-24**: Consulta de Órdenes de Compra

---

##  Flujo Completo del Proceso Automático

### 1. Detección de Necesidades (MonitoreoStockService)

```
 Productos con stock < stock_mínimo
    +
 Productos con alta rotación (ventas en 30 días)
    ↓
 Lista de productos que necesitan reposición (sin duplicados)
```

**Archivo**: `app/Services/Compras/MonitoreoStockService.php`

### 2. Generación y Envío de Solicitudes

```
 Productos necesitados
    ↓
 Selección de proveedores por producto/categoría
    ↓
 Envío DUAL CHANNEL:
   - WhatsApp (Twilio)
   - Email (Mailpit/SMTP)
    ↓
 Magic Link único por proveedor (UUID)
```

**Archivos**:
- `app/Services/Compras/SolicitudCotizacionService.php`
- `app/Jobs/EnviarSolicitudCotizacionWhatsApp.php`
- `app/Jobs/EnviarSolicitudCotizacionEmail.php`

### 3. Portal del Proveedor (Sin Login)

```
 URL: /portal/cotizacion/{token}
    ↓
 Proveedor identificado automáticamente por token
    ↓
 Formulario para responder:
   - Precio unitario por producto
   - Cantidad disponible
   - Plazo de entrega (días)
   - Observaciones
    ↓
✅ Enviar cotización / ❌ Rechazar
```

**Archivos**:
- `app/Http/Controllers/Portal/PortalProveedorController.php`
- `resources/js/Pages/Portal/Cotizacion.vue`
- `resources/js/Pages/Portal/Agradecimiento.vue`
- `resources/js/Pages/Portal/Error.vue`

### 4. Cierre Automático y Timeout

```
 Scheduler diario (00:00)
    ↓
 Buscar solicitudes con fecha_vencimiento < hoy
    ↓
 Marcar como "Vencida"
```

**Archivos**:
- `app/Console/Commands/CerrarSolicitudesVencidasCommand.php`
- `routes/console.php` (Scheduler configurado)

### 5. Ranking de Ofertas

```
 Solicitud con respuestas
    ↓
🏆 Ranking automático por:
   1. Precio total (menor a mayor)
   2. Plazo de entrega
   3. Productos cotizados completos
    ↓
 Vista comparativa para selección
```

**Archivos**:
- `app/Services/Compras/SolicitudCotizacionService.php` → `obtenerRankingOfertas()`
- `resources/js/Pages/Compras/SolicitudesCotizacion/Show.vue`
- `resources/js/Pages/Compras/Ofertas/Comparar.vue`

---

## 📁 Estructura de Archivos del Módulo

### Controllers
```
app/Http/Controllers/
├── Portal/
│   └── PortalProveedorController.php    # CU-20: Portal público proveedor
├── SolicitudCotizacionController.php    # CU-20: Solicitudes de cotización
├── OfertaCompraController.php           # CU-21: Evaluación de ofertas
├── OrdenCompraController.php            # CU-22: Órdenes de compra
└── RecepcionMercaderiaController.php    # CU-23: Recepción mercadería
```

### Services
```
app/Services/Compras/
├── MonitoreoStockService.php            # Detección productos bajo stock
├── SolicitudCotizacionService.php       # Gestión de solicitudes
├── RegistrarOfertaService.php           # Procesamiento de ofertas
├── GenerarOrdenCompraService.php        # Generación de OC
└── RecepcionarMercaderiaService.php     # Recepción con stock update
```

### Jobs
```
app/Jobs/
├── EnviarSolicitudCotizacionWhatsApp.php   # WhatsApp via Twilio
├── EnviarSolicitudCotizacionEmail.php      # Email via Laravel Mail
└── EnviarOrdenCompraWhatsApp.php           # Envío de OC
```

### Commands (Scheduler)
```
app/Console/Commands/
├── CerrarSolicitudesVencidasCommand.php    # Cierre automático
├── EnviarRecordatoriosCotizacionCommand.php # Recordatorios
└── MonitorearStockCommand.php              # Monitoreo diario
```

### Vistas Vue
```
resources/js/Pages/
├── Portal/
│   ├── Cotizacion.vue                   # Formulario proveedor
│   ├── Agradecimiento.vue               # Post-respuesta
│   └── Error.vue                        # Errores de acceso
└── Compras/
    ├── SolicitudesCotizacion/
    │   ├── Index.vue                    # Listado solicitudes
    │   ├── Create.vue                   # Nueva solicitud
    │   └── Show.vue                     # Detalle + Ranking
    ├── Ofertas/
    │   ├── Index.vue                    # Listado ofertas
    │   └── Comparar.vue                 # Comparativa
    ├── Ordenes/
    │   ├── Index.vue                    # Listado OC
    │   └── Show.vue                     # Detalle OC
    └── Recepciones/
        ├── Index.vue                    # OC pendientes recepción
        ├── Create.vue                   # Registrar recepción
        ├── Show.vue                     # Detalle recepción
        └── Historial.vue                # Recepciones pasadas
```

---

##  Tests Creados

### Feature Tests
```
tests/Feature/Compras/
├── SolicitudCotizacionTest.php
│   ├── test_puede_crear_solicitud_cotizacion
│   ├── test_cotizacion_proveedor_tiene_token_unico
│   ├── test_portal_proveedor_accesible_por_token
│   ├── test_portal_rechaza_token_invalido
│   ├── test_portal_rechaza_solicitud_vencida
│   ├── test_proveedor_puede_responder_cotizacion
│   ├── test_comando_cierra_solicitudes_vencidas
│   └── test_ranking_ofertas_ordenado_por_precio
│
└── RecepcionMercaderiaTest.php
    ├── test_recepcion_total_actualiza_stock_y_estado_oc
    ├── test_recepcion_parcial_mantiene_oc_pendiente
    ├── test_multiples_recepciones_parciales
    ├── test_recepcion_no_puede_exceder_cantidad_pedida
    ├── test_recepcion_registra_auditoria
    └── test_index_muestra_oc_pendientes_recepcion
```

### Unit Tests
```
tests/Unit/Services/Compras/
└── MonitoreoStockServiceTest.php
    ├── test_detecta_productos_bajo_stock_minimo
    ├── test_detecta_productos_alta_rotacion
    ├── test_detecta_productos_necesitan_reposicion_sin_duplicados
    ├── test_ignora_productos_sin_stock
    └── test_ignora_productos_inactivos
```

---

## ⚙️ Configuración del Scheduler

**Archivo**: `routes/console.php`

| Comando | Horario | Descripción |
|---------|---------|-------------|
| `stock:monitorear --generar --enviar` | 08:00 | Detecta productos y envía solicitudes |
| `cotizaciones:cerrar-vencidas` | 00:00 | Cierra solicitudes vencidas |
| `cotizaciones:enviar-recordatorios --canal=ambos` | 09:00 | Recordatorios a proveedores |

---

## 🔐 Rutas del Portal (Sin Autenticación)

```php
// routes/web.php
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/cotizacion/{token}', [PortalProveedorController::class, 'mostrarCotizacion'])->name('cotizacion');
    Route::post('/cotizacion/{token}/responder', [PortalProveedorController::class, 'responderCotizacion'])->name('cotizacion.responder');
    Route::post('/cotizacion/{token}/rechazar', [PortalProveedorController::class, 'rechazarCotizacion'])->name('cotizacion.rechazar');
});
```

---

## 📧 Configuración de Canales

### WhatsApp (Twilio)
```env
TWILIO_SID=your_sid
TWILIO_TOKEN=your_token
TWILIO_WHATSAPP_FROM=+14155238886
```

### Email (Mailpit para desarrollo)
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=sistema@tecnosoluciones.com
```

---

## ✅ Checklist de Cumplimiento (Profesor)

| Requerimiento | Estado | Implementación |
|---------------|--------|----------------|
| Detectar productos bajo stock | ✅ | `MonitoreoStockService::detectarProductosBajoStock()` |
| Detectar productos alta rotación | ✅ | `MonitoreoStockService::detectarProductosAltaRotacion()` |
| Generar lista preliminar | ✅ | `detectarProductosNecesitanReposicion()` |
| Envío automático WhatsApp | ✅ | `EnviarSolicitudCotizacionWhatsApp` Job |
| Envío automático Email | ✅ | `EnviarSolicitudCotizacionEmail` Job |
| URL única por proveedor | ✅ | Magic Link con UUID (`token_unico`) |
| Portal sin login | ✅ | Rutas públicas en `/portal/` |
| Proveedor responde qué tiene | ✅ | Formulario con precio, cantidad, plazo |
| Timeout en solicitudes | ✅ | `fecha_vencimiento` + comando de cierre |
| Cierre automático | ✅ | `CerrarSolicitudesVencidasCommand` |
| Ranking de ofertas | ✅ | `obtenerRankingOfertas()` ordenado por precio |
| Comparativa visual | ✅ | Vista `Compras/Ofertas/Comparar.vue` |

---

## 📝 Notas Adicionales

1. **Sin Hardcoding**: Todos los estados, filtros y configuraciones vienen de la BD.

2. **Paleta de Colores**: El módulo usa indigo-600/700/800 para mantener consistencia.

3. **Auditoría**: Todas las operaciones críticas registran en la tabla `auditorias`.

4. **Transacciones**: Operaciones complejas usan `DB::transaction()` para integridad.

5. **Cola de Jobs**: Los envíos de WhatsApp/Email se procesan en background para no bloquear.

---

*Última actualización: $(date)*
*Módulo: Compras (CU-20 a CU-24)*
