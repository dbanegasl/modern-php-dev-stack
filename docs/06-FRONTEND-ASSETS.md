# 📦 Assets Frontend con Node.js y npm

Compilación de CSS/JS con Vite, Webpack, Tailwind y herramientas modernas.

---

## 🎯 ¿Qué es?

El stack incluye un contenedor Node.js para:
- Instalar dependencias npm
- Compilar SASS/SCSS a CSS
- Bundlear JavaScript con Webpack/Vite
- Usar Tailwind CSS, PostCSS, etc.
- Hot Module Replacement (HMR) en desarrollo

**Puerto**: 5173 (Vite dev server)

---

## 📂 Estructura

```
src/
├── package.json         # Dependencias npm
├── package-lock.json    # Lock file
├── vite.config.js       # Config Vite (si lo usas)
├── tailwind.config.js   # Config Tailwind (si lo usas)
└── public/
    ├── css/
    │   └── style.css    # CSS compilado o generado
    ├── js/
    │   └── app.js       # JavaScript compilado
    └── images/
```

---

## 🚀 Comandos Esenciales

### Instalar Dependencias

```bash
docker compose exec node npm install
```

Instala las dependencias de `src/package.json` en el contenedor.

### Ejecutar Dev Server (Vite)

```bash
docker compose exec node npm run dev
```

Inicia servidor de desarrollo en `http://localhost:5173`

### Compilar para Producción

```bash
docker compose exec node npm run build
```

Genera assets optimizados en `public/`

### Instalar un Nuevo Paquete

```bash
docker compose exec node npm install tailwindcss postcss autoprefixer
```

### Actualizar Dependencias

```bash
docker compose exec node npm update
```

### Ver Scripts Disponibles

```bash
docker compose exec node cat package.json | grep -A 10 '"scripts"'
```

---

## 🎨 Ejemplos de Configuración

### Con Tailwind CSS + Vite

Crea `src/package.json`:
```json
{
  "name": "my-app",
  "version": "1.0.0",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "dependencies": {
    "vite": "^4.0.0"
  },
  "devDependencies": {
    "tailwindcss": "^3.0.0",
    "postcss": "^8.0.0",
    "autoprefixer": "^10.0.0"
  }
}
```

Luego:
```bash
docker compose exec node npm install
docker compose exec node npm run dev
```

### Con SASS

```json
{
  "scripts": {
    "sass": "sass src/scss:public/css",
    "sass:watch": "sass --watch src/scss:public/css"
  },
  "devDependencies": {
    "sass": "^1.50.0"
  }
}
```

```bash
docker compose exec node npm install
docker compose exec node npm run sass:watch
```

---

## 🔄 Hot Module Replacement (HMR)

Vite permite que cambios en CSS/JS se vean instantáneamente sin recargar la página.

### Configurar `vite.config.js`

```javascript
import { defineConfig } from 'vite'

export default defineConfig({
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      host: 'localhost',
      port: 5173
    }
  }
})
```

### Usar en HTML

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="http://localhost:5173/src/main.css">
</head>
<body>
    <h1>Mi App</h1>
    <script type="module" src="http://localhost:5173/src/main.js"></script>
</body>
</html>
```

---

## 📋 Comandos Útiles

| Comando | Descripción |
|---------|-------------|
| `npm install` | Instalar dependencias |
| `npm install <pkg>` | Instalar nuevo paquete |
| `npm update` | Actualizar paquetes |
| `npm run dev` | Dev server (Vite) |
| `npm run build` | Build producción |
| `npm list` | Listar dependencias |
| `npm uninstall <pkg>` | Desinstalar paquete |

---

## 🔗 Integración con PHP

### Referencia desde PHP

```php
<!-- En tu HTML generado por PHP -->
<link rel="stylesheet" href="/public/css/style.css">
<script src="/public/js/app.js"></script>
```

### Con Vite + PHP

Si usas Vite con HMR:

```php
<?php if (getenv('APP_ENV') === 'development'): ?>
    <!-- Dev: Hot reload habilitado -->
    <script type="module" src="http://localhost:5173/src/main.js"></script>
    <link rel="stylesheet" href="http://localhost:5173/src/main.css">
<?php else: ?>
    <!-- Producción: Assets compilados -->
    <link rel="stylesheet" href="/public/css/main.css">
    <script src="/public/js/main.js"></script>
<?php endif; ?>
```

---

## 🐛 Troubleshooting

### Problema: npm install falla

```bash
# Verifica que node esté corriendo
docker compose ps node

# Accede al contenedor manualmente
docker compose exec node sh
```

### Problema: Dev server no responde

```bash
# Verifica puerto 5173
docker compose logs node

# Reinicia
docker compose restart node
```

### Problema: HMR no funciona

1. Verifica `vite.config.js`
2. Recarga página manualmente
3. Verifica que localhost:5173 sea accesible

---

## 📚 Recursos

- [Vite Docs](https://vitejs.dev/)
- [npm Docs](https://docs.npmjs.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Webpack](https://webpack.js.org/)

---

**Siguiente paso**: [Nginx vs Apache](./07-NGINX-APACHE.md)
