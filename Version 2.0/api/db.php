<?php
require_once __DIR__ . '/config.php';

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Igual que slugify() del lado del cliente, para IDs de categorías nuevas. */
function slugify(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($translit !== false) $s = $translit;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s;
}

/** "hace 3 horas", "hace 2 días", etc. a partir de un DATETIME de MySQL. */
function timeAgo(string $mysqlDatetime): string {
    $then = strtotime($mysqlDatetime);
    $diff = time() - $then;
    if ($diff < 60) return 'ahora';
    if ($diff < 3600) { $m = intdiv($diff, 60); return "hace $m minuto" . ($m === 1 ? '' : 's'); }
    if ($diff < 86400) { $h = intdiv($diff, 3600); return "hace $h hora" . ($h === 1 ? '' : 's'); }
    $d = intdiv($diff, 86400);
    return "hace $d día" . ($d === 1 ? '' : 's');
}
