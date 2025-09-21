-- =============================================
-- CLEANUP TABELLE OBSOLETE - BLOCKSY AUTOMATICO
-- =============================================
-- Questo script rimuove le tabelle e colonne non più utilizzate
-- dal momento che il sistema ora è completamente automatico e
-- utilizza solo la licenza server per l'identificazione

-- ATTENZIONE: Esegui questo script solo se sei sicuro che il
-- sistema automatico funzioni correttamente!

-- =============================================
-- 1. RIMUOVI TABELLA VOTE_CODES (se esiste)
-- =============================================
DROP TABLE IF EXISTS `sl_vote_codes`;

-- =============================================
-- 2. RIMUOVI VISTA vote_codes (se esiste)
-- =============================================
DROP VIEW IF EXISTS `vote_codes`;

-- =============================================
-- 3. RIMUOVI TABELLA DEI CODICI SCADUTI (se esiste)
-- =============================================
DROP TABLE IF EXISTS `sl_expired_codes`;

-- =============================================
-- 4. RIMUOVI COLONNE OBSOLETE DA sl_reward_logs
-- =============================================
-- Rimuovi colonne relative ai vecchi codici se esistono
ALTER TABLE `sl_reward_logs` 
DROP COLUMN IF EXISTS `vote_code_id`,
DROP COLUMN IF EXISTS `code_used`;

-- =============================================
-- 5. RIMUOVI TABELLA sl_pending_votes (se esiste)
-- =============================================
DROP TABLE IF EXISTS `sl_pending_votes`;

-- =============================================
-- 6. RIMUOVI INDICI RELATIVI AI CODICI
-- =============================================
ALTER TABLE `sl_servers` 
DROP INDEX IF EXISTS `idx_vote_codes`;

-- =============================================
-- 7. PULIZIA FINALE - Rimuovi eventuali tabelle temporanee
-- =============================================
DROP TABLE IF EXISTS `sl_temp_codes`;
DROP TABLE IF EXISTS `sl_backup_codes`;

-- =============================================
-- 8. AGGIORNA STRUTTURA REWARD_LOGS
-- =============================================
-- Assicurati che la tabella reward_logs abbia solo i campi necessari
-- per il sistema automatico basato su licenza

-- Rimuovi eventuali colonne obsolete
ALTER TABLE `sl_reward_logs` 
DROP COLUMN IF EXISTS `manual_claim`,
DROP COLUMN IF EXISTS `claim_ip`;

-- =============================================
-- MESSAGGIO DI CONFERMA
-- =============================================
SELECT '✅ Pulizia tabelle obsolete completata!' AS messaggio,
       'Il sistema Blocksy ora è completamente automatico!' AS nota,
       'Le ricompense vengono distribuite senza codici manuali.' AS info;

-- =============================================
-- STATO FINALE DELLE TABELLE
-- =============================================
SHOW TABLES LIKE 'sl_%';