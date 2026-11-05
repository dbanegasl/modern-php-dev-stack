# 🏥 Alta de un cliente MediABC en la stack

Pasos validados en un servidor limpio (clone nuevo de la stack) para instalar `mediabc-app` como un cliente nuevo.
La fuente de verdad de la app es su propia documentación (`src/mediabc-app/docs/operations/install.md` y
`runbook-clonado-actualizacion.md`); esta guía la adapta al stack Docker y recoge un detalle que ninguna de las dos cubre.

> ⚠️ **Incidente conocido — léelo antes de instalar:** el `.env` de mediabc debe pertenecer a `www-data` **antes** de
> ejecutar el instalador web. Ver [Incidente](#incidente-env-sin-permiso-de-escritura).

---

## Requisitos previos

- La stack levantada (`docker compose up -d --build`) con `.env`, `.secrets/` y `docker/ssl/` según [01-QUICKSTART](./01-QUICKSTART.md).
- Acceso SSH a `git@github.com:duotics/mediabc-app.git`.

---

## 1️⃣ Clonar mediabc

`src/*` está en el `.gitignore` de la stack, así que mediabc no ensucia su repo.

```bash
git clone git@github.com:duotics/mediabc-app.git src/mediabc-app
```

## 2️⃣ Dependencias PHP

`vendor/` y `node_modules/` no viajan en el repo (`dist/` sí: el frontend ya está construido).

```bash
docker compose exec -w /var/www/html/mediabc-app php composer install --no-dev --optimize-autoloader
```

## 3️⃣ `.env` de la app (con el propietario correcto)

Se parte de la plantilla. `APP_SECRET` y `APP_TOKEN` se **dejan vacíos**: los genera el instalador y es el único autorizado.
Se usa `root` porque el instalador debe poder hacer `CREATE DATABASE`.

```bash
cd src/mediabc-app
cp .env.example .env
sed -i 's|^APP_URLR=.*|APP_URLR="http://localhost:8802/"|' .env
sed -i 's|^APP_URL=.*|APP_URL="http://localhost:8802/mediabc-app/"|' .env
sed -i 's|^DB_SERVER=.*|DB_SERVER="db"|' .env
sed -i 's|^DB_BASE=.*|DB_BASE="mediabc_app"|' .env
sed -i 's|^DB_USER=.*|DB_USER="root"|' .env
sed -i "s|^DB_PASS=.*|DB_PASS=\"$(cat ../../.secrets/db_root_password.txt)\"|" .env
cd ../..

# IMPRESCINDIBLE: el instalador web corre como www-data y debe poder ESCRIBIR el .env
docker compose exec php chown www-data:www-data /var/www/html/mediabc-app/.env
docker compose exec php ls -l /var/www/html/mediabc-app/.env     # propietario: www-data
```

> Tras el `chown`, editar ese archivo desde el host requiere `sudo` (o `docker compose exec`).

## 4️⃣ Permisos de directorios runtime

```bash
docker compose exec php sh -c 'cd /var/www/html/mediabc-app && mkdir -p logs temp backups && chown -R www-data:www-data data logs temp backups'
```

## 5️⃣ Instalar (web, por Apache)

Abrir `http://localhost:8802/mediabc-app/install/install.php`, completar el administrador y pulsar **Instalar Sistema**.
El resultado debe mostrar todos los pasos en verde (~108 tablas, CIE-10 con 14 206 registros).

> El instalador web **solo** está permitido en `install/install.php`. Apache lo permite vía el `.htaccess` de la app.
> En Nginx hay que dejar la excepción en `docker/nginx/conf.d/<proyecto>.conf` (ver [07-NGINX-APACHE](./07-NGINX-APACHE.md)):
> ```nginx
> location ~* ^/mediabc-app/install/(?!install\.php$) { deny all; return 404; }
> ```

## 6️⃣ Verificar (con tests que distinguen "vacío" de "lleno")

```bash
# Las claves deben tener longitud >> 2 (2 = solo las comillas vacías). No imprime los valores.
grep -E '^(APP_SECRET|APP_TOKEN)=' src/mediabc-app/.env | awk -F= '{print $1": longitud="length($2)}'
#   Esperado: APP_SECRET ~138, APP_TOKEN 34

docker compose exec -u www-data -w /var/www/html/mediabc-app php php install/update_cli.php --status
#   Esperado: versión código = versión BD, 0 migraciones pendientes

curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8802/mediabc-app/index.php
#   Esperado: 200 (login) — nunca 500
```

> ⚠️ No uses `sed 's/=.*/=<generado>/'` para "comprobar" las claves: imprime `<generado>` aunque estén vacías.

---

## Esperado en un cliente nuevo

- **El logo y el fondo del login salen rotos (404 de `data/img/logo.png` y `data/bg/bg00.jpg`).** Es normal: son contenido
  por cliente, no versionado (`data/*` solo trae `.gitkeep`). Se suben desde la configuración de la app.
- Ruido de DevTools (`*.css.map`, `.well-known/…devtools.json`, `favicon.ico`): sin efecto.

---

## Incidente: `.env` sin permiso de escritura

**Qué pasó.** Se creó el `.env` desde el host (propietario: el usuario del host). El instalador web corre como `www-data`
y, al no poder escribir el archivo, **generó las claves en memoria pero no las guardó**. La instalación terminó en verde, pero
`index.php` devolvía 500 y `logs/errors.log` mostraba:

```
Encrypt.php: APP_SECRET/APP_TOKEN no configurados. Ejecute el instalador …
```

**Por qué costó detectarlo.**
- El instalador no avisa de que no pudo escribir las claves.
- Con `APP_ENV="PROD"` los errores no salen en pantalla: hay que leer `mediabc-app/logs/errors.log`.
- La documentación de mediabc exige permisos de escritura para `data/`, `logs/`, `temp/` y `backups/`, pero **no menciona el `.env`**.
- Una verificación que imprimía un valor fijo en lugar de medir el contenido dio una falsa confirmación.

**Cómo salir si ya ocurrió.** `migrations/reset_drop_tables.php` **no sirve** en ese estado: carga `init.php`, que falla por las
claves vacías, y termina sin imprimir nada. Como el instalador crea la base si no existe, se resetea directamente:

```bash
docker compose exec php chown www-data:www-data /var/www/html/mediabc-app/.env
docker compose exec db sh -c 'mariadb -uroot -p"$(cat /run/secrets/db_root_password)" -e "DROP DATABASE IF EXISTS mediabc_app"'
docker compose exec php rm -f /var/www/html/mediabc-app/data/.system_initialized /var/www/html/mediabc-app/data/.config_sync_hash
# …y reinstalar desde el navegador (paso 5)
```

> Solo en instalaciones de prueba: borra la base `mediabc_app` completa.

**Lección.** Tras instalar, `.env` y base de datos van **siempre juntos**: restaurar una BD cifrada sin su `.env` deja los datos ilegibles
(BUG-07 en el runbook de mediabc).

**Pendiente en el repo de mediabc (no tocado desde aquí):** añadir a `docs/operations/install.md` que `.env` también debe ser
escribible por el usuario del servidor en la vía web, y/o que el instalador avise si `updateEnv()` no pudo escribir.
