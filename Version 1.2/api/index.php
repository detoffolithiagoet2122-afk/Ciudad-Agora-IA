<?php
/**
 * API de Tablón — un único endpoint con "acciones".
 * El frontend le pega a este archivo con POST { action: "...", ...datos }.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // descomentá esto si tu dominio ya tiene HTTPS (recomendado)
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

function ok(array $data = []): void {
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $error, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
function requireLogin(): int {
    $id = currentUserId();
    if ($id === null) fail('Tenés que iniciar sesión.', 401);
    return $id;
}
function isMember(PDO $pdo, int $groupId, int $userId): bool {
    $st = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?');
    $st->execute([$groupId, $userId]);
    return (bool)$st->fetchColumn();
}

function isGroupAdmin(PDO $pdo, int $groupId, int $userId): bool {
    $st = $pdo->prepare('SELECT is_admin FROM group_members WHERE group_id=? AND user_id=?');
    $st->execute([$groupId, $userId]);
    return (bool)$st->fetchColumn();
}

// Convierte una fecha+hora de verdad (DATETIME) en el texto lindo que ya
// veníamos mostrando, tipo "Jue 28/08 · 21:00".
function formatMeetupDate(string $eventAt): string {
    $ts = strtotime($eventAt);
    $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $dia = $dias[(int)date('w', $ts)];
    return $dia . ' ' . date('d/m', $ts) . ' · ' . date('H:i', $ts);
}

// Junta una fecha (YYYY-MM-DD, de un <input type="date">) y una hora
// (HH:MM, de un <input type="time">) y valida que sea una fecha real.
// Devuelve el DATETIME armado, o null si algo vino mal formado.
function buildEventAt(string $date, string $time): ?string {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return null;
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) return null;
    $candidate = $date . ' ' . $time . ':00';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $candidate);
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $candidate) return null;
    return $candidate;
}

// Lista simple de palabras a censurar. No hace falta IA para esto: alcanza
// con reemplazar coincidencias exactas de palabra completa (no dentro de
// otras palabras) por asteriscos, sin importar mayúsculas/tildes.
function censorText(string $text): string {
    static $banned = [
        'puto','puta','putos','putas','pelotudo','pelotuda','pelotudos','pelotudas',
        'boludo','boluda','boludos','boludas','forro','forra','forros','forras',
        'imbecil','imbeciles','idiota','idiotas','estupido','estupida','estupidos','estupidas',
        'tarado','tarada','tarados','taradas','gil','gila','giles','pendejo','pendeja','pendejos',
        'trolo','trolos','conchudo','conchuda','conchudos',
    ];
    // Normalizamos tildes para poder comparar "estúpido" y "estupido" igual.
    $normalize = function (string $s): string {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $t !== false ? strtolower($t) : strtolower($s);
    };
    $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($words as &$w) {
        $bare = preg_replace('/[^\p{L}\p{N}]+/u', '', $w);
        if ($bare === '') continue;
        if (in_array($normalize($bare), $banned, true)) {
            // Reemplazamos solo la parte alfanumérica, dejando signos de puntuación pegados.
            $w = preg_replace('/\p{L}|\p{N}/u', '*', $w);
        }
    }
    return implode('', $words);
}

// Manda el mail con el link de confirmación usando la API de Brevo (por HTTPS,
// puerto 443), porque InfinityFree bloquea mail() y las conexiones SMTP salientes.
function sendVerificationEmail(string $to, string $name, string $link): bool {
    $payload = [
        'sender'      => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
        'to'          => [['email' => $to, 'name' => $name]],
        'subject'     => 'Confirma tu cuenta de Tablon',
        'htmlContent' => '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"></head>'
            . '<body style="margin:0;padding:0">'
            . '<div style="font-family:sans-serif;font-size:15px;color:#14161f">'
            . '<p>Hola ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '!</p>'
            . '<p>Gracias por crear tu cuenta en Tabl&oacute;n. Para poder entrar, confirm&aacute; tu mail '
            . 'tocando el bot&oacute;n de abajo (el link vale por 24 horas):</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:inline-block;background:#ff3d6e;color:#fff;text-decoration:none;'
            . 'font-weight:bold;padding:12px 22px;border-radius:4px;margin:10px 0">Confirmar mi cuenta</a></p>'
            . '<p style="font-size:12px;color:#666">Si el bot&oacute;n no funciona, copi&aacute; y peg&aacute; este link en el navegador:<br>'
            . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-size:12px;color:#666">Si vos no creaste esta cuenta, pod&eacute;s ignorar este mensaje.</p>'
            . '</div></body></html>',
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Si falla, no rompemos el registro (el link de debug sigue sirviendo en
    // modo APP_DEBUG), pero dejamos rastro en el log del servidor para poder
    // ver qué pasó (revisalo en el panel de errores de tu hosting).
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('Brevo: no se pudo mandar el mail a ' . $to . ' — HTTP ' . $httpCode . ' — ' . $curlError . ' — ' . $response);
        return false;
    }
    return true;
}

// Página simple (HTML, no JSON) que ve la persona cuando toca el link del mail.
function verifyPageHTML(bool $success, string $message): string {
    $color = $success ? '#38c98d' : '#ff3d6e';
    $title = $success ? '¡Listo!' : 'Ups...';
    $siteUrl = htmlspecialchars(SITE_URL, ENT_QUOTES);
    $msg = htmlspecialchars($message, ENT_QUOTES);
    return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
      . '<meta name="viewport" content="width=device-width, initial-scale=1">'
      . '<title>Tablón — verificación de mail</title>'
      . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
      . 'background:#14161f;color:#f1ecdd;font-family:sans-serif;padding:20px;box-sizing:border-box}'
      . '.card{background:#f1ecdd;color:#14161f;max-width:420px;width:100%;padding:32px 28px;border-radius:6px;'
      . 'border:2.5px solid #14161f;text-align:center}'
      . 'h1{margin:0 0 12px;color:' . $color . '}p{line-height:1.5;margin:0 0 20px}'
      . 'a{display:inline-block;background:#ff3d6e;color:#f1ecdd;text-decoration:none;font-weight:bold;'
      . 'padding:12px 22px;border-radius:4px;border:2.5px solid #14161f}</style></head><body>'
      . '<div class="card"><h1>' . $title . '</h1><p>' . $msg . '</p>'
      . '<a href="' . $siteUrl . '">Volver a Tablón</a></div></body></html>';
}

$raw   = file_get_contents('php://input');
$input = [];
if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}
$action = $input['action'] ?? ($_GET['action'] ?? '');

try {
    $pdo = getPDO();

    switch ($action) {

        // ---------- diagnóstico rápido: /api/index.php?action=ping ----------
        case 'ping':
            ok(['php' => PHP_VERSION, 'db' => 'conectado']);
            break;

        // ---------- estado inicial de toda la app ----------
        case 'bootstrap': {
            $categories = $pdo->query('SELECT id, label FROM categories ORDER BY label')->fetchAll();

            $users = $pdo->query('SELECT id, name, handle, color_idx FROM users ORDER BY id')->fetchAll();
            $userCats = $pdo->query('SELECT user_id, category_id FROM user_categories')->fetchAll();
            $friendRows = $pdo->query('SELECT user_id, friend_id, status FROM friends')->fetchAll();

            $catsByUser = [];
            foreach ($userCats as $r) $catsByUser[$r['user_id']][] = $r['category_id'];

            // Solo cuenta como "amigo" si la solicitud ya fue aceptada.
            $friendsByUser = [];
            foreach ($friendRows as $r) {
                if ($r['status'] === 'accepted') {
                    $friendsByUser[$r['user_id']][] = (string)$r['friend_id'];
                }
            }

            $usersOut = array_map(function ($u) use ($catsByUser, $friendsByUser) {
                return [
                    'id' => (string)$u['id'],
                    'name' => $u['name'],
                    'handle' => $u['handle'],
                    'colorIdx' => (int)$u['color_idx'],
                    'categories' => $catsByUser[$u['id']] ?? [],
                    'friends' => $friendsByUser[$u['id']] ?? [],
                ];
            }, $users);

            $groups = $pdo->query('SELECT id, name, category_id, zone, description FROM `groups` ORDER BY id')->fetchAll();
            $members = $pdo->query('SELECT group_id, user_id, is_admin FROM group_members')->fetchAll();
            $meetups = $pdo->query('SELECT id, group_id, title, date_label, event_at, place, created_by, edited_at FROM meetups ORDER BY (event_at IS NULL) ASC, event_at ASC, id DESC')->fetchAll();
            $going = $pdo->query('SELECT meetup_id, user_id FROM meetup_going')->fetchAll();
            $posts = $pdo->query('SELECT id, group_id, author_id, body, created_at, edited_at FROM posts ORDER BY id DESC')->fetchAll();
            $comments = $pdo->query('SELECT id, post_id, author_id, body, created_at FROM post_comments ORDER BY id ASC')->fetchAll();

            $membersByGroup = [];
            $adminsByGroup = [];
            foreach ($members as $r) {
                $membersByGroup[$r['group_id']][] = (string)$r['user_id'];
                if ((int)$r['is_admin'] === 1) $adminsByGroup[$r['group_id']][] = (string)$r['user_id'];
            }
            $goingByMeetup = [];
            foreach ($going as $r) $goingByMeetup[$r['meetup_id']][] = (string)$r['user_id'];
            $meetupsByGroup = [];
            foreach ($meetups as $m) {
                $meetupsByGroup[$m['group_id']][] = [
                    'id' => (string)$m['id'],
                    'title' => $m['title'],
                    'date' => $m['date_label'],
                    'eventDate' => $m['event_at'] !== null ? date('Y-m-d', strtotime($m['event_at'])) : null,
                    'eventTime' => $m['event_at'] !== null ? date('H:i', strtotime($m['event_at'])) : null,
                    'place' => $m['place'],
                    'going' => $goingByMeetup[$m['id']] ?? [],
                    'createdBy' => $m['created_by'] !== null ? (string)$m['created_by'] : null,
                    'edited' => $m['edited_at'] !== null,
                ];
            }
            $commentsByPost = [];
            foreach ($comments as $c) {
                $commentsByPost[$c['post_id']][] = [
                    'id' => (string)$c['id'],
                    'authorId' => $c['author_id'] !== null ? (string)$c['author_id'] : null,
                    'text' => $c['body'],
                    'time' => timeAgo($c['created_at']),
                ];
            }
            $postsByGroup = [];
            foreach ($posts as $p) {
                $postsByGroup[$p['group_id']][] = [
                    'id' => (string)$p['id'],
                    'authorId' => $p['author_id'] !== null ? (string)$p['author_id'] : null,
                    'text' => $p['body'],
                    'time' => timeAgo($p['created_at']),
                    'edited' => $p['edited_at'] !== null,
                    'comments' => $commentsByPost[$p['id']] ?? [],
                ];
            }

            $groupsOut = array_map(function ($g) use ($membersByGroup, $adminsByGroup, $meetupsByGroup, $postsByGroup) {
                return [
                    'id' => (string)$g['id'],
                    'name' => $g['name'],
                    'categoryId' => $g['category_id'],
                    'zone' => $g['zone'],
                    'description' => $g['description'],
                    'members' => $membersByGroup[$g['id']] ?? [],
                    'admins' => $adminsByGroup[$g['id']] ?? [],
                    'meetups' => $meetupsByGroup[$g['id']] ?? [],
                    'posts' => $postsByGroup[$g['id']] ?? [],
                ];
            }, $groups);

            $cuid = currentUserId();

            // Solicitudes pendientes que le llegaron y que mandó el usuario actual.
            $incomingRequests = [];
            $outgoingRequests = [];
            if ($cuid !== null) {
                foreach ($friendRows as $r) {
                    if ($r['status'] !== 'pending') continue;
                    if ((int)$r['friend_id'] === $cuid) $incomingRequests[] = (string)$r['user_id'];
                    if ((int)$r['user_id'] === $cuid) $outgoingRequests[] = (string)$r['friend_id'];
                }
            }

            ok([
                'categories' => $categories,
                'users' => $usersOut,
                'groups' => $groupsOut,
                'currentUserId' => $cuid !== null ? (string)$cuid : null,
                'incomingRequests' => $incomingRequests,
                'outgoingRequests' => $outgoingRequests,
            ]);
            break;
        }

        // ---------- autenticación ----------
        case 'register': {
            $name = trim((string)($input['name'] ?? ''));
            $handleRaw = strtolower(trim(str_replace('@', '', (string)($input['handle'] ?? ''))));
            $email = strtolower(trim((string)($input['email'] ?? '')));
            $password = (string)($input['password'] ?? '');
            $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];

            if ($name === '' || $handleRaw === '') fail('Falta el nombre o el usuario.');
            if ($email === '' || !preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) fail('Ingresá un Gmail válido.');
            if (strlen($password) < 4) fail('La contraseña tiene que tener al menos 4 caracteres.');

            $handle = '@' . $handleRaw;
            $st = $pdo->prepare('SELECT id FROM users WHERE LOWER(handle)=LOWER(?)');
            $st->execute([$handle]);
            if ($st->fetch()) fail('Ese usuario ya existe, probá con otro.');

            $st = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?)');
            $st->execute([$email]);
            if ($st->fetch()) fail('Ese Gmail ya tiene una cuenta. Probá iniciar sesión.');

            $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(20));
            $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24); // 24hs

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO users (name, handle, email, password_hash, color_idx, email_verify_token, email_verify_expires) VALUES (?,?,?,?,?,?,?)');
            $ins->execute([$name, $handle, $email, $hash, $count % 5, $token, $expires]);
            $uid = (int)$pdo->lastInsertId();

            if (!empty($categories)) {
                $insCat = $pdo->prepare('INSERT IGNORE INTO user_categories (user_id, category_id) VALUES (?,?)');
                foreach ($categories as $c) $insCat->execute([$uid, (string)$c]);
            }
            $pdo->commit();

            $link = rtrim(SITE_URL, '/') . '/api/index.php?action=verify_email&token=' . $token;
            sendVerificationEmail($email, $name, $link);

            ok([
                'registered' => true,
                'email' => $email,
                // Solo viaja en modo debug (ver config.php), para poder probar sin mail real en XAMPP.
                'devVerifyLink' => APP_DEBUG ? $link : null,
            ]);
            break;
        }

        case 'login': {
            $identifier = trim((string)($input['handle'] ?? ''));
            $password = (string)($input['password'] ?? '');
            if ($identifier === '') fail('Ingresá tu usuario o tu Gmail.');

            // Si tiene forma de mail (algo@algo.algo), buscamos por email;
            // si no, lo tratamos como @usuario (como hacía antes).
            $looksLikeEmail = (bool)preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $identifier);

            if ($looksLikeEmail) {
                $st = $pdo->prepare('SELECT id, name, password_hash, email, email_verified_at FROM users WHERE LOWER(email) = LOWER(?)');
                $st->execute([$identifier]);
            } else {
                $handleRaw = strtolower(trim(str_replace('@', '', $identifier)));
                $st = $pdo->prepare("SELECT id, name, password_hash, email, email_verified_at FROM users WHERE LOWER(REPLACE(handle,'@','')) = ?");
                $st->execute([$handleRaw]);
            }
            $u = $st->fetch();
            if (!$u) fail('No encontramos esa cuenta. Probá el acceso de invitade.');
            if (!password_verify($password, $u['password_hash'])) fail('Contraseña incorrecta.');

            // Solo bloqueamos por falta de confirmación a las cuentas que TIENEN
            // un mail cargado y todavía no lo confirmaron (las cuentas de ejemplo
            // no tienen mail, así que no las afecta esto).
            if ($u['email'] !== null && $u['email_verified_at'] === null) {
                fail('Todavía no confirmaste tu Gmail. Revisá tu casilla (y la carpeta de spam) y tocá el link que te mandamos.');
            }

            $_SESSION['user_id'] = (int)$u['id'];
            ok(['userId' => (string)$u['id'], 'name' => $u['name']]);
            break;
        }

        // Esta es la que se abre cuando la persona toca el link del mail.
        // Devuelve una página HTML (no JSON), porque la abre directo el navegador.
        case 'verify_email': {
            header('Content-Type: text/html; charset=utf-8');
            $token = trim((string)($input['token'] ?? $_GET['token'] ?? ''));
            if ($token === '') { echo verifyPageHTML(false, 'Falta el código de verificación en el link.'); exit; }

            $st = $pdo->prepare('SELECT id, email_verify_expires FROM users WHERE email_verify_token = ?');
            $st->execute([$token]);
            $u = $st->fetch();
            if (!$u) { echo verifyPageHTML(false, 'Ese link ya no es válido. Pedí que te reenvíen el mail de confirmación.'); exit; }

            if ($u['email_verify_expires'] !== null && strtotime($u['email_verify_expires']) < time()) {
                echo verifyPageHTML(false, 'Este link venció (valía 24hs). Pedí que te reenvíen el mail de confirmación.');
                exit;
            }

            $pdo->prepare('UPDATE users SET email_verified_at = NOW(), email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?')
                ->execute([$u['id']]);
            echo verifyPageHTML(true, 'Tu Gmail quedó confirmado. Ya podés volver a Tablón e iniciar sesión.');
            exit;
        }

        // Por si el mail no llegó o el link venció: pide uno nuevo.
        case 'resend_verification': {
            $email = strtolower(trim((string)($input['email'] ?? '')));
            if ($email === '') fail('Ingresá tu Gmail.');

            $st = $pdo->prepare('SELECT id, name, email_verified_at FROM users WHERE LOWER(email) = ?');
            $st->execute([$email]);
            $u = $st->fetch();
            if (!$u) fail('No encontramos una cuenta con ese Gmail.');
            if ($u['email_verified_at'] !== null) fail('Ese Gmail ya está confirmado, probá iniciar sesión.');

            $token = bin2hex(random_bytes(20));
            $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24);
            $pdo->prepare('UPDATE users SET email_verify_token=?, email_verify_expires=? WHERE id=?')
                ->execute([$token, $expires, $u['id']]);

            $link = rtrim(SITE_URL, '/') . '/api/index.php?action=verify_email&token=' . $token;
            sendVerificationEmail($email, $u['name'], $link);

            ok(['resent' => true, 'devVerifyLink' => APP_DEBUG ? $link : null]);
            break;
        }

        case 'guest_login': {
            $st = $pdo->prepare('SELECT id, name FROM users WHERE handle = ? LIMIT 1');
            $st->execute(['@biaferreyra']);
            $u = $st->fetch();
            if (!$u) fail('Todavía no hay datos de demo cargados (corré seed.php una vez).');
            $_SESSION['user_id'] = (int)$u['id'];
            ok(['userId' => (string)$u['id'], 'name' => $u['name']]);
            break;
        }

        case 'logout':
            $_SESSION = [];
            session_destroy();
            ok();
            break;

        // Guarda los temas elegidos en la pantalla de "¿qué te interesa?"
        // que aparece la primera vez que entrás (reemplaza los que tenías).
        case 'set_interests': {
            $uid = requireLogin();
            $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];
            $categories = array_values(array_unique(array_map('strval', $categories)));

            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM user_categories WHERE user_id=?')->execute([$uid]);
            if (!empty($categories)) {
                $insCat = $pdo->prepare('INSERT IGNORE INTO user_categories (user_id, category_id) VALUES (?,?)');
                foreach ($categories as $c) $insCat->execute([$uid, $c]);
            }
            $pdo->commit();
            ok();
            break;
        }

        // ---------- grupos ----------
        case 'create_group': {
            $uid = requireLogin();
            $name = trim((string)($input['name'] ?? ''));
            $zone = trim((string)($input['zone'] ?? ''));
            $desc = trim((string)($input['description'] ?? ''));
            $categoryId = (string)($input['categoryId'] ?? '');
            $customLabel = trim((string)($input['customCategory'] ?? ''));

            // Mismo límite que el contador del frontend, por si alguien lo saltea.
            $name = mb_substr($name, 0, 80);
            $desc = mb_substr($desc, 0, 600);

            if ($categoryId === '__custom') {
                if ($customLabel === '') fail('Escribí el nombre de tu tema.');
                $slug = slugify($customLabel) ?: ('tema-' . time());
                $st = $pdo->prepare('SELECT id FROM categories WHERE id=? OR LOWER(label)=LOWER(?)');
                $st->execute([$slug, $customLabel]);
                $existing = $st->fetchColumn();
                if ($existing) {
                    $categoryId = $existing;
                } else {
                    $pdo->prepare('INSERT INTO categories (id, label) VALUES (?,?)')->execute([$slug, $customLabel]);
                    $categoryId = $slug;
                }
            }
            if ($name === '' || $zone === '' || $desc === '' || $categoryId === '') fail('Completá todos los campos.');

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO `groups` (name, category_id, zone, description, created_by) VALUES (?,?,?,?,?)');
            $ins->execute([$name, $categoryId, $zone, $desc, $uid]);
            $gid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?,?,1)')->execute([$gid, $uid]);
            $pdo->commit();

            ok(['groupId' => (string)$gid]);
            break;
        }

        case 'join_group': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            $pdo->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?,?)')->execute([$gid, $uid]);
            ok();
            break;
        }

        case 'leave_group': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            $pdo->prepare('DELETE FROM group_members WHERE group_id=? AND user_id=?')->execute([$gid, $uid]);
            ok();
            break;
        }

        // ---------- encuentros ----------
        case 'create_meetup': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            if (!isMember($pdo, $gid, $uid)) fail('Tenés que ser miembro del grupo.', 403);
            $title = trim((string)($input['title'] ?? ''));
            $date = trim((string)($input['date'] ?? ''));
            $time = trim((string)($input['time'] ?? ''));
            $place = trim((string)($input['place'] ?? ''));
            if ($title === '' || $date === '' || $time === '' || $place === '') fail('Completá todos los campos del encuentro.');

            $eventAt = buildEventAt($date, $time);
            if ($eventAt === null) fail('La fecha o la hora no son válidas.');
            $label = formatMeetupDate($eventAt);

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO meetups (group_id, title, date_label, event_at, place, created_by) VALUES (?,?,?,?,?,?)');
            $ins->execute([$gid, $title, $label, $eventAt, $place, $uid]);
            $mid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO meetup_going (meetup_id, user_id) VALUES (?,?)')->execute([$mid, $uid]);
            $pdo->commit();

            ok(['meetupId' => (string)$mid]);
            break;
        }

        case 'toggle_going': {
            $uid = requireLogin();
            $mid = (int)($input['meetupId'] ?? 0);
            $gidSt = $pdo->prepare('SELECT group_id FROM meetups WHERE id=?');
            $gidSt->execute([$mid]);
            $meetupGroupId = $gidSt->fetchColumn();
            if (!$meetupGroupId || !isMember($pdo, (int)$meetupGroupId, $uid)) fail('Tenés que ser miembro del grupo.', 403);
            $st = $pdo->prepare('SELECT 1 FROM meetup_going WHERE meetup_id=? AND user_id=?');
            $st->execute([$mid, $uid]);
            if ($st->fetch()) {
                $pdo->prepare('DELETE FROM meetup_going WHERE meetup_id=? AND user_id=?')->execute([$mid, $uid]);
            } else {
                $pdo->prepare('INSERT IGNORE INTO meetup_going (meetup_id, user_id) VALUES (?,?)')->execute([$mid, $uid]);
            }
            ok();
            break;
        }

        // Solo lo puede editar quien creó el encuentro.
        case 'edit_meetup': {
            $uid = requireLogin();
            $mid = (int)($input['meetupId'] ?? 0);
            $title = trim((string)($input['title'] ?? ''));
            $date = trim((string)($input['date'] ?? ''));
            $time = trim((string)($input['time'] ?? ''));
            $place = trim((string)($input['place'] ?? ''));
            if ($title === '' || $date === '' || $time === '' || $place === '') fail('Completá todos los campos del encuentro.');

            $eventAt = buildEventAt($date, $time);
            if ($eventAt === null) fail('La fecha o la hora no son válidas.');
            $label = formatMeetupDate($eventAt);

            $st = $pdo->prepare('SELECT created_by FROM meetups WHERE id=?');
            $st->execute([$mid]);
            $row = $st->fetch();
            if (!$row) fail('Ese encuentro ya no existe.', 404);
            if ((int)$row['created_by'] !== $uid) fail('Solo podés editar los encuentros que creaste vos.', 403);

            $pdo->prepare('UPDATE meetups SET title=?, date_label=?, event_at=?, place=?, edited_at=NOW() WHERE id=?')
                ->execute([$title, $label, $eventAt, $place, $mid]);
            ok();
            break;
        }

        // Lo puede borrar quien lo creó, o un admin del grupo.
        case 'delete_meetup': {
            $uid = requireLogin();
            $mid = (int)($input['meetupId'] ?? 0);
            $st = $pdo->prepare('SELECT group_id, created_by FROM meetups WHERE id=?');
            $st->execute([$mid]);
            $row = $st->fetch();
            if (!$row) { ok(); break; }
            $isOwner = (int)$row['created_by'] === $uid;
            if (!$isOwner && !isGroupAdmin($pdo, (int)$row['group_id'], $uid)) {
                fail('Solo podés borrar los encuentros que creaste vos (o ser admin del grupo).', 403);
            }
            $pdo->prepare('DELETE FROM meetups WHERE id=?')->execute([$mid]);
            ok();
            break;
        }

        // ---------- muro ----------
        case 'create_post': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            if (!isMember($pdo, $gid, $uid)) fail('Tenés que ser miembro del grupo.', 403);
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '') fail('Escribí algo para publicar.');
            $text = censorText(mb_substr($text, 0, 500));
            $pdo->prepare('INSERT INTO posts (group_id, author_id, body) VALUES (?,?,?)')->execute([$gid, $uid, $text]);
            ok();
            break;
        }

        // Solo lo puede editar quien escribió el post.
        case 'edit_post': {
            $uid = requireLogin();
            $pid = (int)($input['postId'] ?? 0);
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '') fail('Escribí algo para publicar.');
            $text = censorText(mb_substr($text, 0, 500));

            $st = $pdo->prepare('SELECT author_id FROM posts WHERE id=?');
            $st->execute([$pid]);
            $row = $st->fetch();
            if (!$row) fail('Ese post ya no existe.', 404);
            if ((int)$row['author_id'] !== $uid) fail('Solo podés editar tus propios posts.', 403);

            $pdo->prepare('UPDATE posts SET body=?, edited_at=NOW() WHERE id=?')->execute([$text, $pid]);
            ok();
            break;
        }

        // Lo puede borrar quien lo escribió, o un admin del grupo.
        case 'delete_post': {
            $uid = requireLogin();
            $pid = (int)($input['postId'] ?? 0);
            $st = $pdo->prepare('SELECT group_id, author_id FROM posts WHERE id=?');
            $st->execute([$pid]);
            $row = $st->fetch();
            if (!$row) { ok(); break; }
            $isOwner = (int)$row['author_id'] === $uid;
            if (!$isOwner && !isGroupAdmin($pdo, (int)$row['group_id'], $uid)) {
                fail('Solo podés borrar tus propios posts (o ser admin del grupo).', 403);
            }
            $pdo->prepare('DELETE FROM posts WHERE id=?')->execute([$pid]);
            ok();
            break;
        }

        // Cualquier miembro del grupo puede comentar un post.
        case 'create_comment': {
            $uid = requireLogin();
            $pid = (int)($input['postId'] ?? 0);
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '') fail('Escribí algo para comentar.');
            $text = censorText(mb_substr($text, 0, 300));

            $st = $pdo->prepare('SELECT group_id FROM posts WHERE id=?');
            $st->execute([$pid]);
            $groupId = $st->fetchColumn();
            if (!$groupId) fail('Ese post ya no existe.', 404);
            if (!isMember($pdo, (int)$groupId, $uid)) fail('Tenés que ser miembro del grupo.', 403);

            $pdo->prepare('INSERT INTO post_comments (post_id, author_id, body) VALUES (?,?,?)')->execute([$pid, $uid, $text]);
            ok();
            break;
        }

        // Lo puede borrar quien lo escribió, o un admin del grupo.
        case 'delete_comment': {
            $uid = requireLogin();
            $cid = (int)($input['commentId'] ?? 0);
            $st = $pdo->prepare('SELECT pc.author_id, p.group_id FROM post_comments pc JOIN posts p ON p.id = pc.post_id WHERE pc.id=?');
            $st->execute([$cid]);
            $row = $st->fetch();
            if (!$row) { ok(); break; }
            $isOwner = (int)$row['author_id'] === $uid;
            if (!$isOwner && !isGroupAdmin($pdo, (int)$row['group_id'], $uid)) {
                fail('Solo podés borrar tus propios comentarios (o ser admin del grupo).', 403);
            }
            $pdo->prepare('DELETE FROM post_comments WHERE id=?')->execute([$cid]);
            ok();
            break;
        }

        // ---------- moderación de grupo ----------

        // Un admin del grupo puede hacer admin a otro miembro.
        case 'promote_admin': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            $target = (int)($input['userId'] ?? 0);
            if (!isGroupAdmin($pdo, $gid, $uid)) fail('Solo un admin del grupo puede hacer esto.', 403);
            if (!isMember($pdo, $gid, $target)) fail('Esa persona no es miembro del grupo.');
            $pdo->prepare('UPDATE group_members SET is_admin=1 WHERE group_id=? AND user_id=?')->execute([$gid, $target]);
            ok();
            break;
        }

        // Un admin puede sacarle el rol a otro admin (no te podés sacar a
        // vos mismo si sos el único admin que queda: el grupo se quedaría sin nadie).
        case 'demote_admin': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            $target = (int)($input['userId'] ?? 0);
            if (!isGroupAdmin($pdo, $gid, $uid)) fail('Solo un admin del grupo puede hacer esto.', 403);
            $countSt = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id=? AND is_admin=1');
            $countSt->execute([$gid]);
            $adminCount = (int)$countSt->fetchColumn();
            if ($adminCount <= 1) fail('No podés sacarle el rol al único admin que le queda al grupo.');
            $pdo->prepare('UPDATE group_members SET is_admin=0 WHERE group_id=? AND user_id=?')->execute([$gid, $target]);
            ok();
            break;
        }

        // Un admin puede expulsar a un miembro del grupo.
        case 'remove_member': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            $target = (int)($input['userId'] ?? 0);
            if (!isGroupAdmin($pdo, $gid, $uid)) fail('Solo un admin del grupo puede hacer esto.', 403);
            if ($target === $uid) fail('No podés expulsarte a vos mismo, usá "Salir del grupo".');
            $pdo->prepare('DELETE FROM group_members WHERE group_id=? AND user_id=?')->execute([$gid, $target]);
            ok();
            break;
        }

        // ---------- amigos ----------

        // Enviar una solicitud de amistad. Si la otra persona ya me había
        // mandado una solicitud a mí, se aceptan mutuamente al toque.
        case 'add_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            if (!$fid || $fid === $uid) { ok(); break; }

            $st = $pdo->prepare('SELECT status FROM friends WHERE user_id=? AND friend_id=?');
            $st->execute([$fid, $uid]);
            $reverseStatus = $st->fetchColumn();

            $pdo->beginTransaction();
            if ($reverseStatus === 'pending' || $reverseStatus === 'accepted') {
                // La otra persona ya me había mandado (o ya somos amigos): confirmamos ambos lados.
                $pdo->prepare("UPDATE friends SET status='accepted' WHERE user_id=? AND friend_id=?")->execute([$fid, $uid]);
                $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?,?,'accepted')
                               ON DUPLICATE KEY UPDATE status='accepted'")->execute([$uid, $fid]);
            } else {
                $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?,?,'pending')
                               ON DUPLICATE KEY UPDATE status='pending'")->execute([$uid, $fid]);
            }
            $pdo->commit();
            ok();
            break;
        }

        // Aceptar una solicitud que me mandaron (friendId = quien la mandó).
        case 'accept_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);

            $st = $pdo->prepare("SELECT status FROM friends WHERE user_id=? AND friend_id=?");
            $st->execute([$fid, $uid]);
            if ($st->fetchColumn() !== 'pending') fail('No hay ninguna solicitud pendiente de esa persona.');

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE friends SET status='accepted' WHERE user_id=? AND friend_id=?")->execute([$fid, $uid]);
            $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?,?,'accepted')
                           ON DUPLICATE KEY UPDATE status='accepted'")->execute([$uid, $fid]);
            $pdo->commit();
            ok();
            break;
        }

        // Rechazar una solicitud que me mandaron (friendId = quien la mandó).
        case 'reject_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            $pdo->prepare("DELETE FROM friends WHERE user_id=? AND friend_id=? AND status='pending'")->execute([$fid, $uid]);
            ok();
            break;
        }

        // Cancelar una solicitud que YO mandé y todavía no fue aceptada.
        case 'cancel_friend_request': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            $pdo->prepare("DELETE FROM friends WHERE user_id=? AND friend_id=? AND status='pending'")->execute([$uid, $fid]);
            ok();
            break;
        }

        // Sacar a alguien que ya era amigo (borra la amistad en los dos sentidos).
        case 'remove_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            $pdo->prepare('DELETE FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)')
                ->execute([$uid, $fid, $fid, $uid]);
            ok();
            break;
        }

        default:
            fail('Acción desconocida: ' . $action, 404);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[tablon api] ' . $e->getMessage());
    fail(APP_DEBUG ? $e->getMessage() : 'Uy, algo salió mal de nuestro lado. Probá de nuevo en un rato.', 500);
}
