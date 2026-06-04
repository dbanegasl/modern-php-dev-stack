# ⏰ Timezone y Charset/Collation

Configuración de zona horaria y encoding para MariaDB y PHP.

---

## 🌍 Timezone (Zona Horaria)

### Configuración Actual

**Timezone Global**: `America/Bogota`

Se define en tres lugares:

#### 1. Variable de Entorno (`.env`)

```bash
TIMEZONE=America/Bogota
```

Se pasa a todos los contenedores como variable `TZ`.

#### 2. PHP (`docker/php/php.ini`)

```ini
date.timezone = America/Bogota
```

#### 3. MariaDB (`docker/mariadb/my.cnf`)

```ini
[mysqld]
# Usa la TZ del contenedor (heredada de .env)
```

---

## ✅ Verificar Timezone

### PHP

```bash
docker compose exec php php -r "
echo 'PHP Timezone: ' . ini_get('date.timezone') . PHP_EOL;
echo 'Current Time: ' . date('Y-m-d H:i:s') . PHP_EOL;
echo 'Offset: ' . date('P') . PHP_EOL;
"
```

Esperado:
```
PHP Timezone: America/Bogota
Current Time: 2026-06-03 23:08:20
Offset: -05:00
```

### MariaDB

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SELECT NOW(), @@global.time_zone, @@session.time_zone;"
```

Esperado:
```
NOW()                | 2026-06-03 23:08:09
@@global.time_zone   | SYSTEM
@@session.time_zone  | SYSTEM
```

**SYSTEM** significa que hereda la TZ del contenedor (correcto).

### Desde PHP a MariaDB

```bash
docker compose exec php php -r "
\$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
\$pdo = new PDO('mysql:host=' . getenv('DB_HOST'), getenv('DB_USER'), \$password);
\$result = \$pdo->query('SELECT NOW()')->fetch();
echo 'Database NOW(): ' . \$result[0];
"
```

Debería coincidir con la hora del sistema.

---

## 🔄 Cambiar Timezone

### Paso 1: Edita `.env`

```bash
TIMEZONE=Europe/London  # o la zona que necesites
```

Otros ejemplos:
- `America/New_York`
- `Europe/Madrid`
- `Asia/Tokyo`
- `UTC`

[Ver lista completa de timezones](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)

### Paso 2: Reinicia los Contenedores

```bash
docker compose down
docker compose up -d
```

### Paso 3: Verifica

```bash
docker compose exec php php -r "echo ini_get('date.timezone');"
```

---

## 🔤 Charset y Collation

### ¿Qué es?

- **Charset**: Codificación de caracteres (UTF-8, Latin1, etc.)
- **Collation**: Orden y reglas de comparación (sensible a mayúsculas, tildes, etc.)

Ejemplo:
```sql
-- UTF-8 MB4 soporta emoji y caracteres especiales
CREATE TABLE users (
    id INT,
    name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
);
```

### Configuración Actual

**Charset**: `utf8mb4` (UTF-8 de 4 bytes, soporta emoji)  
**Collation**: `utf8mb4_unicode_ci` (case-insensitive, acepta Unicode)

---

## ✅ Verificar Charset y Collation

### MariaDB - Variables Globales

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SHOW VARIABLES LIKE 'character%'; SHOW VARIABLES LIKE 'collation%';"
```

Esperado:
```
character_set_server     | utf8mb4
character_set_database   | utf8mb4
collation_server         | utf8mb4_unicode_ci
collation_database       | utf8mb4_unicode_ci
```

### Base de Datos Específica

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SHOW CREATE DATABASE duotics\G"
```

Esperado:
```
CREATE DATABASE `duotics` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

### Tabla Específica

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "SHOW CREATE TABLE usuarios\G"
```

Verás el charset y collation de esa tabla.

---

## 🔧 Cambiar Charset/Collation

### Base de Datos Existente

```sql
-- Alterar base de datos
ALTER DATABASE duotics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Alterar tabla
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Alterar columna
ALTER TABLE usuarios MODIFY name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Desde PHP:
```bash
docker compose exec php php -r "
\$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
\$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), 
              getenv('DB_USER'), \$password);
\$pdo->exec('ALTER DATABASE duotics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo 'Base de datos convertida';
"
```

### Nueva Base de Datos

```sql
CREATE DATABASE mi_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Nueva Tabla

```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE,
    name VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 📊 Collations Comunes

| Collation | Descripción | Caso | Acentos |
|-----------|-------------|------|---------|
| `utf8mb4_unicode_ci` | Unicode moderno | Insensible | Sensible |
| `utf8mb4_general_ci` | Unicode rápido | Insensible | Insensible |
| `utf8mb4_bin` | Binario exacto | Sensible | Sensible |
| `utf8mb4_spanish_ci` | Español | Insensible | Sensible |

**Recomendación**: `utf8mb4_unicode_ci` (mejor para internacionales)

---

## 🚀 Campo DATETIME ON UPDATE CURRENT_TIMESTAMP

Ahora que timezone está sincronizado, esto funciona correctamente:

```sql
CREATE TABLE registros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

Cuando actualices un registro:
```sql
UPDATE registros SET content = 'nuevo' WHERE id = 1;
-- updated_at se actualiza automáticamente a NOW()
```

Verificar:
```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "SELECT created_at, updated_at FROM registros;"
```

---

## 🎯 Checklist de Configuración

- [x] Timezone sincronizado en `.env`
- [x] PHP con timezone correcto
- [x] MariaDB con TZ heredada
- [x] Charset `utf8mb4` en bases de datos
- [x] Collation `utf8mb4_unicode_ci` en tablas
- [x] Campos DATETIME capturando hora correcta

---

## 🔍 Debugging de Problemas de Encoding

### Problema: Caracteres extraños (ñ, é, emoji)

**Causa**: Charset incorrecto en tabla/columna

**Solución**:
```sql
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Problema: Comparaciones sensibles a mayúsculas/minúsculas

**Causa**: Collation case-sensitive

**Solución**:
```sql
-- Cambiar collation a insensible
ALTER TABLE usuarios MODIFY email VARCHAR(100) COLLATE utf8mb4_unicode_ci;
```

### Problema: Hora incorrecta en DATETIME

**Causa**: Timezone no sincronizado

**Solución**:
1. Edita `.env` con timezone correcto
2. Reinicia: `docker compose down && docker compose up -d`
3. Verifica: `docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e "SELECT NOW();"`

---

## 📚 Recursos

- [Timezones PHP](https://www.php.net/manual/en/timezones.php)
- [Timezones MariaDB](https://mariadb.com/kb/en/time-zones/)
- [UTF-8 vs UTF-8 MB4](https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb4.html)
- [Collations MySQL/MariaDB](https://mariadb.com/kb/en/library/setting-character-sets-and-collations/)

---

**Siguiente paso**: [Assets Frontend con Node.js](./06-FRONTEND-ASSETS.md)
