# 📁 Estructura del Proyecto

Comprende cómo está organizado el proyecto y dónde agregar tu código.

---

## 🏗️ Árbol de Directorios

```
modernPhpDevStack/
│
├── .env                          # Variables de entorno (NO COMMITEAR)
├── .env.example                  # Plantilla .env para referencia
├── .gitignore                    # Archivos ignorados por git
├── .git/                         # Repositorio git
├── docker-compose.yml            # Orquestación de contenedores
├── README.md                     # README principal del proyecto
│
├── docs/                         # 📚 DOCUMENTACIÓN (estás aquí)
│   ├── README.md                # Índice de documentación
│   ├── 01-QUICKSTART.md         # Inicio rápido
│   ├── 02-PROJECT-STRUCTURE.md  # Estructura (este archivo)
│   ├── 03-XDEBUG-DEBUGGING.md   # Debugging
│   ├── 04-SECRETS-MANAGEMENT.md # Gestión de secretos
│   ├── 05-TIMEZONE-CHARSET.md   # Timezone y charset
│   ├── 06-FRONTEND-ASSETS.md    # Assets con Node.js
│   ├── 07-NGINX-APACHE.md       # Nginx vs Apache
│   ├── 08-MAILPIT-TESTING.md    # Testing de emails
│   ├── 09-DATABASE-MANAGEMENT.md# Gestión de BD
│   └── 10-TROUBLESHOOTING.md    # Troubleshooting
│
├── src/                          # 🚀 CÓDIGO DE APLICACIÓN (AQUÍ VA TU CÓDIGO)
│   ├── index.php                # Dashboard principal
│   ├── test.php                 # Script de test (ejemplo)
│   ├── api/                     # APIs REST (opcional)
│   ├── views/                   # Templates HTML (opcional)
│   ├── models/                  # Clases de base de datos (opcional)
│   ├── controllers/             # Controladores (opcional)
│   ├── config/                  # Archivos de configuración
│   │   ├── database.php         # Config de BD
│   │   └── app.php              # Config general
│   ├── public/                  # Assets estáticos públicos
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── composer.json            # Dependencias PHP (si usas Composer)
│
├── docker/                       # 🐳 CONFIGURACIÓN DOCKER
│   ├── php/
│   │   ├── Dockerfile           # Imagen PHP personalizada
│   │   ├── php.ini              # Configuración PHP
│   │   └── xdebug.ini           # Configuración Xdebug
│   ├── nginx/
│   │   └── default.conf         # Configuración Nginx
│   ├── apache/
│   │   └── httpd.conf           # Configuración Apache
│   ├── mariadb/
│   │   └── my.cnf               # Configuración MariaDB (charset, collation)
│   └── ssl/
│       ├── server.crt           # Certificado SSL (NO COMMITEAR)
│       └── server.key           # Clave privada SSL (NO COMMITEAR)
│
├── .secrets/                     # 🔐 SECRETOS (NO COMMITEAR)
│   ├── db_password.txt          # Contraseña usuario BD
│   └── db_root_password.txt     # Contraseña root BD
│
├── .vscode/                      # ⚙️ CONFIGURACIÓN VS CODE
│   └── launch.json              # Configuración Xdebug debugging
│
└── node_modules/                # 📦 DEPENDENCIAS NODE (SI USAS NPM)
    # (Generado por npm install)

```

---

## 📝 Descripción de Directorios Principales

### 🚀 `/src` - Código de la Aplicación

**Este es el directorio más importante**. Aquí va:
- Código PHP de tu aplicación
- Configuraciones específicas
- Assets (CSS, JS, imágenes)
- Modelos, controladores, vistas (si usas patrón MVC)

Ejemplo de estructura MVC dentro de `src/`:

```
src/
├── index.php              # Punto de entrada
├── .htaccess              # Reescrituras URL (Apache)
├── composer.json          # (Opcional) Dependencias Composer
│
├── public/                # Assets públicos
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── images/
│
├── app/
│   ├── Controllers/       # Controladores
│   │   └── HomeController.php
│   ├── Models/            # Modelos de BD
│   │   └── User.php
│   ├── Views/             # Templates
│   │   └── home.php
│   └── Config/            # Configuración
│       ├── Database.php
│       └── App.php
│
└── tests/                 # Tests unitarios (opcional)
    └── Feature/
```

**Sincronización**: El directorio `src/` está montado en todos los contenedores en `/var/www/html`.

---

### 🐳 `/docker` - Configuración Docker

Contiene las configuraciones de cada servicio:

#### `docker/php/Dockerfile`
- Define la imagen PHP personalizada
- Instala extensiones (gd, zip, intl, pdo_mysql, etc.)
- Copia configuraciones (php.ini, xdebug.ini)

#### `docker/php/php.ini`
- Límites de memoria: `memory_limit = 512M`
- Timezone: `date.timezone = America/Bogota`
- Upload máximo: `upload_max_filesize = 200M`

#### `docker/php/xdebug.ini`
- Configuración de debugging remoto
- Puerto: 9003
- Host: `host.docker.internal`

#### `docker/nginx/default.conf`
- Reescritura de URLs
- Root document: `/var/www/html`
- FastCGI a PHP-FPM en puerto 9000

