# tecnosoluciones
Sistema de gestión comercial y técnica

---

Este proyecto utiliza:
- Laravel Sail 
- Laravel Breeze + Vue + Inertia
- MySQL, Redis, Mailpit
- Vite para el frontend

> El objetivo es mantener un entorno reproducible y fácil de montar sin depender de herramientas locales más allá de Docker.

# Preparar entorno de desarrollo

## 1. Instalar dependencias PHP (`composer install`)

Si ya tenés **composer** instalado localmente:

```bash
composer install
```

### Si NO tenés PHP ni Composer instalado

Podés instalarlas desde un contenedor:
```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(pwd)":/var/www/html \
  -w /var/www/html \
  composer install --ignore-platform-reqs
```

> Importante: El directorio vendor/ debe existir en el host. Como Sail monta la carpeta del proyecto dentro del contenedor, si el host no tiene vendor/, el contenedor tampoco lo tendrá.

## 2. Crear archivo de variables de entorno

Si el proyecto aún no tiene .env, podes tomar el .env.example que está disponible en la raíz del repo. Luego generar la App Key:
```bash
./vendor/bin/sail artisan key:generate
```

## 3. Construir e iniciar contenedores (Sail)

### Construir (solo la primera vez o tras cambios al Dockerfile)
```bash
./vendor/bin/sail build --no-cache
```

### Iniciar contenedores (uso diario)

```bash
./vendor/bin/sail up -d
```

## 4. Ejecutar migraciones
```bash
./vendor/bin/sail artisan migrate
```

## 5. Iniciar servidor de Vite (Vue + HMR)
Necesario para que Vue renderice componentes y habilitar la recarga en caliente (HMR):
```bash
./vendor/bin/sail npm run dev
```

# Herramientas

| Servicio                           | URL / Host                                       | Notas                                        |
| ---------------------------------- | ------------------------------------------------ | -------------------------------------------- |
| **Aplicación Web**                 | [http://localhost:65080](http://localhost:65080) | Requiere Vite corriendo en `localhost:65173` |
| **Base de Datos MySQL**            | `mysql:3306`                                     | Acceso desde contenedores                    |
| **Cliente Web de BD (PHPMyAdmin)** | [http://localhost:65081](http://localhost:65081) | Listo para consultar la BD del proyecto      |
| **Redis (opcional)**               | `localhost:65037`                                | No esencial                                  |
| **Mailpit - SMTP**                 | `localhost:65003`                                | Para pruebas de correo                       |
| **Mailpit - Dashboard**            | [http://localhost:65013](http://localhost:65013) | Ver correos enviados                         |

# Flujo de desarrollo típico

1. Pre-requisitos
```bash
# Iniciar contenedores
./vendor/bin/sail up -d

# Iniciar server Vue+Vite
./vendor/bin/sail npm run dev
```

2. Editar código (Laravel + Vue)

3. (Opcional) `./vendor/bin/sail artisan migrate` (si hay cambios en BD)
