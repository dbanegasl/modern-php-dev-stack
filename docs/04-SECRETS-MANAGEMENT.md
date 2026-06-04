# 🔐 Gestión de Secretos

Cómo manejar credenciales de base de datos y variables sensibles de forma segura.

---

## 📂 Estructura de Secretos

```
.secrets/
├── db_password.txt          # Contraseña usuario 'duotics'
└── db_root_password.txt     # Contraseña root de MariaDB
```

**Nunca commitear este directorio** (está en `.gitignore`).

---

## 🔑 Contraseñas por Defecto

Abre cada archivo para ver la contraseña actual:

```bash
cat .secrets/db_password.txt
cat .secrets/db_root_password.txt
```

---

## ✏️ Cambiar Contraseñas

### Paso 1: Edita los archivos de secretos

```bash
# Editar contraseña del usuario
echo "nueva_contraseña_usuario" > .secrets/db_password.txt

# Editar contraseña root
echo "nueva_contraseña_root" > .secrets/db_root_password.txt
```

### Paso 2: Reinicia MariaDB

```bash
# Detener el stack
docker compose down

# Levantar nuevamente
docker compose up -d
```

MariaDB se reiniciará con las nuevas contraseñas.

### Paso 3: Verifica la conexión

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e "SELECT 1;"
```

Deberías ver: `1`

---

## 🔗 Acceder a Secretos desde PHP

### Método 1: Desde Variables de Entorno

MariaDB, PHP y phpMyAdmin reciben la ruta del archivo en variables de entorno:

```php
<?php
// Leer contraseña de forma segura
$db_password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));

// Conectar a base de datos
$dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE');
$pdo = new PDO($dsn, getenv('DB_USER'), $db_password);

echo "Conectado a: " . getenv('DB_DATABASE');
?>
```

**Ventajas:**
- Contraseña no está en el código
- Se lee del archivo en tiempo de ejecución
- Segura incluso si el código es expuesto

### Método 2: Variables de Entorno Directas

También está disponible en `$_ENV`:

```php
<?php
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'duotics';
$db_name = $_ENV['DB_DATABASE'] ?? 'duotics';

echo "Host: " . $db_host;
echo "Usuario: " . $db_user;
echo "Database: " . $db_name;
?>
```

---

## 🛡️ Mejores Prácticas

### ❌ NO Hagas Esto

```php
<?php
// ❌ NUNCA hardcodear contraseñas
$password = "mi_contraseña_123";

// ❌ NUNCA guardar en .env commiteado
// (aunque esté en .gitignore, es riesgo)

// ❌ NUNCA mostrar errores con credenciales
try {
    // ...
} catch (Exception $e) {
    echo "Error: " . $e->getMessage(); // Puede exponer contraseña
}
?>
```

### ✅ HAZ Esto

```php
<?php
// ✅ Leer de archivo secreto
$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));

// ✅ Usar variables de entorno
$host = getenv('DB_HOST');
$database = getenv('DB_DATABASE');

// ✅ Manejo seguro de errores
try {
    $pdo = new PDO($dsn, $user, $password);
} catch (Exception $e) {
    error_log("DB Connection failed"); // Log, no mostrar
    die("Error de conexión. Contacta soporte.");
}

// ✅ Validar entrada
$user_id = intval($_GET['id'] ?? 0);
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
?>
```

---

## 🔄 Docker Secrets vs Archivos

### Cómo Funciona en Docker Compose

```yaml
secrets:
  db_password:
    file: .secrets/db_password.txt
  db_root_password:
    file: .secrets/db_root_password.txt

services:
  db:
    secrets:
      - db_password
      - db_root_password
    environment:
      DB_PASSWORD_FILE: /run/secrets/db_password
```

Los secretos se montan en `/run/secrets/` con permisos restringidos.

---

## 🚀 Usar Config External (Avanzado)

Si quieres más control, puedes usar:

```yaml
secrets:
  db_password:
    file: /path/segura/db_password.txt
```

O desde un servicio de secretos:

```yaml
secrets:
  db_password:
    external: true  # Desde Docker Secrets Manager
```

---

## 📋 Resumen

| Tarea | Comando |
|-------|---------|
| Ver contraseña | `cat .secrets/db_password.txt` |
| Cambiar contraseña | `echo "nueva" > .secrets/db_password.txt` && `docker compose down && up -d` |
| Acceder desde PHP | `getenv('DB_PASSWORD_FILE')` |
| Reiniciar BD | `docker compose restart db` |
| Respaldar secretos | `cp -r .secrets/ .secrets.backup/` |

---

**Siguiente paso**: [Timezone y Charset/Collation](./05-TIMEZONE-CHARSET.md)
