# 🛑 Troubleshooting

Soluciones a problemas comunes y cómo depurar issues.

---

## 🔴 Error: Port Already in Use

### Problema
```
Error: Port 8801 is already allocated
```

### Solución

**Opción 1: Liberar el puerto (macOS/Linux)**

```bash
# Ver qué proceso usa el puerto
lsof -i :8801

# Matar el proceso
kill -9 <PID>

# O simplemente cambiar puerto en .env
# PORT_NGINX=8801 → PORT_NGINX=8901
```

**Opción 2: Cambiar puerto en `.env`**

```bash
# Editar .env
PORT_NGINX=8901          # En lugar de 8801
PORT_NGINX_HTTPS=8911    # En lugar de 8811

# Reiniciar
docker compose down
docker compose up -d
```

**Opción 3: Ver todos los puertos en uso (Windows)**

```cmd
netstat -ano | findstr 8801
taskkill /PID <PID> /F
```

---

## 🔴 Error: Contenedores no inician

### Síntomas
```bash
docker compose ps
# Muestra "Exit 1" o "Exited"
```

### Solución

**Paso 1: Ver logs**

```bash
docker compose logs -f [servicio]
# Por ejemplo:
docker compose logs -f db
docker compose logs -f php
docker compose logs -f nginx
```

**Paso 2: Analizar el error**

Busca mensajes de error en los logs. Ejemplos comunes:

**PHP errores:**
- `Dockerfile not found` → `docker/php/Dockerfile` no existe
- `permission denied` → Problema de permisos en volúmenes

**MariaDB errores:**
- `Can't find mysqld` → Imagen corrupta
- `ERROR 1045 Access denied` → Contraseña incorrecta en `.secrets/`

**Solución general:**

```bash
# Reconstruir imágenes
docker compose down -v
docker compose up -d --build
```

---

## 🔴 Error: Connection Refused (BD)

### Problema
```
Connection refused
SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
```

### Solución

**Opción 1: Verificar que MariaDB está corriendo**

```bash
docker compose ps db
# Debe estar "Up"

# Si no, revisar logs
docker compose logs db
```

**Opción 2: Esperar a que MariaDB inicie**

Cuando levanta por primera vez, tarda en inicializar. Espera 10 segundos:

```bash
docker compose up -d
sleep 10
docker compose exec php php -r "echo 'Probando...'; new PDO('mysql:host=db', 'root', 'pass');"
```

**Opción 3: Reiniciar MariaDB**

```bash
docker compose restart db
```

---

## 🔴 Error: Bad Hostname en Nginx/Apache

### Problema
```
Could not resolve host: db
Connection refused
```

### Solución

**Verificar nombre del host:**

```bash
# Desde PHP
docker compose exec php ping db
# Debe responder

# Desde Nginx
docker compose exec nginx ping db
# Debe responder
```

**Si no responde:**

```bash
# Reiniciar red Docker
docker network rm modern-xampp-net
docker compose down
docker compose up -d
```

---

## 🔴 Error: Permission Denied en Volúmenes

### Problema
```
permission denied: '/var/www/html'
```

### Solución

**Verificar permisos locales:**

```bash
ls -la src/
# Debe ser legible por tu usuario
```

**Corregir permisos:**

```bash
chmod -R 755 src/
chmod -R 644 src/*.*
```

**Dentro del contenedor:**

```bash
docker compose exec php chown -R www-data:www-data /var/www/html
```

---

## 🔴 Error: Xdebug No Conecta

### Problema
```
Xdebug: [Step Debug] Could not connect to debugging client.
```

### Solución

**Esto es normal.** Significa que Xdebug intentó conectar pero no había listener.

**Para que funcione:**

1. Abre VS Code en la carpeta del proyecto
2. Presiona `F5` para iniciar el listener
3. Ve a http://localhost:8801
4. Haz clic en el botón de testing

Si aún no funciona:

```bash
# Verificar que Xdebug esté instalado
docker compose exec php php -m | grep xdebug

# Verificar configuración
docker compose exec php php -i | grep xdebug
```

---

## 🔴 Error: PHP No Encuentra Extensiones

### Problema
```
PHP Warning: PHP Startup: Unable to load dynamic library 'xdebug'
```

### Solución

```bash
# Reconstruir imagen PHP
docker compose down
docker compose up -d --build php

# Verificar
docker compose exec php php -m | grep -i xdebug
```

---

## 🔴 Error: Charset UTF-8 Incorrecto

### Problema
```
Caracteres extraños: ñ → Ã±, é → Ã©
```

### Solución

