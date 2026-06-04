# 🌐 Nginx vs Apache

Diferencias, ventajas y desventajas de los dos servidores web.

---

## 📊 Comparación Rápida

| Aspecto | Nginx | Apache |
|---------|-------|--------|
| **Performance** | Ligero, rápido | Pesado, más recursos |
| **Uso de Memoria** | Bajo (asincrónico) | Alto (proceso por request) |
| **.htaccess** | ❌ No | ✅ Sí |
| **Módulos** | Pocos (compilados) | Muchos (dinámicos) |
| **Reescritura URLs** | Archivo config | .htaccess o config |
| **Certificados SSL** | Excelente soporte | Excelente soporte |
| **Compresión** | Nativa | Con módulo |
| **Curva Aprendizaje** | Media | Fácil |

---

## 🚀 En Este Stack

**Ambos están disponibles simultáneamente:**

- **Nginx**: http://localhost:8801 (HTTP) / https://localhost:8811 (HTTPS)
- **Apache**: http://localhost:8802 (HTTP) / https://localhost:8812 (HTTPS)

Ambos apuntan a `/var/www/html` (carpeta `src/`).

---

## ⚙️ Configuración Nginx

**Archivo**: `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name _;
    
    root /var/www/html;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
    
    # Reescritura de URLs (sin .php)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### Reescribir URLs en Nginx

Para que `localhost:8801/users/123` vaya a `index.php?id=123`:

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?path=$1 last;
    }
}
```

---

## ⚙️ Configuración Apache

**Archivo**: `docker/apache/httpd.conf`

Soporta `.htaccess` automáticamente.

Crea `src/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # No reescribir archivos/directorios reales
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Reescribir todo a index.php
    RewriteRule ^(.*)$ /index.php [L,QSA]
</IfModule>
```

### Cambiar Directorio Raíz

En `docker/apache/httpd.conf`:

```apache
DocumentRoot /var/www/html/public
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
</Directory>
```

---

## 🔄 Cuándo Usar Cada Uno

### Usa Nginx si:
- ✅ Esperas alto tráfico
- ✅ Quieres mejor performance
- ✅ No necesitas .htaccess
- ✅ Prefieres configuración centralizada

### Usa Apache si:
- ✅ Necesitas .htaccess
- ✅ Tienes módulos específicos
- ✅ Es tu preferencia personal
- ✅ Necesitas Virtual Hosts complejos

---

## 🧪 Probar Ambos

### Test Performance

```bash
# Nginx
curl -w "Tiempo: %{time_total}s\n" http://localhost:8801

# Apache
curl -w "Tiempo: %{time_total}s\n" http://localhost:8802
```

### Test HTTPS

```bash
# Nginx HTTPS (acepta warning SSL)
curl -k https://localhost:8811

# Apache HTTPS (acepta warning SSL)
curl -k https://localhost:8812
```

---

## 🔗 Integración con PHP-FPM

Ambos usan PHP-FPM (FastCGI):

```yaml
services:
  nginx:
    fastcgi_pass php:9000    # Comunica con PHP-FPM
  apache:
    fastcgi_pass php:9000    # Comunica con PHP-FPM
  php:
    ports:
      - "9000"               # Puerto FastCGI
```

El código PHP es el mismo en ambos.

---

## 🛠️ Personalizar Configuración

### Nginx

Edita `docker/nginx/default.conf`:

```nginx
server {
    # Tu configuración
}
```

Luego:
```bash
docker compose down
docker compose up -d --build
```

### Apache

Edita `docker/apache/httpd.conf`:

```apache
# Tu configuración
```

Luego reinicia:
```bash
docker compose restart apache
```

---

## 📚 Ejemplos

### Reescritura de URLs (Nginx + Apache)

**Nginx** (`docker/nginx/default.conf`):
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Apache** (`src/.htaccess`):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L,QSA]
</IfModule>
```

### Bloquear Acceso Directo

**Nginx**:
```nginx
location ~ /\. {
    deny all;
}
```

**Apache** (`src/.htaccess`):
```apache
<FilesMatch "^\.">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>
```

---

## 🔍 Logs

Ver logs de cada servidor:

```bash
# Nginx
docker compose logs -f nginx

# Apache
docker compose logs -f apache
```

---

## 🎯 Recomendación

Para este stack:
- **Desarrollo**: Usa indistintamente, ambos funcionan igual
- **Testing**: Prueba en ambos antes de producción
- **Producción**: Nginx (mejor performance)

---

**Siguiente paso**: [Email Testing con Mailpit](./08-MAILPIT-TESTING.md)
