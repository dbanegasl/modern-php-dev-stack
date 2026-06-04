# 💾 Gestión de Base de Datos

Operaciones comunes con MariaDB, backups y restauración.

---

## 🔗 Métodos de Acceso

### 1. phpMyAdmin (UI Web)

**URL**: http://localhost:8804  
**Usuario**: `duotics`  
**Contraseña**: Ver en `.secrets/db_password.txt`

Interface gráfica completa para gestionar BD.

### 2. Línea de Comandos (mariadb-cli)

```bash
# Conectar como usuario normal
docker compose exec db mariadb -u duotics -p$(cat .secrets/db_password.txt) duotics

# Conectar como root
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt)
```

### 3. Desde PHP (PDO)

```php
<?php
$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USER'),
    $password
);

$result = $pdo->query("SELECT * FROM usuarios")->fetchAll();
var_dump($result);
?>
```

---

## 🏗️ Crear Tablas

### Desde CLI

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e "

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100),
    password VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255),
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX (user_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

"
```

### Desde phpMyAdmin

1. Accede a http://localhost:8804
2. Selecciona base de datos (duotics)
3. Pestaña "SQL"
4. Copia y pega el CREATE TABLE
5. Click "Execute"

---

## 📥 Insertar Datos

### Inserción Simple

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e "

INSERT INTO usuarios (email, name, password) VALUES 
('alice@example.com', 'Alice', SHA2('password123', 256)),
('bob@example.com', 'Bob', SHA2('password456', 256));

"
```

### Inserción desde PHP

```php
<?php
$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
               getenv('DB_USER'), $password);

$stmt = $pdo->prepare("INSERT INTO usuarios (email, name) VALUES (?, ?)");
$stmt->execute(['john@example.com', 'John']);

echo "Registros insertados: " . $pdo->lastInsertId();
?>
```

---

## 📋 Consultas

### Ver Base de Datos

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SHOW DATABASES;"
```

### Ver Tablas

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "SHOW TABLES;"
```

### Ver Estructura Tabla

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "DESCRIBE usuarios;"
```

### Consulta SELECT

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "SELECT id, email, name FROM usuarios WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);"
```

---

## 🔄 Actualizar y Eliminar

### UPDATE

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "UPDATE usuarios SET name = 'Alice Updated' WHERE email = 'alice@example.com';"
```

### DELETE

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "DELETE FROM usuarios WHERE id = 1;"
```

---

## 💾 Backups

### Backup Completo

```bash
docker compose exec db mysqldump -u root -p$(cat .secrets/db_root_password.txt) \
  --all-databases > backup_complete_$(date +%Y%m%d_%H%M%S).sql
```

### Backup de Una Base de Datos

```bash
docker compose exec db mysqldump -u root -p$(cat .secrets/db_root_password.txt) duotics \
  > backup_duotics_$(date +%Y%m%d_%H%M%S).sql
```

### Backup de Una Tabla

```bash
docker compose exec db mysqldump -u root -p$(cat .secrets/db_root_password.txt) duotics usuarios \
  > backup_usuarios_$(date +%Y%m%d_%H%M%S).sql
```

**Los backups se guardan en tu máquina local.**

---

## 📥 Restaurar Backups

### Restaurar Base Completa

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) \
  < backup_complete_20240603_120000.sql
```

### Restaurar una Base de Datos

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics \
  < backup_duotics_20240603_120000.sql
```

### Restaurar una Tabla

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics \
  < backup_usuarios_20240603_120000.sql
```

---

## 🔧 Mantenimiento

### Optimizar Tablas

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "OPTIMIZE TABLE usuarios, posts;"
```

### Reparar Tablas (si están dañadas)

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "REPAIR TABLE usuarios;"
```

### Ver Tamaño de BD

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e "
SELECT 
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
GROUP BY table_schema;
"
```

---

## 🔐 Usuarios y Permisos

### Crear Nuevo Usuario

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e "
CREATE USER 'nuevo_usuario'@'%' IDENTIFIED BY 'nueva_contraseña';
GRANT ALL PRIVILEGES ON duotics.* TO 'nuevo_usuario'@'%';
FLUSH PRIVILEGES;
"
```

### Ver Usuarios

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "SELECT user, host FROM mysql.user;"
```

### Cambiar Contraseña Usuario

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e \
  "ALTER USER 'duotics'@'%' IDENTIFIED BY 'nueva_contraseña';"
```

---

## 🎯 Checklist Común

- [x] Ver datos: `SELECT * FROM tabla;`
- [x] Insertar: `INSERT INTO tabla VALUES (...);`
- [x] Actualizar: `UPDATE tabla SET campo = valor;`
- [x] Eliminar: `DELETE FROM tabla WHERE condición;`
- [x] Backup: `mysqldump ... > backup.sql`
- [x] Restaurar: `mariadb < backup.sql`

---

## 📚 Recursos

- [MariaDB Docs](https://mariadb.com/kb/en/)
- [SQL Tutorial](https://www.w3schools.com/sql/)
- [phpMyAdmin Manual](https://docs.phpmyadmin.net/)

---

**Siguiente paso**: [Troubleshooting](./10-TROUBLESHOOTING.md)
