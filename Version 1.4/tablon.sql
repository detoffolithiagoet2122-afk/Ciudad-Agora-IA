-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql301.infinityfree.com
-- Tiempo de generación: 25-09-2026 a las 18:00:56
-- Versión del servidor: 11.4.13-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_42945901_tablon`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` varchar(64) NOT NULL,
  `label` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `label`) VALUES
('ajedrez', 'Ajedrez'),
('baile', 'Baile y danza'),
('ceramica', 'Cerámica'),
('ciclismo', 'Ciclismo urbano'),
('cine', 'Cine under'),
('cocina', 'Cocina y repostería'),
('debate', 'Debate y actualidad'),
('escritura', 'Escritura creativa'),
('foto', 'Fotografía analógica'),
('gaming', 'Videojuegos'),
('huerta', 'Huerta urbana'),
('idiomas', 'Intercambio de idiomas'),
('lectura', 'Club de lectura'),
('musica', 'Bandas y música en vivo'),
('rocket-league', 'Rocket League'),
('rol', 'Rol y TTRPG'),
('running', 'Running'),
('senderismo', 'Trekking y montañismo'),
('skate', 'Skate'),
('tecnologia', 'Tecnología y programación'),
('voluntariado', 'Voluntariado y causas sociales');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `friends`
--

