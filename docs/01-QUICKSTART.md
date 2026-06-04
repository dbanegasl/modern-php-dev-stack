# 🚀 Guía de Inicio Rápido

Esta guía te llevará paso a paso para levantar el stack y verificar que todo funciona correctamente.

## ✅ Requisitos Previos

- **Docker Desktop** (macOS, Windows o Linux)
- **Docker Compose** (incluido en Docker Desktop)
- **VS Code** (opcional, pero recomendado para debugging)
- **VS Code Extension**: [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) (opcional)

Verifica que Docker esté funcionando:
```bash
docker --version
docker compose --version
```

---

## 1️⃣ Clonar/Descargar el Proyecto

```bash
git clone <tu-repositorio> modernPhpDevStack
cd modernPhpDevStack
```

---

## 2️⃣ Revisar Configuración

Verifica que los archivos de configuración existan:

```bash
# El archivo .env debe existir
cat .env

# Los secretos deben existir
ls -la .secrets/
# Debe contener:
# - db_password.txt
# - db_root_password.txt

# Los certificados SSL deben existir
ls -la docker/ssl/
# Debe contener:
# - server.crt
# - server.key
```

**Si falta alguno**, cópialos del `.env.example` o regénéralos según necesites.

---

## 3️⃣ Levantar el Stack

```bash
# Opción 1: Levantar sin rebuild (más rápido, si ya existe)
docker compose up -d

# Opción 2: Levantar con rebuild (primera vez o cambios en Dockerfile)
docker compose up -d --build
```

Espera a que todos los contenedores inicien:

```bash
docker compose ps
```

Deberías ver algo así:
```
CONTAINER ID   IMAGE                    STATUS
xxxxx          modern-xampp-php         Up 2 seconds
xxxxx          nginx:alpine             Up 2 seconds
xxxxx          httpd:2.4-alpine         Up 2 seconds
xxxxx          mariadb:11.5             Up 2 seconds
xxxxx          phpmyadmin:latest        Up 2 seconds
xxxxx          axllent/mailpit:latest   Up 2 seconds
xxxxx          node:20-alpine           Up 2 seconds
```

---

## 4️⃣ Verificar Servicios

### Web Servers (Nginx & Apache)

Abre en tu navegador:
- **Nginx HTTP**: http://localhost:8801 ✅
- **Nginx HTTPS**: https://localhost:8811 (acepta el warning SSL)
- **Apache HTTP**: http://localhost:8802 ✅
- **Apache HTTPS**: https://localhost:8812 (acepta el warning SSL)

Deberías ver el dashboard oscuro con información del stack.

### phpMyAdmin (Gestor de BD)

- **URL**: http://localhost:8804
- **Usuario**: `duotics`
- **Contraseña**: Ver en `.secrets/db_password.txt`

```bash
cat .secrets/db_password.txt
```

### Mailpit (Email Testing)

- **Web UI**: http://localhost:8805
- **SMTP Puerto**: 8825 (para PHP mail)

---

## 5️⃣ Verificar Configuración de Base de Datos

Conectar a MariaDB y validar timezone y charset:

```bash
# Acceder a mariadb directamente
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SELECT NOW(); SELECT @@global.time_zone; SHOW VARIABLES LIKE 'character_set_database';"
```

Esperado:
```
NOW()               | 2026-06-03 23:08:09
@@global.time_zone  | SYSTEM
character_set_*     | utf8mb4
collation_database  | utf8mb4_unicode_ci
```

---

## 6️⃣ Verificar PHP

Ejecutar un script PHP simple:

```bash
docker compose exec php php -r "
echo 'PHP Version: ' . phpversion() . PHP_EOL;
echo 'Timezone: ' . ini_get('date.timezone') . PHP_EOL;
echo 'Current Time: ' . date('Y-m-d H:i:s') . PHP_EOL;
echo 'Extensions: ';
echo implode(', ', array_slice(get_loaded_extensions(), 0, 5)) . '...' . PHP_EOL;
"
```

Esperado:
```
PHP Version: 8.3.x
Timezone: America/Bogota
Current Time: 2026-06-03 23:11:59
Extensions: Core, standard, pcre, ...
```

---

## 7️⃣ Verificar Conexión BD desde PHP

```bash
docker compose exec php php -r "
\$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
try {
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST'),
        getenv('DB_USER'),
        \$password
    );
    echo '✅ Conexión a MariaDB exitosa!' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . PHP_EOL;
}
"
```

Esperado:
```
✅ Conexión a MariaDB exitosa!
```

---

## 8️⃣ Crear tu Primer Script PHP

Abre o crea `src/test.php`:

```php
<?php
// src/test.php

echo "<h1>¡Hola desde Modern PHP Dev Stack!</h1>";
echo "<p>Timezone: " . ini_get('date.timezone') . "</p>";
echo "<p>Hora actual: " . date('Y-m-d H:i:s') . "</p>";

// Conectar a base de datos
$db_password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE');
    $pdo = new PDO($dsn, getenv('DB_USER'), $db_password);
    $result = $pdo->query("SELECT DATABASE() as current_db, @@character_set_database as charset, @@collation_database as collation")->fetch();
    
    echo "<h2>Base de Datos</h2>";
    echo "<ul>";
    echo "<li>Base activa: " . $result['current_db'] . "</li>";
    echo "<li>Charset: " . $result['charset'] . "</li>";
    echo "<li>Collation: " . $result['collation'] . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
```

Luego accede en tu navegador:
- http://localhost:8801/test.php (Nginx)
- http://localhost:8802/test.php (Apache)

---

## 9️⃣ Configurar Debugging (Opcional pero Recomendado)

Ver la guía completa: [Debugging con Xdebug](./03-XDEBUG-DEBUGGING.md)

Pasos rápidos:
1. Instala extensión PHP Debug en VS Code
2. Abre VS Code en la raíz del proyecto
3. Crea un breakpoint en `src/index.php` línea 54
4. Presiona `F5` para iniciar debugger
5. Haz clic en "⚡ Trigger Breakpoint Test" en el dashboard

---

## 🛑 Detener el Stack

```bash
# Detener sin eliminar volúmenes (data persiste)
docker compose down

# Detener y eliminar todo (incluyendo BD)
docker compose down -v
```

---

## 📝 Resumen

✅ Stack levantado y corriendo
✅ Todos los servicios accesibles
✅ Base de datos con charset UTF-8 MB4
✅ Timezone sincronizado (America/Bogota)
✅ PHP conectado a MariaDB

---

## 🆘 Problemas?

Si tienes issues:
1. Revisa [Troubleshooting](./10-TROUBLESHOOTING.md)
2. Mira los logs: `docker compose logs -f`
3. Verifica puertos no usados: `docker ps`

---

**Siguiente paso**: Estudia la [Estructura del Proyecto](./02-PROJECT-STRUCTURE.md) para entender dónde ir agregando código.
