-- SQL Script per creare le tabelle del database Minecraft Server List
-- SQL Script to create Minecraft Server List database tables

-- Tabella utenti / Users table
CREATE TABLE IF NOT EXISTS `sl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `minecraft_nick` varchar(50) DEFAULT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `data_registrazione` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_minecraft_nick` (`minecraft_nick`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella server / Servers table
CREATE TABLE IF NOT EXISTS `sl_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `banner_url` varchar(500) DEFAULT NULL,
  `descrizione` text,
  `ip` varchar(255) NOT NULL,
  `versione` varchar(50) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `data_inserimento` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_aggiornamento` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_ip` (`ip`),
  KEY `idx_active` (`is_active`),
  KEY `idx_owner_id` (`owner_id`),
  FOREIGN KEY (`owner_id`) REFERENCES `sl_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella voti / Votes table
CREATE TABLE IF NOT EXISTS `sl_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `data_voto` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_server_vote` (`server_id`, `user_id`, `data_voto`),
  KEY `idx_data_voto` (`data_voto`),
  KEY `idx_server_id` (`server_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`server_id`) REFERENCES `sl_servers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `sl_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per configurazioni webhook
CREATE TABLE `sl_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `webhook_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `webhook_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `command_template` text COLLATE utf8mb4_unicode_ci DEFAULT 'give {player} minecraft:diamond 1',
  `timeout_seconds` int(11) DEFAULT 30,
  `retry_attempts` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_server_id` (`server_id`),
  KEY `idx_enabled` (`enabled`),
  CONSTRAINT `fk_webhook_server` FOREIGN KEY (`server_id`) REFERENCES `sl_servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per log webhook
CREATE TABLE `sl_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `minecraft_nick` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_status` int(11) DEFAULT NULL,
  `response_data` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_webhook_id` (`webhook_id`),
  KEY `idx_server_id` (`server_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_executed_at` (`executed_at`),
  CONSTRAINT `fk_log_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `sl_webhooks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_server` FOREIGN KEY (`server_id`) REFERENCES `sl_servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `sl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista per contare i voti per server / View to count votes per server
CREATE OR REPLACE VIEW `server_votes_count` AS
SELECT 
    s.id,
    s.nome,
    COUNT(v.id) as total_votes
FROM sl_servers s
LEFT JOIN sl_votes v ON s.id = v.server_id
WHERE s.is_active = 1
GROUP BY s.id, s.nome;

-- Inserimento utente admin di default (password: admin123)
-- Default admin user insertion (password: admin123)
INSERT INTO `sl_users` (`minecraft_nick`, `password_hash`, `is_admin`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Inserimento server di esempio / Sample server insertion
INSERT INTO `sl_servers` (`nome`, `banner_url`, `descrizione`, `ip`, `versione`, `logo_url`) VALUES 
('Server Epico', 'https://via.placeholder.com/468x60.png?text=Server+Epico', 'Un server Minecraft epico con tante modalità di gioco!', 'play.serverepico.it', '1.20.4', 'https://via.placeholder.com/100x100.png?text=Logo'),
('Creative Building', 'https://via.placeholder.com/468x60.png?text=Creative+Building', 'Server creativo per costruttori di ogni livello!', 'creative.building.com', '1.20.1', 'https://via.placeholder.com/100x100.png?text=CB'),
('PVP Arena', 'https://via.placeholder.com/468x60.png?text=PVP+Arena', 'Il miglior server PVP con arene personalizzate!', 'pvp.arena.net', '1.19.4', 'https://via.placeholder.com/100x100.png?text=PVP');

-- Permessi e ottimizzazioni / Permissions and optimizations
-- Assicurati che l'utente MySQL abbia i permessi necessari
-- Make sure the MySQL user has the necessary permissions

-- Per ottimizzare le query, assicurati di avere questi indici
-- To optimize queries, make sure you have these indexes
SHOW INDEX FROM sl_users;
SHOW INDEX FROM sl_servers;
SHOW INDEX FROM sl_votes;