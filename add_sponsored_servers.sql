-- =============================================
-- AGGIUNTA SERVER SPONSORIZZATI
-- =============================================
-- Questo script aggiunge i campi necessari per gestire i server sponsorizzati

-- =============================================
-- 1. AGGIUNGI CAMPI SPONSORIZZAZIONE A sl_servers
-- =============================================
ALTER TABLE `sl_servers` 
ADD COLUMN `is_sponsored` tinyint(1) DEFAULT 0 AFTER `is_active`,
ADD COLUMN `sponsor_priority` int(11) DEFAULT 0 AFTER `is_sponsored`,
ADD COLUMN `sponsor_expires_at` datetime DEFAULT NULL AFTER `sponsor_priority`,
ADD KEY `idx_sponsored` (`is_sponsored`),
ADD KEY `idx_sponsor_priority` (`sponsor_priority`),
ADD KEY `idx_sponsor_expires` (`sponsor_expires_at`);

-- =============================================
-- 2. CREA TABELLA PER LOG SPONSORIZZAZIONI
-- =============================================
CREATE TABLE IF NOT EXISTS `sl_sponsor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` enum('activated', 'deactivated', 'renewed', 'priority_changed') NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_server_id` (`server_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`server_id`) REFERENCES `sl_servers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `sl_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. CREA VISTA PER SERVER SPONSORIZZATI ATTIVI
-- =============================================
CREATE OR REPLACE VIEW `active_sponsored_servers` AS
SELECT 
    s.id,
    s.nome,
    s.descrizione,
    s.ip,
    s.versione,
    s.logo_url,
    s.banner_url,
    s.tipo_server,
    s.sponsor_priority,
    s.sponsor_expires_at,
    s.owner_id,
    s.data_inserimento,
    COUNT(v.id) as total_votes,
    CASE 
        WHEN s.sponsor_expires_at > NOW() THEN 1 
        ELSE 0 
    END as sponsor_active
FROM sl_servers s
LEFT JOIN sl_votes v ON s.id = v.server_id
WHERE s.is_sponsored = 1 
    AND (s.sponsor_expires_at IS NULL OR s.sponsor_expires_at > NOW())
    AND s.is_active = 1
GROUP BY s.id
ORDER BY s.sponsor_priority DESC, s.data_aggiornamento DESC;

-- =============================================
-- 4. INSERISCI SERVER SPONSORIZZATI DI ESEMPIO
-- =============================================
-- Questi sono esempi - da modificare secondo necessità
UPDATE `sl_servers` SET 
    `is_sponsored` = 1, 
    `sponsor_priority` = 10,
    `sponsor_expires_at` = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE `id` = 1;

UPDATE `sl_servers` SET 
    `is_sponsored` = 1, 
    `sponsor_priority` = 5,
    `sponsor_expires_at` = DATE_ADD(NOW(), INTERVAL 15 DAY)
WHERE `id` = 2;

-- =============================================
-- MESSAGGIO DI CONFERMA
-- =============================================
SELECT '✅ Sistema sponsorizzazioni installato!' AS messaggio,
       'Campi aggiunti: is_sponsored, sponsor_priority, sponsor_expires_at' AS info,
       'Tabella log creata: sl_sponsor_logs' AS dettagli;