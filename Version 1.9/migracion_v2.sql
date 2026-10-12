-- =====================================================================
-- Tablón v2 — migración de base de datos
-- Correr UNA sola vez en phpMyAdmin (pestaña SQL) sobre la base
-- de Tablón. No borra datos: solo agrega columnas y tablas.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Fechas reales para los encuentros viejos que tenían la fecha en texto
UPDATE meetups SET event_at='2026-08-28 21:00:00' WHERE id=1 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-30 17:00:00' WHERE id=2 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-31 10:00:00' WHERE id=3 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-26 07:30:00' WHERE id=4 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-27 20:00:00' WHERE id=5 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-30 16:00:00' WHERE id=6 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-08-29 21:30:00' WHERE id=7 AND event_at IS NULL;
UPDATE meetups SET event_at='2026-09-11 17:00:00', date_label='Vie 11/09 · 17:00' WHERE id=8 AND event_at IS NULL;

-- 2) Cupo y nivel en los encuentros
ALTER TABLE meetups
  ADD COLUMN IF NOT EXISTS capacity INT NULL DEFAULT NULL AFTER place,
  ADD COLUMN IF NOT EXISTS level VARCHAR(20) NOT NULL DEFAULT 'todos' AFTER capacity;

-- 3) Lista de espera: cada anotado puede estar "going" o "waitlist"
ALTER TABLE meetup_going
  ADD COLUMN IF NOT EXISTS status ENUM('going','waitlist') NOT NULL DEFAULT 'going',
  ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 4) Usuarios: moderadores del sitio y aceptación de normas
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_moderator TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS rules_accepted_at DATETIME NULL DEFAULT NULL;
-- Thiago queda como moderador general del sitio
UPDATE users SET is_moderator=1 WHERE handle='@thiagodeto89';

-- 5) Chat de cada grupo
CREATE TABLE IF NOT EXISTS group_messages (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  author_id INT NULL,
  body VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY group_msg (group_id, id),
  CONSTRAINT gm_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
  CONSTRAINT gm_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6) Reseñas / "¿cómo estuvo?" después de un encuentro
CREATE TABLE IF NOT EXISTS meetup_recaps (
  id INT NOT NULL AUTO_INCREMENT,
  meetup_id INT NOT NULL,
  author_id INT NOT NULL,
  body VARCHAR(400) NOT NULL DEFAULT '',
  photo_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY one_per_person (meetup_id, author_id),
  CONSTRAINT mr_meetup FOREIGN KEY (meetup_id) REFERENCES meetups(id) ON DELETE CASCADE,
  CONSTRAINT mr_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7) Bloqueos entre usuarios
CREATE TABLE IF NOT EXISTS user_blocks (
  blocker_id INT NOT NULL,
  blocked_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (blocker_id, blocked_id),
  KEY blocked (blocked_id),
  CONSTRAINT ub_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT ub_blocked FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8) Denuncias
CREATE TABLE IF NOT EXISTS reports (
  id INT NOT NULL AUTO_INCREMENT,
  reporter_id INT NULL,
  target_type VARCHAR(20) NOT NULL,
  target_id INT NOT NULL,
  group_id INT NULL,
  reason VARCHAR(40) NOT NULL,
  details VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  resolved_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY status_group (status, group_id),
  CONSTRAINT rp_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9) Intentos de login (para frenar a quien prueba contraseñas)
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  identifier VARCHAR(190) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ip_time (ip, attempted_at),
  KEY ident_time (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
