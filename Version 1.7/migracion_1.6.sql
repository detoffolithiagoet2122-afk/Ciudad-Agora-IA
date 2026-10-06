-- ============================================================
-- Tablón — cambios en la base de datos para la Versión 1.6
-- ============================================================
-- Importá este archivo UNA sola vez en phpMyAdmin (pestaña "SQL" o
-- "Importar") sobre la base que ya tenés andando (if0_42945901_tablon).
-- No borra nada: solo agrega columnas y una tabla nueva.
--
-- Hacelo ANTES de subir los archivos nuevos de la 1.6 al hosting: la API
-- nueva ya lee estas columnas y, si no existen, la página no carga.
-- ============================================================

-- Perfil: una bio corta y el barrio de cada persona.
ALTER TABLE `users`
  ADD COLUMN `bio` varchar(200) DEFAULT NULL AFTER `avatar_path`,
  ADD COLUMN `zone` varchar(120) DEFAULT NULL AFTER `bio`;

-- Reacciones del muro: una por persona por post (like, love, haha, wow, fuego).
CREATE TABLE IF NOT EXISTS `post_reactions` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `post_reactions_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
