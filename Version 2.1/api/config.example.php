<?php
// =====================================================================
// Tablón — configuración (EJEMPLO, sin datos reales)
// Copiá este archivo como "config.php" en la misma carpeta y completá
// tus datos. config.php NO se sube a GitHub (está en .gitignore).
// =====================================================================

// Base de datos MySQL (en InfinityFree: panel → MySQL Databases)
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_XXXXXXXX_tablon');
define('DB_USER', 'if0_XXXXXXXX');
define('DB_PASS', 'TU_CONTRASEÑA_DE_LA_BASE');

// En producción dejalo en false (si es true muestra los errores en pantalla)
define('APP_DEBUG', false);

// Dirección del sitio (se usa en los links de los mails)
define('SITE_URL', 'https://tu-sitio.com');

// Brevo (mails de verificación y de recuperar contraseña)
define('BREVO_SENDER_EMAIL', 'tu-mail@ejemplo.com');
define('BREVO_SENDER_NAME', 'Tablón');
define('BREVO_API_KEY', 'TU_API_KEY_DE_BREVO');
