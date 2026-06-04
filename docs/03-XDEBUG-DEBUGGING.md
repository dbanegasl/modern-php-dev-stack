# 🐛 Debugging con Xdebug en VS Code

Guía completa para hacer debugging paso a paso de tu código PHP con breakpoints en tiempo real.

---

## 🎯 Qué es Xdebug

Xdebug es una extensión PHP que permite:
- ⏸️ Pausar la ejecución en puntos específicos (breakpoints)
- 👀 Inspeccionar variables en tiempo real
- 🔄 Saltar línea por línea (step over, step into)
- 📊 Ver stack de llamadas
- 🔍 Evaluar expresiones

**Versión instalada**: Xdebug 3.x  
**Puerto**: 9003  
**Host**: host.docker.internal (tu máquina)

---

## 📋 Requisitos

1. **VS Code** instalado
2. **Extensión PHP Debug** instalada
   - ID: `felixbecker.php-debug`
   - Descárgala desde: https://marketplace.visualstudio.com/items?itemName=felixbecker.php-debug

3. **El proyecto abierto en VS Code**
   ```bash
   cd /ruta/a/modernPhpDevStack
   code .
   ```

---

## 🔧 Configuración Inicial

### 1. Verificar que Xdebug está habilitado

```bash
docker compose exec php php -m | grep -i xdebug
```

Deberías ver: `Xdebug`

### 2. Verificar configuración de Xdebug

```bash
docker compose exec php php -r "phpinfo();" | grep -A 5 "xdebug"
```

Esperado:
```
xdebug.mode => debug,develop => debug,develop
xdebug.client_host => host.docker.internal => host.docker.internal
xdebug.client_port => 9003 => 9003
```

---

## 🚀 Paso a Paso: Tu Primer Debug

### Paso 1: Abre el Proyecto en VS Code

```bash
code /home/daniel/dev/docker/modernPhpDevStack
```

### Paso 2: Verifica que launch.json existe

Abre [`.vscode/launch.json`](.vscode/launch.json) (en la raíz del proyecto).

Debe contener:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug (Docker)",
            "type": "php",
            "port": 9003,
            "pathMapping": {
                "/var/www/html": "${workspaceFolder}/src"
            }
        }
    ]
}
```

**¿No existe?** Créalo:
- Abre la paleta de comandos: `Ctrl+Shift+P` (Win/Linux) o `Cmd+Shift+P` (Mac)
- Escribe: `Debug: Add Configuration`
- Selecciona `PHP`
- Edítalo manualmente para que quede como arriba

### Paso 3: Coloca un Breakpoint

Abre `src/index.php` y busca una línea donde quieras pausar (por ejemplo, línea 54).

Haz clic en el número de línea en el gutter (margen izquierdo):
```
54 • $_SERVER ...    <- Haz clic aquí para colocar el punto rojo
```

Deberías ver un **punto rojo** en esa línea.

### Paso 4: Inicia el Listener de Xdebug

En VS Code:
1. Abre el panel **Run & Debug** (o presiona `Ctrl+Shift+D`)
2. En el dropdown, selecciona **"Listen for Xdebug (Docker)"**
3. Presiona el botón ▶️ verde o presiona **F5**

Verás en la parte inferior:
```
Listening on :9003...
```

### Paso 5: Activa el Breakpoint desde el Navegador

Abre en tu navegador: http://localhost:8801

Verás el dashboard. Busca el botón morado **⚡ Trigger Breakpoint Test** y haz clic.

### Paso 6: ¡Pausa en VS Code!

Automáticamente:
1. VS Code se pone en primer plano
2. La línea 54 se destaca en naranja (pausa)
3. Abre el panel de variables (izquierda) para inspeccionar

---

## 🎮 Controles de Debug

Una vez pausado en un breakpoint, puedes:

| Control | Atajo | Función |
|---------|-------|---------|
| **Continue** | `F5` o ▶️ | Continúa hasta el próximo breakpoint |
| **Step Over** | `F10` | Ejecuta la línea actual (sin entrar en funciones) |
| **Step Into** | `F11` | Entra en la función llamada |
| **Step Out** | `Shift+F11` | Sale de la función actual |
| **Stop** | `Shift+F5` | Detiene el debugging |
| **Restart** | `Ctrl+Shift+F5` | Reinicia el debugging |

---

## 👀 Inspeccionar Variables

### Variables Locales (Panel Izquierdo)

Cuando estés pausado, en la sección **Variables**, verás:
- **Local**: Variables del scope actual
- **Global**: Variables globales ($_SERVER, $_GET, etc.)
- **Static**: Variables estáticas

Haz clic en `▶️` para expandir y ver contenidos.

Ejemplo:
```
Local
  $_GET: Array
    ▶️ id: "123"
    ▶️ name: "John"
  $user: Object
    ▶️ id: 1
    ▶️ email: "john@example.com"