CREATE TABLE `friends` (
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'accepted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `friends`
--

INSERT INTO `friends` (`user_id`, `friend_id`, `status`) VALUES
(1, 4, 'accepted'),
(1, 7, 'accepted'),
(2, 7, 'accepted'),
(3, 6, 'accepted'),
(4, 1, 'accepted'),
(6, 3, 'accepted'),
(7, 1, 'accepted'),
(7, 2, 'accepted'),
(14, 16, 'accepted'),
(16, 14, 'accepted');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `category_id` varchar(64) NOT NULL,
  `zone` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `groups`
--

INSERT INTO `groups` (`id`, `name`, `category_id`, `zone`, `description`, `photo_path`, `created_by`, `created_at`) VALUES
(1, 'Rodada Nocturna', 'ciclismo', 'Palermo', 'Salimos a rodar de noche por la ciudad, ritmo tranqui. Se suma cualquiera con bici y luces.', NULL, 2, '2026-08-26 21:23:38'),
(2, 'Mesa Salvaje', 'rol', 'Almagro', 'Campaña homebrew los sábados. Ambiente relajado, sin power-gaming.', NULL, 3, '2026-08-26 21:23:38'),
(3, 'Klub 35mm', 'foto', 'San Telmo', 'Revelado casero, salidas fotográficas y canje de rollos vencidos.', NULL, 1, '2026-08-26 21:23:38'),
(4, 'Runners del Bajo', 'running', 'Puerto Madero', '5 a 8km suaves y después café. No hace falta ser rápido.', NULL, 2, '2026-08-26 21:23:38'),
(5, 'Ensayo Abierto', 'musica', 'Villa Crespo', 'Jams sin banda fija. Traé tu instrumento o tus ganas de cantar.', NULL, 4, '2026-08-26 21:23:38'),
(6, 'Torre de Peones', 'ajedrez', 'Plaza Almagro', 'Tableros callejeros los domingos a la tarde, todos los niveles.', NULL, 3, '2026-08-26 21:23:38'),
(7, 'Rampa Libre', 'skate', 'Parque Sarmiento', 'Sesión libre en la rampa. Se enseña a quien recién arranca.', NULL, 4, '2026-08-26 21:23:38'),
(8, 'Huerta Comunitaria Norte', 'huerta', 'Belgrano', 'Cuidamos una huerta de barrio los fines de semana. Siempre falta una mano.', NULL, 5, '2026-08-26 21:23:38'),
(9, 'Cine Under Constitución', 'cine', 'Constitución', 'Función y debate de cine independiente argentino, una vez por mes.', NULL, 1, '2026-08-26 21:23:38'),
(10, 'La liga de cohetes', 'rocket-league', 'Parque Rivadavia, Caballito', 'Grupo para poder jugar Rocket  League, ya sea en forma presencial o de forma virtual', 'uploads/groups/3f74ea82e4f7db8f5a21aa97.png', 14, '2026-09-25 14:21:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `group_members`
--

CREATE TABLE `group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `group_members`
--

INSERT INTO `group_members` (`group_id`, `user_id`, `is_admin`, `joined_at`) VALUES
(1, 1, 0, '2026-08-26 21:23:38'),
(1, 2, 1, '2026-08-26 21:23:38'),
(1, 7, 0, '2026-08-26 21:23:38'),
(1, 14, 0, '2026-09-23 06:13:11'),
(2, 3, 1, '2026-08-26 21:23:38'),
(2, 6, 0, '2026-08-26 21:23:38'),
(3, 1, 1, '2026-08-26 21:23:38'),
(3, 7, 0, '2026-08-26 21:23:38'),
(4, 2, 1, '2026-08-26 21:23:38'),
(4, 7, 0, '2026-08-26 21:23:38'),
(5, 4, 1, '2026-08-26 21:23:38'),
(5, 6, 0, '2026-08-26 21:23:38'),
(6, 3, 1, '2026-08-26 21:23:38'),
(7, 4, 1, '2026-08-26 21:23:38'),
(8, 5, 1, '2026-08-26 21:23:38'),
(9, 1, 1, '2026-08-26 21:23:38'),
(10, 14, 1, '2026-09-25 14:21:24'),
(10, 16, 1, '2026-09-25 14:26:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meetups`
--

CREATE TABLE `meetups` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `date_label` varchar(120) NOT NULL,
  `event_at` datetime DEFAULT NULL,
  `place` varchar(200) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `edited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `meetups`
--

INSERT INTO `meetups` (`id`, `group_id`, `title`, `date_label`, `event_at`, `place`, `created_by`, `created_at`, `edited_at`) VALUES
(1, 1, 'Rodada de jueves', 'Jue 28/08 · 21:00', NULL, 'Planetario, Palermo', 2, '2026-08-26 21:23:38', NULL),
(2, 2, 'Sesión 14: El pantano', 'Sáb 30/08 · 17:00', NULL, 'Centro Cultural Recoleta, Recoleta', 3, '2026-08-26 21:23:38', NULL),
(3, 3, 'Salida: Feria de San Telmo', 'Dom 31/08 · 10:00', NULL, 'Plaza Dorrego', 1, '2026-08-26 21:23:38', NULL),
(4, 4, 'Trote suave + café', 'Mar 26/08 · 07:30', NULL, 'Puente de la Mujer', 2, '2026-08-26 21:23:38', NULL),
(5, 5, 'Jam de miércoles', 'Mié 27/08 · 20:00', NULL, 'Casa cultural Anselmo', 4, '2026-08-26 21:23:38', NULL),
(6, 7, 'Sesión libre + asado', 'Sáb 30/08 · 16:00', NULL, 'Skatepark Parque Sarmiento', 4, '2026-08-26 21:23:38', NULL),
(7, 9, 'Función + debate', 'Vie 29/08 · 21:30', NULL, 'Centro cultural La Tosquera', 1, '2026-08-26 21:23:38', NULL),
(8, 4, 'Juntada parque saavedra running', 'Viernes 11/09    -    17:00', NULL, 'Parque Saavedra, Saavedra', NULL, '2026-08-26 22:11:21', NULL),
(9, 1, 'Paseo por palermo', 'Jue 10/09 · 21:11', '2026-09-10 21:11:00', 'Parque Centenario, Caballito', 14, '2026-09-23 15:14:32', '2026-09-23 15:14:41'),
(10, 10, 'Torneo Rocket league en linea', 'Sáb 26/09 · 17:30', '2026-09-26 17:30:00', 'La computadora', 14, '2026-09-25 14:30:07', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meetup_going`
--

CREATE TABLE `meetup_going` (
  `meetup_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `meetup_going`
--

INSERT INTO `meetup_going` (`meetup_id`, `user_id`) VALUES
(3, 1),
(7, 1),
(1, 2),
(4, 2),
(2, 3),
(5, 4),
(6, 4),
(5, 6),
(1, 7),
(3, 7),
(9, 14),
(10, 14),
(10, 16);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `type` varchar(30) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `group_id`, `type`, `actor_id`, `message`, `is_read`, `created_at`) VALUES
(1, 16, 10, 'meetup', 14, 'Thiago Federico De Toffoli organizó un encuentro en La liga de cohetes: \"Torneo Rocket league en linea\"', 1, '2026-09-25 14:30:07'),
(2, 16, 10, 'post', 14, 'Thiago Federico De Toffoli publicó algo nuevo en La liga de cohetes', 1, '2026-09-25 14:30:47'),
(3, 14, 10, 'comment', 16, 'Agustin comentó tu publicación', 1, '2026-09-25 14:31:05'),
(4, 14, 10, 'post', 16, 'Agustin publicó algo nuevo en La liga de cohetes', 1, '2026-09-25 14:38:40'),
(5, 14, 10, 'post', 16, 'Agustin publicó algo nuevo en La liga de cohetes', 1, '2026-09-25 14:43:34'),
(6, 14, 10, 'post', 16, 'Agustin publicó algo nuevo en La liga de cohetes', 1, '2026-09-25 14:45:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `edited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id`, `group_id`, `author_id`, `body`, `created_at`, `edited_at`) VALUES
(1, 1, 2, 'Esta semana vamos por la costanera, terreno más plano.', '2026-08-26 20:23:38', NULL),
(2, 2, 3, '¿Alguien tiene una hoja de personaje de más para el sábado?', '2026-08-26 18:23:38', NULL),
(3, 3, 7, 'Traje química de revelado de más si alguien quiere.', '2026-08-26 21:23:38', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `post_comments`
--

CREATE TABLE `post_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `handle` varchar(60) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `email_verify_token` varchar(64) DEFAULT NULL,
  `email_verify_expires` datetime DEFAULT NULL,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `color_idx` int(11) NOT NULL DEFAULT 0,
  `avatar_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `handle`, `email`, `email_verified_at`, `email_verify_token`, `email_verify_expires`, `password_reset_token`, `password_reset_expires`, `password_hash`, `color_idx`, `avatar_path`, `created_at`) VALUES
(1, 'Bianca Ferreyra', '@biaferreyra', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 0, NULL, '2026-08-26 21:23:38'),
(2, 'Tomás Aguirre', '@tomiaguirre', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 1, NULL, '2026-08-26 21:23:38'),
(3, 'Malena Sosa', '@malesosa', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 2, NULL, '2026-08-26 21:23:38'),
(4, 'Franco Ibarra', '@frankoibarra', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 3, NULL, '2026-08-26 21:23:38'),
(5, 'Uma Krause', '@umakrause', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 4, NULL, '2026-08-26 21:23:38'),
(6, 'Nico Paredes', '@nicoparedes', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 0, NULL, '2026-08-26 21:23:38'),
(7, 'Sol Medina', '@solmedina', NULL, '2026-09-19 19:17:29', NULL, NULL, NULL, NULL, '$2y$10$uxRyFDevKFvr05NhW6LcEOARLoLrJJfhCePHZJ3o.QvMwAd/vS3Jm', 1, NULL, '2026-08-26 21:23:38'),
(14, 'Thiago Federico De Toffoli', '@thiagodeto89', 'detoffoli.thiago.et21.22@gmail.com', '2026-09-20 08:23:16', NULL, NULL, NULL, NULL, '$2y$12$SAn1mt.tnPecnGZo/lVhFeIvw0ZNoPqTJ6f8/2FRK48kGrzzyrd0q', 2, 'uploads/avatars/f126a465ddee426373aa7ea7.png', '2026-09-20 08:22:47'),
(15, 'selena veloz', '@sele125', 'selevelozmente@gmail.com', '2026-09-20 08:46:35', NULL, NULL, NULL, NULL, '$2y$10$xyCNqrPLRZTc2O9JnU1sZ.OE5EU9WGqILlswOeRfQV2pl33zk5HdW', 3, NULL, '2026-09-20 08:46:13'),
(16, 'Agustin', '@agusdeto', 'thiagodeto@gmail.com', '2026-09-25 14:25:59', NULL, NULL, NULL, NULL, '$2y$12$ET.wbe52CZdiJ0sc3sty.u3nh5SbB9/bZQ09vxA5LgpxbcgGV.o.q', 4, NULL, '2026-09-25 14:25:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_categories`
--

CREATE TABLE `user_categories` (
  `user_id` int(11) NOT NULL,
  `category_id` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_categories`
--

INSERT INTO `user_categories` (`user_id`, `category_id`) VALUES
(3, 'ajedrez'),
(16, 'ajedrez'),
(15, 'baile'),
(5, 'ceramica'),
(16, 'ceramica'),
(2, 'ciclismo'),
(1, 'cine'),
(16, 'cine'),
(1, 'foto'),
(7, 'foto'),
(14, 'gaming'),
(5, 'huerta'),
(15, 'lectura'),
(4, 'musica'),
(6, 'musica'),
(16, 'rocket-league'),
(3, 'rol'),
(6, 'rol'),
(14, 'rol'),
(2, 'running'),
(7, 'running'),
(14, 'running'),
(4, 'skate'),
(14, 'skate'),
(14, 'voluntariado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`user_id`,`friend_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indices de la tabla `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `meetups`
--
ALTER TABLE `meetups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `meetup_going`
--
ALTER TABLE `meetup_going`
  ADD PRIMARY KEY (`meetup_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indices de la tabla `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `handle` (`handle`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `meetups`
--
ALTER TABLE `meetups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `meetups`
--
ALTER TABLE `meetups`
  ADD CONSTRAINT `meetups_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `meetup_going`
--
ALTER TABLE `meetup_going`
  ADD CONSTRAINT `meetup_going_ibfk_1` FOREIGN KEY (`meetup_id`) REFERENCES `meetups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetup_going_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `user_categories`
--
ALTER TABLE `user_categories`
  ADD CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
