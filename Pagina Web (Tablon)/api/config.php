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
define('DB_HOST', 'localhost');
define('DB_NAME', 'tablon');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Poné esto en false una vez que la app funcione en producción,
 * para que los errores no se muestren nunca al público.
 */
define('APP_DEBUG', true);