#### `docker/apache/httpd.conf`
- Habilitación de módulos (mod_rewrite, mod_php)
- Soporte para `.htaccess`
- Directorios permitidos

#### `docker/mariadb/my.cnf`
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Performance settings

#### `docker/ssl/`
- Certificados self-signed para HTTPS
- NO commitear (aunque están en .gitignore)

---

### 🔐 `/.secrets` - Contraseñas y Credenciales

**NUNCA commitear este directorio** (está en .gitignore).

Contiene:
```
.secrets/
├── db_password.txt       # Contraseña usuario 'duotics'
└── db_root_password.txt  # Contraseña root
```

Estos se montan como secretos Docker y se acceden con:
```php
$password = file_get_contents(getenv('DB_PASSWORD_FILE'));
```

---

### ⚙️ `/.env` - Variables de Entorno

**NO commitear** (aunque está en .gitignore).

Contiene puertos, nombres de bases de datos, etc.:
```bash
PHP_VERSION=8.3
TIMEZONE=America/Bogota
PORT_NGINX=8801
PORT_NGINX_HTTPS=8811
MARIADB_DATABASE=duotics
MARIADB_USER=duotics
```

Cópialo de `.env.example` y personalízalo:
```bash
cp .env.example .env
# Edita .env según necesites
```

---

### 📚 `/.vscode` - Configuración VS Code

`launch.json` contiene:
- Configuración de debugging Xdebug
- Puertos (9003)
- Paths mapping

Cuando presionas `F5`, usa esta configuración.

---

### 📚 `/docs` - Documentación

Guías completas sobre:
- Inicio rápido
- Debugging
- Gestión de secretos
- Troubleshooting
- Y más...

---

## 🔄 Cómo Funcionan los Volúmenes

| Volumen Local | Ruta en Contenedor | Servicios | Permisos |
|---|---|---|---|
| `./src/` | `/var/www/html` | PHP, Nginx, Apache, Node | R/W |
| `./docker/nginx/default.conf` | `/etc/nginx/conf.d/default.conf` | Nginx | RO |
| `./docker/apache/httpd.conf` | `/usr/local/apache2/conf/httpd.conf` | Apache | RO |
| `./docker/mariadb/my.cnf` | `/etc/mysql/conf.d/my.cnf` | MariaDB | RO |
| `./docker/ssl/` | `/etc/nginx/ssl/`, `/usr/local/apache2/conf/ssl/` | Nginx, Apache | RO |
| `./.secrets/` | `/run/secrets/` | MariaDB, PHP, PMA | RO |

**RO = Read Only** (no se puede editar desde el contenedor)

---

## 📂 Dónde Agregar Tu Código

### Opción 1: Estructura Simple (Principiantes)

```
src/
├── index.php           # Página de inicio
├── about.php           # Página about
├── contact.php         # Página contacto
└── config.php          # Configuración
```

Acceso: `http://localhost:8801/about.php`

---

### Opción 2: Estructura MVC (Recomendado)

```
src/
├── public/
│   └── index.php       # Punto de entrada (redirige todo aquí)
├── app/
│   ├── Controllers/    # Lógica de negocio
│   ├── Models/         # Acceso a datos
│   ├── Views/          # Templates HTML
│   └── Config/         # Configuración
└── composer.json       # Dependencias PSR-4
```

Acceso: Todo va a través de `index.php` (reescritura de URLs)

---

### Opción 3: Framework (Laravel, Symfony, etc.)

Instala via Composer:
```bash
docker compose exec php composer create-project laravel/laravel .
```

El framework se instala en `src/`

---

## 🚀 Primeras Acciones

1. **Explora la estructura** del proyecto actual
2. **Lee** las configuraciones en `docker/`
3. **Edita** `src/index.php` para personalizar
4. **Crea** tu directorio de código (`src/app/` o `src/my-project/`)
5. **Configura** base de datos si necesitas (ver [09-DATABASE-MANAGEMENT.md](./09-DATABASE-MANAGEMENT.md))

---

## 🎯 Acceso desde PHP

Una vez dentro de tu PHP, los paths relativo están en `/var/www/html`:

```php
<?php
// Archivo: src/test.php
// Ruta real en contenedor: /var/www/html/test.php

echo __DIR__;        // /var/www/html
echo dirname(__FILE__); // /var/www/html

// Para incluir otro archivo:
require 'config/app.php';           // Relativo
require '/var/www/html/config/app.php'; // Absoluto

// Para acceder a archivos públicos desde navegador:
// /public/css/style.css -> http://localhost:8801/public/css/style.css
```

---

## 📦 Composer y Dependencias PHP

Si necesitas librerías PHP:

```bash
# Instalar Composer
docker compose exec php composer install

# Agregar nueva dependencia
docker compose exec php composer require vendor/package

# Autoload de Composer
# Luego en tu PHP:
require 'vendor/autoload.php';
```

---

## 📝 .gitignore

Los siguientes directorios **no se commitean**:

```
.env
.secrets/
docker/ssl/*.crt
docker/ssl/*.key
vendor/
node_modules/
.DS_Store
```

Están protegidos en `.gitignore`.

---

**¡Listo!** Ahora entiendes la estructura. Próximo paso: [Debugging con Xdebug](./03-XDEBUG-DEBUGGING.md)
