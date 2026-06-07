# Mina - Agenda SaaS para Peluquerías y Barberías

Sistema de gestión de citas multi-tenant basado en [Easy Appointments](https://github.com/alextselegidis/easyappointments).

## Características

- **Multi-tenancy**: Múltiples negocios en una sola instancia
- **Planes**: Basic, Pro, Enterprise con límites diferenciados
- **API REST**: JWT authentication
- **Dockerizado**: Listo para desplegar en VPS

## Stack Tecnológico

- PHP 8.2 / CodeIgniter 3
- MySQL 8.0
- Redis (sesiones y cache)
- nginx como proxy inverso
- Docker + docker-compose

## Modelos de Negocio

| Plan | Precio | Límites |
|------|--------|---------|
| Basic | $9/mes | 1 proveedor, 50 citas/mes |
| Pro | $19/mes | 3 proveedores, 500 citas/mes, webhooks |
| Enterprise | $49/mes | Proveedores ilimitados, API, soporte |

## Requisitos

- Docker 20.10+
- Docker Compose 2.0+
- 2 vCPU / 4GB RAM
- 40GB storage + 10GB volume

## Instalación Local (Desarrollo)

```bash
# Clonar repositorio
git clone https://github.com/JorgeObreque/mina.git
cd mina

# Copiar configuración
cp .env.example .env

# Editar .env con tus valores
nano .env

# Iniciar contenedores
docker-compose up -d

# Importar esquema de base de datos
docker-compose exec mysql mysql -u root -p agenda_saas < migrations/001_tenant.sql
```

## Despliegue en Producción (VPS)

```bash
# En el servidor VPS
cd /var/www/mina

# Clonar o actualizar repositorio
git pull origin main

# Copiar y configurar variables
cp .env.example .env
nano .env  # Completar valores reales

# Montar volumes externos
mkdir -p /mnt/volume/{mysql,redis,storage}

# Iniciar servicios
docker-compose build
docker-compose up -d
```

## Variables de Entorno

```env
APP_ENV=production
APP_URL=https://tu-dominio.com
DB_HOST=mysql
DB_NAME=agenda_saas
DB_USER=agenda_user
DB_PASS=tu_password_seguro
MYSQL_ROOT_PASSWORD=tu_root_password
REDIS_HOST=redis
REDIS_PASSWORD=tu_redis_password
JWT_SECRET=tu_jwt_secret_largo_y_seguro
JWT_TTL=3600
```

## API Endpoints

### Autenticación

```
POST /api/v1/tenants/register - Registrar nuevo tenant
GET /api/v1/tenants          - Obtener info del tenant actual
GET  /api/v1/tenants/settings - Obtener settings del tenant
PUT  /api/v1/tenants/settings - Actualizar settings
GET  /api/v1/tenants/plan     - Obtener plan y uso actual
```

### Recursos (requieren JWT)

```
GET    /api/v1/appointments - Listar citas
POST   /api/v1/appointments - Crear cita
GET    /api/v1/services     - Listar servicios
POST   /api/v1/services     - Crear servicio
GET    /api/v1/providers   - Listar proveedores
POST   /api/v1/providers   - Crear proveedor
GET    /api/v1/customers    - Listar clientes
POST   /api/v1/customers    - Crear cliente
```

## Deployment Flow

```
Desarrollador → GitHub (push) → SSH a VPS → git pull → docker-compose up -d
```

## Estructura de Volumes

```
/mnt/volume/
├── mysql/       # Datos MySQL
├── redis/       # Datos Redis
└── storage/     # Uploads y archivos
```

## Seguridad

- SSL/TLS obligatorio (Let's Encrypt)
- JWT con expiración
- Rate limiting por tenant
- Variables sensibles en .env (fuera del repo)

## Licencia

Propiedad de Jorge Obreque. Todos los derechos reservados.
