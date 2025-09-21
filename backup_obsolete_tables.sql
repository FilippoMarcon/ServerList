-- =============================================
-- BACKUP TABELLE OBSOLETE PRIMA DELLA RIMOZIONE
-- =============================================
-- Esegui questo script PRIMA di cleanup_obsolete_tables.sql
-- per creare un backup delle tabelle che verranno rimosse

-- =============================================
-- 1. BACKUP TABELLA sl_vote_codes
-- =============================================
CREATE TABLE IF NOT EXISTS `sl_vote_codes_backup` AS 
SELECT * FROM `sl_vote_codes` WHERE 1=0; -- Solo struttura

-- Copia i dati se esistono
INSERT INTO `sl_vote_codes_backup` 
SELECT * FROM `sl_vote_codes` 
WHERE EXISTS (SELECT 1 FROM `sl_vote_codes` LIMIT 1);

-- =============================================
-- 2. BACKUP VISTA vote_codes (come tabella)
-- =============================================
CREATE TABLE IF NOT EXISTS `vote_codes_backup` AS 
SELECT * FROM `vote_codes` WHERE 1=0; -- Solo struttura

-- Copia i dati se la vista esiste
INSERT INTO `vote_codes_backup` 
SELECT * FROM `vote_codes` 
WHERE EXISTS (SELECT 1 FROM `vote_codes` LIMIT 1);

-- =============================================
-- 3. BACKUP TABELLA sl_pending_votes (se esiste)
-- =============================================
CREATE TABLE IF NOT EXISTS `sl_pending_votes_backup` AS 
SELECT * FROM `sl_pending_votes` WHERE 1=0; -- Solo struttura

-- Copia i dati se esistono
INSERT INTO `sl_pending_votes_backup` 
SELECT * FROM `sl_pending_votes` 
WHERE EXISTS (SELECT 1 FROM `sl_pending_votes` LIMIT 1);

-- =============================================
-- MESSAGGIO DI CONFERMA
-- =============================================
SELECT '✅ Backup tabelle obsolete completato!' AS messaggio,
       'È ora sicuro eseguire cleanup_obsolete_tables.sql' AS nota,
       'I backup sono nelle tabelle: sl_vote_codes_backup, vote_codes_backup, sl_pending_votes_backup' AS info;