**Paso 1: Verificar charset**

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "SHOW VARIABLES LIKE 'character_set%';"
# Todos deben ser utf8mb4
```

**Paso 2: Convertir base de datos**

```bash
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) duotics -e \
  "ALTER DATABASE duotics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Paso 3: En PHP, especificar charset**

```php
<?php
$password = trim(file_get_contents(getenv('DB_PASSWORD_FILE')));
$dsn = "mysql:host=" . getenv('DB_HOST') . 
       ";dbname=" . getenv('DB_DATABASE') . 
       ";charset=utf8mb4";  // ← Agregar esto
$pdo = new PDO($dsn, getenv('DB_USER'), $password);
?>
```

---

## 🔴 Error: Hora Incorrecta

### Problema
```
DATETIME muestra hora incorrecta (desajustada)
```

### Solución

**Verificar timezone:**

```bash
docker compose exec php php -r "echo date('Y-m-d H:i:s');"
docker compose exec db mariadb -u root -p$(cat .secrets/db_root_password.txt) -e "SELECT NOW();"
# Deben coincidir
```

**Cambiar timezone:**

1. Edita `.env`: `TIMEZONE=America/Bogota` (o tu zona)
2. Reinicia: `docker compose down && docker compose up -d`
3. Verifica: `docker compose exec php php -r "echo date('Y-m-d H:i:s');"`

---

## 🔴 Error: Dockerfile Build Fails

### Problema
```
ERROR: failed to build
Step 5: RUN chmod +x /usr/local/bin/install-php-extensions
```

### Solución

```bash
# Limpiar caché de Docker
docker builder prune -a

# Reconstruir sin caché
docker compose build --no-cache php
docker compose up -d
```

---

## 🔴 Error: Node.js npm install Falla

### Problema
```
npm ERR! Could not resolve dependency
```

### Solución

```bash
# Limpiar y reinstalar
docker compose exec node rm -rf node_modules package-lock.json
docker compose exec node npm install
```

---

## 🔴 Error: Mailpit No Captura Emails

### Problema
```
Email no aparece en http://localhost:8805
```

### Solución

**Verificar que Mailpit está corriendo:**

```bash
docker compose ps mailpit
# Debe estar "Up"
```

**Verificar configuración SMTP:**

```php
<?php
ini_set('SMTP', 'mailpit');
ini_set('smtp_port', '1025');

$result = mail('test@example.com', 'Test', 'Content');
var_dump($result);
?>
```

**Verificar red:**

```bash
docker compose exec php ping mailpit
# Debe responder
```

---

## 🔴 Error: Certificados SSL Inválidos

### Problema
```
NET::ERR_CERT_AUTHORITY_INVALID
```

**Esto es normal en desarrollo.** Los certificados son self-signed.

**Solución:**

1. En navegador, acepta la advertencia
2. O usa `curl -k https://localhost:8811` (ignorar SSL)

Para producción, obtén certificados reales.

---

## 📊 Comandos de Diagnóstico

```bash
# Estado de todos los contenedores
docker compose ps

# Ver logs de un servicio
docker compose logs -f [servicio]

# Ver logs de todos
docker compose logs

# Entrar a un contenedor
docker compose exec [servicio] sh

# Verificar conectividad entre contenedores
docker compose exec php ping db
docker compose exec php ping nginx

# Limpiar todo
docker system prune

# Reconstruir completamente
docker compose down -v
docker compose up -d --build
```

---

## 🆘 Última Opción: Reset Completo

Si nada funciona, resetea todo:

```bash
# Parar y eliminar TODO (incluyendo datos)
docker compose down -v

# Limpiar imágenes
docker rmi modern-xampp-php

# Limpiar caché
docker builder prune -a

# Reconstruir desde cero
docker compose up -d --build

# Esperar a que inicie completamente
sleep 15

# Verificar
docker compose ps
curl http://localhost:8801
```

---

## 📞 Información para Reportar Bugs

Si aún tienes problemas, recopila:

```bash
# Información del sistema
docker --version
docker compose --version

# Logs de servicios relevantes
docker compose logs > logs.txt

# Status
docker compose ps > status.txt

# Verificar configuración
cat .env.example > config.txt
```

Luego comparte esta información.

---

## 📚 Recursos Útiles

- [Docker Docs](https://docs.docker.com/)
- [MariaDB Troubleshooting](https://mariadb.com/kb/en/troubleshooting/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP Documentation](https://www.php.net/docs.php)
- [Xdebug Documentation](https://xdebug.org/docs/)

---

**¡Espero que hayas encontrado la solución!**

Si tienes más problemas, revisa los archivos de configuración en `docker/` y verifica los logs con `docker compose logs`.

---

**Índice de Documentación**: [Volver a README](./README.md)
