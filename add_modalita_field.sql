-- Aggiunge il campo modalità alla tabella sl_servers
ALTER TABLE `sl_servers` ADD COLUMN `modalita` TEXT DEFAULT NULL COMMENT 'Modalità di gioco del server (JSON array)';

-- Aggiorna i server esistenti con modalità di default
UPDATE `sl_servers` SET `modalita` = '["Survival", "Adventure"]' WHERE `modalita` IS NULL;