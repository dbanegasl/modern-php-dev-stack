# 📚 Documentación - Modern PHP Dev Stack

Bienvenido a la documentación del stack moderno de desarrollo PHP. Aquí encontrarás guías completas sobre cómo configurar, usar y depurar tu entorno.

## 📖 Guías disponibles

### 🚀 [Guía de Inicio Rápido](./01-QUICKSTART.md)
Pasos iniciales para levantar el stack, verificar que todo funciona y acceder a los servicios.

**Temas:**
- Requisitos previos
- Inicializar el stack
- Verificar estado
- Acceder a cada servicio

---

### 🔧 [Estructura del Proyecto](./02-PROJECT-STRUCTURE.md)
Comprende la organización de carpetas, archivos configurables y dónde agregar tu código.

**Temas:**
- Organización de directorios
- Archivos principales
- Dónde está el código de la aplicación
- Configuraciones por servicio

---

### 🐛 [Debugging con Xdebug](./03-XDEBUG-DEBUGGING.md)
Guía completa para debug paso a paso con VS Code y Xdebug en tiempo real.

**Temas:**
- Configuración de Xdebug
- Configurar VS Code para debugging
- Colocar breakpoints
- Inspeccionar variables
- Troubleshooting

---

### 🔐 [Gestión de Secretos](./04-SECRETS-MANAGEMENT.md)
Cómo manejar credenciales de base de datos y variables sensibles de forma segura.

**Temas:**
- Estructura de secrets
- Cambiar contraseñas
- Acceder a secretos desde PHP
- Mejores prácticas de seguridad

---

### ⏰ [Timezone y Charset/Collation](./05-TIMEZONE-CHARSET.md)
Configuración de zona horaria y encoding para MariaDB y PHP.

**Temas:**
- Timezone en contenedores
- Charset UTF-8 MB4
- Collation utf8mb4_unicode_ci
- Validación de configuración
- Cambiar timezone

---

### 📦 [Assets Frontend con Node.js](./06-FRONTEND-ASSETS.md)
Compilación de CSS/JS con Vite, Webpack, npm y herramientas modernas.

**Temas:**
- Instalar dependencias
- Ejecutar dev server
- Compilar para producción
- Hot Module Replacement (HMR)

---

### 🌐 [Nginx vs Apache](./07-NGINX-APACHE.md)
Diferencias, ventajas y desventajas de usar uno u otro servidor web.

**Temas:**
- Configuración de Nginx
- Configuración de Apache
- Puertos y acceso
- `.htaccess` en Apache
- Reescritura de URLs

---

### 📧 [Email Testing con Mailpit](./08-MAILPIT-TESTING.md)
Interceptar y visualizar emails en desarrollo sin enviar realmente.

**Temas:**
- Configurar SMTP en PHP
- Acceder a Mailpit
- Visualizar emails capturados
- Configuración en php.ini

---

### 💾 [Gestión de Base de Datos](./09-DATABASE-MANAGEMENT.md)
Operaciones comunes con MariaDB, backups y restauración.

**Temas:**
- Acceder a phpMyAdmin
- Línea de comandos mariadb
- Backups y restauración
- Crear tablas
- Migraciones

---

### 🛑 [Troubleshooting](./10-TROUBLESHOOTING.md)
Soluciones a problemas comunes y cómo depurar issues.

**Temas:**
- Puertos en uso
- Contenedores no inician
- Errores de conexión BD
- Problemas de permisos
- Logs de contenedores

---

## 🎯 Mapa Rápido de Accesos

| Servicio | URL | Puerto | Usuario |
|----------|-----|--------|---------|
| **Nginx HTTP** | http://localhost:8801 | 8801 | - |
| **Nginx HTTPS** | https://localhost:8811 | 8811 | - |
| **Apache HTTP** | http://localhost:8802 | 8802 | - |
| **Apache HTTPS** | https://localhost:8812 | 8812 | - |
| **MariaDB** | localhost:8803 | 8803 | root / duotics |
| **phpMyAdmin** | http://localhost:8804 | 8804 | duotics / (contraseña) |
| **Mailpit Web** | http://localhost:8805 | 8805 | - |
| **Mailpit SMTP** | localhost:8825 | 8825 | - |
| **Vite HMR** | localhost:5173 | 5173 | - |

---

## 🚀 Comandos Esenciales

```bash
# Iniciar el stack
docker compose up -d --build

# Detener servicios
docker compose down

# Ver logs
docker compose logs -f [servicio]

# Ejecutar comando en contenedor
docker compose exec [servicio] [comando]

# Ejemplo: acceder a mariadb
docker compose exec db mariadb -u root -pcontraseña

# Ejemplo: ejecutar PHP
docker compose exec php php -r "phpinfo();"
```

---

## 💡 Próximos Pasos

1. Lee [Guía de Inicio Rápido](./01-QUICKSTART.md)
2. Explora la [Estructura del Proyecto](./02-PROJECT-STRUCTURE.md)
3. Configura [Debugging con Xdebug](./03-XDEBUG-DEBUGGING.md)
4. Consulta [Troubleshooting](./10-TROUBLESHOOTING.md) si tienes problemas

---

## 📝 Notas

- El directorio `.secrets/` contiene contraseñas (no commitear)
- El directorio `.env` contiene puertos y variables (no commitear datos sensibles)
- El código está en `./src/` (sincronizado en todos los servicios)
- Los logs de Docker están disponibles con `docker compose logs`

---

**¿Necesitas ayuda?** Consulta el archivo [Troubleshooting](./10-TROUBLESHOOTING.md) o revisa los logs con `docker compose logs`.