```

### Watch Expressions (Vigilancia)

Para vigilar expresiones específicas:

1. En el panel **Run & Debug**, ve a la sección **Watch**
2. Haz clic en **+** para agregar una expresión
3. Escribe lo que quieres vigilar: `$_GET['id']`, `$user->email`, etc.

Verás el valor actualizado en cada línea.

### Debug Console

Para ejecutar código PHP en tiempo real:
1. Abre la **Debug Console** (parte inferior)
2. Escribe código PHP:
   ```
   $new_var = $_GET['id'] * 2
   var_dump($user)
   ```

---

## 📌 Tipos de Breakpoints

### 1. Breakpoint Normal (Rojo)

Pausa siempre que se alcance esa línea.

```php
54 • $_SERVER ...    <- Punto rojo: pausa aquí
```

### 2. Breakpoint Condicional

Pausa solo si una condición es verdadera.

Haz clic derecho en el número de línea → **Add Conditional Breakpoint**

```
Condition: $_GET['id'] == '123'
```

Pausa solo si id es '123'.

### 3. Logpoint

En lugar de pausar, imprime un mensaje en la consola.

Haz clic derecho → **Add Logpoint**

```
Message: Current value: {$_GET['id']}
```

---

## 🔍 Debugging Avanzado

### Debuggear Funciones Específicas

Si quieres debuggear una función sin breakpoints manuales:

```php
function procesarUsuario($id) {
    xdebug_break();  // Pausa automáticamente aquí
    
    // ... código ...
}
```

### Debug de AJAX/API Requests

Para debuggear requests AJAX:

1. En el navegador, abre **DevTools** (F12)
2. Ve a **Network**
3. Haz clic en el request que quieres debuggear
4. Si Xdebug está escuchando, pausará automáticamente

### Debug de Línea Específica

En lugar de mantener el listener todo el tiempo, puedes debuggear solo cuando necesites:

```bash
# Trigger debugging para próxima request
docker compose exec php php -r "
var_dump(xdebug_info());
"
```

---

## 🆘 Troubleshooting

### Problema: "Listening" pero no pausa

**Solución:**
1. Verifica que el listener esté activo (`Listening on :9003`)
2. Verifica que Xdebug está habilitado: `docker compose exec php php -m | grep xdebug`
3. Recarga el navegador

### Problema: "Could not connect to debugging client"

Este mensaje es normal y no afecta la ejecución. Significa:
- Xdebug intentó conectar pero no había un listener activo
- Solución: Inicia el listener en VS Code antes de cargar la página

### Problema: Breakpoint no pausa

**Causas:**
1. El listener no está escuchando (revisa VS Code)
2. El path mapping es incorrecto en `launch.json`
3. El archivo fue editado pero no guardado

**Solución:**
- Presiona `Ctrl+S` para guardar
- Recarga la página en el navegador
- Verifica que VS Code muestre `Listening on :9003`

### Problema: Variables no se muestran

Si el panel de variables está vacío:
1. Abre la **Debug Console** (parte inferior)
2. Tipo `var_dump($variable_name)`
3. Presiona Enter

---

## 💡 Tips & Tricks

### 1. Breakpoint Rápido
Presiona `Ctrl+Shift+P` → "Debug: Add Breakpoint" para crear uno sin mouse.

### 2. Saltar a Línea
En la paleta de comandos: `Debug: Go to Line`

### 3. Reintentos Rápidos
Después de corregir código:
1. Presiona `Shift+F5` para reiniciar
2. O presiona `F5` para continuar desde donde pausó

### 4. Debug de Variable Compleja
```php
$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
// En Debug Console: var_dump($data['user']['name'])
```

### 5. Debuggear desde CLI
```bash
# Ejecutar PHP con debugging habilitado
docker compose exec -e XDEBUG_CONFIG="idekey=vscode" php php script.php
```

---

## 🎓 Ejemplo Práctico Completo

### Archivo: `src/debug-example.php`

```php
<?php
// Debug Example: Debugging paso a paso

$usuarios = [
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
    ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
];

// BREAKPOINT AQUÍ (línea 12) - Para inspeccionar $usuarios
foreach ($usuarios as $usuario) {
    // BREAKPOINT AQUÍ (línea 14) - Para ver cada usuario
    $usuario['email_upper'] = strtoupper($usuario['email']);
    
    // BREAKPOINT AQUÍ (línea 17) - Para ver el resultado
    echo $usuario['name'] . ': ' . $usuario['email_upper'] . '<br>';
}

echo '<h2>Debug completado</h2>';
?>
```

### Pasos:

1. Coloca 3 breakpoints en las líneas indicadas
2. Abre `F5` para iniciar debug
3. Accede a http://localhost:8801/debug-example.php
4. Pausa en línea 12: inspecciona `$usuarios`
5. Presiona `F10` para step over
6. Pausa en línea 14: inspecciona cada usuario
7. Presiona `F10` nuevamente
8. Pausa en línea 17: ve el resultado final
9. Presiona `F5` para continuar

---

## 📚 Recursos

- [Documentación oficial Xdebug](https://xdebug.org/)
- [PHP Debug Extension para VS Code](https://marketplace.visualstudio.com/items?itemName=felixbecker.php-debug)
- [Debugging en VS Code](https://code.visualstudio.com/docs/editor/debugging)

---

**¡Ahora eres un experto en debugging!** 

Próximo paso: Explora [Gestión de Secretos](./04-SECRETS-MANAGEMENT.md) para entender cómo manejar credenciales de forma segura.
