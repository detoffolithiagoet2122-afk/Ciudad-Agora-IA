<?php
/**
 * Datos de conexión a la base de datos MySQL.
 *
 * Estos valores YA están puestos para XAMPP en tu compu (usuario root,
 * sin contraseña). Sólo tenés que asegurarte de haber creado en
 * phpMyAdmin (http://localhost/phpmyadmin) una base llamada "tablon"
 * e importado ahí api/schema.sql.
 *
 * Si más adelante subís esto a un hosting real (cPanel), vas a tener
 * que cambiar estos 4 valores por los que te dé tu hosting.
 */
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

/**
 * Con esto en true, los errores le muestran el detalle técnico exacto a
 * cualquiera (y el mail de confirmación aparece como link visible en la
 * pantalla, en vez de depender solo del mail real). Sirve para probar en
 * XAMPP local, pero NO tiene que quedar así en un sitio público — ya que
 * tu dominio está en línea y la gente lo está usando, esto va en false.
 */
define('APP_DEBUG', false);

/**
 * La URL de tu sitio, CON barra al final. Se usa para armar el link que
 * le mandamos por mail a la gente para que confirme su Gmail.
 *
 * - En tu dominio real, poné algo como: 'https://tudominio.com/'
 * - En XAMPP local (para probar) sería algo como: 'http://localhost/Agora-IA/'
 *
 * Importante: en XAMPP local, mandar mails de verdad normalmente NO
 * funciona sin configuración extra (XAMPP no trae un servidor de correo
 * configurado). Por eso, mientras APP_DEBUG esté en true, la API te
 * devuelve el link de verificación directo en la respuesta para que
 * puedas probarlo sin depender del mail real. En tu hosting con dominio
 * real, el mail sí debería salir andando.
 */
define('SITE_URL', '');

/**
 * Brevo (para mandar el mail de confirmación de verdad).
 * InfinityFree bloquea mail() y SMTP, así que usamos la API de Brevo por HTTPS.
 *
 * - BREVO_SENDER_EMAIL: el mail que verificaste como remitente en Brevo
 *   (Senders, Domains & Dedicated IPs → tu mail confirmado).
 * - BREVO_API_KEY: la sacás en Brevo → tu perfil (arriba a la derecha) →
 *   SMTP & API → pestaña "API Keys" → "Generate a new API key".
 */
define('BREVO_SENDER_EMAIL', '');
define('BREVO_SENDER_NAME', '');
define('BREVO_API_KEY', '');