# 📧 Email Testing con Mailpit

Interceptar y visualizar emails en desarrollo sin enviar realmente.

---

## 🎯 ¿Qué es Mailpit?

Mailpit es un servidor SMTP + web UI que:
- ✅ Captura todos los emails que tu app intenta enviar
- ✅ Los visualiza en un dashboard web bonito
- ✅ No envía emails realmente (no necesita credenciales)
- ✅ Perfecto para development y testing

**Puertos:**
- Web UI: http://localhost:8805
- SMTP: localhost:8825

---

## 📧 Configurar PHP para usar Mailpit

### Opción 1: Cambiar `php.ini` (No recomendado)

```ini
[mail]
sendmail_path = "/var/spool/postfix/sendmail -S mailpit:1025"
```

### Opción 2: Usar librería (Recomendado)

Instala PHPMailer o SwiftMailer via Composer:

```bash
docker compose exec php composer require phpmailer/phpmailer
```

Luego en tu código:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer();

// SMTP de Mailpit
$mail->isSMTP();
$mail->Host = 'mailpit';
$mail->Port = 1025;
$mail->SMTPAuth = false;  // Sin autenticación

$mail->setFrom('noreply@example.com', 'Mi App');
$mail->addAddress('usuario@example.com');
$mail->Subject = 'Test Email';
$mail->Body = 'Este es un test';

if($mail->send()) {
    echo 'Email enviado a Mailpit';
} else {
    echo 'Error: ' . $mail->ErrorInfo;
}
?>
```

### Opción 3: mail() function (Simple)

```php
<?php
// En docker/php/php.ini o en tu código
ini_set('SMTP', 'mailpit');
ini_set('smtp_port', '1025');

mail('usuario@example.com', 'Asunto', 'Contenido');
?>
```

---

## 🌐 Acceder a Mailpit

1. Abre http://localhost:8805 en tu navegador
2. Verás el dashboard con los emails capturados
3. Haz clic en un email para verlo completo

---

## 📨 Configuración SMTP

Si tu código necesita credenciales SMTP:

```
Host: mailpit
Puerto: 1025
Usuario: (ninguno)
Contraseña: (ninguna)
TLS/SSL: No
```

Desde PHP:
```php
$mail->Host = 'mailpit';
$mail->Port = 1025;
$mail->SMTPAuth = false;
```

Desde .env:
```bash
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
```

---

## 🧪 Test Script

Crea `src/test-mail.php`:

```php
<?php
echo "<h1>Test Mailpit</h1>";

// Sin librería
ini_set('SMTP', 'mailpit');
ini_set('smtp_port', '1025');

$to = 'test@example.com';
$subject = 'Test Email from ' . date('Y-m-d H:i:s');
$message = 'Este es un email de prueba con timestamp: ' . date('Y-m-d H:i:s');
$headers = "From: noreply@example.com\r\nContent-Type: text/html; charset=UTF-8";

if (mail($to, $subject, $message, $headers)) {
    echo "<p style='color: green;'>✅ Email enviado a Mailpit</p>";
    echo "<p>Revisa <a href='http://localhost:8805' target='_blank'>Mailpit</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error al enviar</p>";
}
?>
```

Accede a: http://localhost:8801/test-mail.php

---

## 🔍 Características de Mailpit

### Ver Email Completo
- Asunto, De, Para
- Cuerpo HTML y texto
- Attachments (si los hay)

### Release Email
Botón "Release" para enviar realmente (si lo necesitas)

### Buscar Emails
- Por remitente
- Por destinatario
- Por asunto

### API REST (Avanzado)
```bash
curl http://localhost:8805/api/messages
```

---

## 🗑️ Limpiar Mailpit

Para eliminar todos los emails capturados:

```bash
docker compose exec mailpit rm -rf /data/mailpit.db
docker compose restart mailpit
```

O usa la UI (botón Delete all).

---

## 🆘 Troubleshooting

### Problema: Email no se envía

Verifica que Mailpit esté corriendo:
```bash
docker compose ps mailpit
```

Debe estar "Up".

### Problema: Email no aparece en dashboard

1. Recarga la página (F5)
2. Verifica que estés usando puerto 1025
3. Revisa logs: `docker compose logs mailpit`

### Problema: SMTP connection refused

Verifica el nombre del host:
```bash
docker compose exec php ping mailpit
```

Debe responder.

---

## 📋 Resumen

| Tarea | Comando/URL |
|-------|------------|
| Ver emails | http://localhost:8805 |
| Host SMTP | mailpit |
| Puerto SMTP | 1025 |
| Autenticación | No requerida |
| Test | `curl mailpit:1025` (desde contenedor) |

---

**Siguiente paso**: [Gestión de Base de Datos](./09-DATABASE-MANAGEMENT.md)
