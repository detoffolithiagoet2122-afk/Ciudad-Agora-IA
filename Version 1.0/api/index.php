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
            $friendRows = $pdo->query('SELECT user_id, friend_id FROM friends')->fetchAll();

            $catsByUser = [];
            foreach ($userCats as $r) $catsByUser[$r['user_id']][] = $r['category_id'];
            $friendsByUser = [];
            foreach ($friendRows as $r) $friendsByUser[$r['user_id']][] = (string)$r['friend_id'];

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
            $members = $pdo->query('SELECT group_id, user_id FROM group_members')->fetchAll();
            $meetups = $pdo->query('SELECT id, group_id, title, date_label, place FROM meetups ORDER BY id DESC')->fetchAll();
            $going = $pdo->query('SELECT meetup_id, user_id FROM meetup_going')->fetchAll();
            $posts = $pdo->query('SELECT id, group_id, author_id, body, created_at FROM posts ORDER BY id DESC')->fetchAll();

            $membersByGroup = [];
            foreach ($members as $r) $membersByGroup[$r['group_id']][] = (string)$r['user_id'];
            $goingByMeetup = [];
            foreach ($going as $r) $goingByMeetup[$r['meetup_id']][] = (string)$r['user_id'];
            $meetupsByGroup = [];
            foreach ($meetups as $m) {
                $meetupsByGroup[$m['group_id']][] = [
                    'id' => (string)$m['id'],
                    'title' => $m['title'],
                    'date' => $m['date_label'],
                    'place' => $m['place'],
                    'going' => $goingByMeetup[$m['id']] ?? [],
                ];
            }
            $postsByGroup = [];
            foreach ($posts as $p) {
                $postsByGroup[$p['group_id']][] = [
                    'id' => (string)$p['id'],
                    'authorId' => $p['author_id'] !== null ? (string)$p['author_id'] : null,
                    'text' => $p['body'],
                    'time' => timeAgo($p['created_at']),
                ];
            }

            $groupsOut = array_map(function ($g) use ($membersByGroup, $meetupsByGroup, $postsByGroup) {
                return [
                    'id' => (string)$g['id'],
                    'name' => $g['name'],
                    'categoryId' => $g['category_id'],
                    'zone' => $g['zone'],
                    'description' => $g['description'],
                    'members' => $membersByGroup[$g['id']] ?? [],
                    'meetups' => $meetupsByGroup[$g['id']] ?? [],
                    'posts' => $postsByGroup[$g['id']] ?? [],
                ];
            }, $groups);

            $cuid = currentUserId();
            ok([
                'categories' => $categories,
                'users' => $usersOut,
                'groups' => $groupsOut,
                'currentUserId' => $cuid !== null ? (string)$cuid : null,
            ]);
            break;
        }

        // ---------- autenticación ----------
        case 'register': {
            $name = trim((string)($input['name'] ?? ''));
            $handleRaw = strtolower(trim(str_replace('@', '', (string)($input['handle'] ?? ''))));
            $password = (string)($input['password'] ?? '');
            $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];

            if ($name === '' || $handleRaw === '') fail('Falta el nombre o el usuario.');
            if (strlen($password) < 4) fail('La contraseña tiene que tener al menos 4 caracteres.');

            $handle = '@' . $handleRaw;
            $st = $pdo->prepare('SELECT id FROM users WHERE LOWER(handle)=LOWER(?)');
            $st->execute([$handle]);
            if ($st->fetch()) fail('Ese usuario ya existe, probá con otro.');

            $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO users (name, handle, password_hash, color_idx) VALUES (?,?,?,?)');
            $ins->execute([$name, $handle, $hash, $count % 5]);
            $uid = (int)$pdo->lastInsertId();

            if (empty($categories)) {
                $first = $pdo->query('SELECT id FROM categories ORDER BY label LIMIT 1')->fetchColumn();
                if ($first) $categories = [$first];
            }
            $insCat = $pdo->prepare('INSERT IGNORE INTO user_categories (user_id, category_id) VALUES (?,?)');
            foreach ($categories as $c) $insCat->execute([$uid, (string)$c]);
            $pdo->commit();

            $_SESSION['user_id'] = $uid;
            ok(['userId' => (string)$uid, 'name' => $name]);
            break;
        }

        case 'login': {
            $handleRaw = strtolower(trim(str_replace('@', '', (string)($input['handle'] ?? ''))));
            $password = (string)($input['password'] ?? '');
            $st = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE LOWER(REPLACE(handle,'@','')) = ?");
            $st->execute([$handleRaw]);
            $u = $st->fetch();
            if (!$u) fail('No encontramos esa cuenta. Probá el acceso de invitade.');
            if (!password_verify($password, $u['password_hash'])) fail('Contraseña incorrecta.');
            $_SESSION['user_id'] = (int)$u['id'];
            ok(['userId' => (string)$u['id'], 'name' => $u['name']]);
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

        // ---------- grupos ----------
        case 'create_group': {
            $uid = requireLogin();
            $name = trim((string)($input['name'] ?? ''));
            $zone = trim((string)($input['zone'] ?? ''));
            $desc = trim((string)($input['description'] ?? ''));
            $categoryId = (string)($input['categoryId'] ?? '');
            $customLabel = trim((string)($input['customCategory'] ?? ''));

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
            $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?,?)')->execute([$gid, $uid]);
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
            $place = trim((string)($input['place'] ?? ''));
            if ($title === '' || $date === '' || $place === '') fail('Completá los tres campos del encuentro.');

            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO meetups (group_id, title, date_label, place, created_by) VALUES (?,?,?,?,?)');
            $ins->execute([$gid, $title, $date, $place, $uid]);
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

        // ---------- muro ----------
        case 'create_post': {
            $uid = requireLogin();
            $gid = (int)($input['groupId'] ?? 0);
            if (!isMember($pdo, $gid, $uid)) fail('Tenés que ser miembro del grupo.', 403);
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '') fail('Escribí algo para publicar.');
            $pdo->prepare('INSERT INTO posts (group_id, author_id, body) VALUES (?,?,?)')->execute([$gid, $uid, $text]);
            ok();
            break;
        }

        // ---------- amigos ----------
        case 'add_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            if ($fid && $fid !== $uid) {
                $pdo->prepare('INSERT IGNORE INTO friends (user_id, friend_id) VALUES (?,?)')->execute([$uid, $fid]);
            }
            ok();
            break;
        }

        case 'remove_friend': {
            $uid = requireLogin();
            $fid = (int)($input['friendId'] ?? 0);
            $pdo->prepare('DELETE FROM friends WHERE user_id=? AND friend_id=?')->execute([$uid, $fid]);
            ok();
            break;
        }

        default:
            fail('Acción desconocida: ' . $action, 404);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[tablon api] ' . $e->getMessage());
    fail(APP_DEBUG ? $e->getMessage() : 'Error interno del servidor. Revisá api/config.php y que la base de datos exista.', 500);
}
