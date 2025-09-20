-- =====================================================
-- AGGIORNAMENTO SISTEMA VOTI - UN VOTO AL GIORNO
-- Vote System Update - One Vote Per Day
-- =====================================================

-- Questo file ottimizza il database per il nuovo sistema di voto
-- This file optimizes the database for the new voting system

-- =====================================================
-- 1. AGGIUNTA INDICI PER PERFORMANCE
-- =====================================================

-- Indice per query sui voti giornalieri per utente
ALTER TABLE `sl_votes` 
ADD INDEX `idx_user_daily_votes` (`user_id`, `data_voto`);

-- Indice per query sui voti per data
ALTER TABLE `sl_votes` 
ADD INDEX `idx_vote_date` (`data_voto`);

-- Indice composto per query ottimizzate
ALTER TABLE `sl_votes` 
ADD INDEX `idx_user_date_server` (`user_id`, `data_voto`, `server_id`);

-- =====================================================
-- 2. STORED PROCEDURE PER CONTROLLO VOTO GIORNALIERO
-- =====================================================

DELIMITER //

-- Procedura per controllare se un utente può votare oggi
DROP PROCEDURE IF EXISTS CanUserVoteToday//

CREATE PROCEDURE CanUserVoteToday(IN user_id INT)
BEGIN
    SELECT 
        CASE 
            WHEN COUNT(*) > 0 THEN 0 
            ELSE 1 
        END as can_vote,
        MAX(v.data_voto) as last_vote_time,
        s.nome as voted_server_name
    FROM sl_votes v
    LEFT JOIN sl_servers s ON v.server_id = s.id
    WHERE v.user_id = user_id 
    AND DATE(v.data_voto) = CURDATE()
    GROUP BY s.nome;
END//

-- Procedura per ottenere statistiche voti giornalieri
DROP PROCEDURE IF EXISTS GetDailyVoteStats//

CREATE PROCEDURE GetDailyVoteStats()
BEGIN
    SELECT 
        DATE(data_voto) as vote_date,
        COUNT(*) as total_votes,
        COUNT(DISTINCT user_id) as unique_voters,
        COUNT(DISTINCT server_id) as servers_voted
    FROM sl_votes 
    WHERE data_voto >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(data_voto)
    ORDER BY vote_date DESC;
END//

DELIMITER ;

-- =====================================================
-- 3. VISTA PER STATISTICHE VOTI GIORNALIERI
-- =====================================================

-- Vista per voti di oggi
CREATE OR REPLACE VIEW `daily_votes_today` AS
SELECT 
    u.id as user_id,
    u.minecraft_nick,
    s.id as server_id,
    s.nome as server_name,
    v.data_voto
FROM sl_votes v
JOIN sl_users u ON v.user_id = u.id
JOIN sl_servers s ON v.server_id = s.id
WHERE DATE(v.data_voto) = CURDATE()
ORDER BY v.data_voto DESC;

-- Vista per classifica server con voti di oggi
CREATE OR REPLACE VIEW `server_ranking_today` AS
SELECT 
    s.id,
    s.nome,
    s.ip,
    s.versione,
    s.logo_url,
    COUNT(v.id) as votes_today,
    (SELECT COUNT(*) FROM sl_votes WHERE server_id = s.id) as total_votes
FROM sl_servers s
LEFT JOIN sl_votes v ON s.id = v.server_id AND DATE(v.data_voto) = CURDATE()
WHERE s.is_active = 1
GROUP BY s.id, s.nome, s.ip, s.versione, s.logo_url
ORDER BY votes_today DESC, total_votes DESC;

-- =====================================================
-- 4. TRIGGER PER LOG AUTOMATICO
-- =====================================================

DELIMITER //

-- Trigger per loggare i voti (opzionale)
DROP TRIGGER IF EXISTS log_vote_activity//

CREATE TRIGGER log_vote_activity
    AFTER INSERT ON sl_votes
    FOR EACH ROW
BEGIN
    -- Inserisci un log dell'attività (se hai una tabella di log)
    -- INSERT INTO activity_log (user_id, action, details, created_at)
    -- VALUES (NEW.user_id, 'VOTE', CONCAT('Voted for server ID: ', NEW.server_id), NOW());
    
    -- Per ora, non facciamo nulla, ma il trigger è pronto per future implementazioni
    SET @dummy = 0;
END//

DELIMITER ;

-- =====================================================
-- 5. FUNZIONI UTILITY
-- =====================================================

DELIMITER //

-- Funzione per calcolare il tempo fino a mezzanotte
DROP FUNCTION IF EXISTS TimeUntilMidnight//

CREATE FUNCTION TimeUntilMidnight() 
RETURNS TIME
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE current_time TIME;
    DECLARE seconds_until_midnight INT;
    
    SET current_time = CURTIME();
    SET seconds_until_midnight = TIME_TO_SEC('24:00:00') - TIME_TO_SEC(current_time);
    
    RETURN SEC_TO_TIME(seconds_until_midnight);
END//

DELIMITER ;

-- =====================================================
-- 6. PULIZIA DATI VECCHI (OPZIONALE)
-- =====================================================

-- Procedura per pulire voti molto vecchi (oltre 1 anno)
DELIMITER //

DROP PROCEDURE IF EXISTS CleanOldVotes//

CREATE PROCEDURE CleanOldVotes()
BEGIN
    -- Elimina voti più vecchi di 1 anno (opzionale)
    -- DELETE FROM sl_votes WHERE data_voto < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
    
    -- Per ora non eliminiamo nulla, ma la procedura è pronta
    SELECT 'Pulizia voti non eseguita - procedura pronta per uso futuro' as message;
END//

DELIMITER ;

-- =====================================================
-- 7. VERIFICA SISTEMA
-- =====================================================

-- Query di test per verificare il nuovo sistema
SELECT 'VERIFICA NUOVO SISTEMA VOTI' as info;

-- Mostra voti di oggi
SELECT 
    'Voti di oggi' as tipo,
    COUNT(*) as totale,
    COUNT(DISTINCT user_id) as utenti_unici,
    COUNT(DISTINCT server_id) as server_votati
FROM sl_votes 
WHERE DATE(data_voto) = CURDATE();

-- Mostra utenti che hanno già votato oggi
SELECT 
    'Utenti che hanno votato oggi' as info,
    u.minecraft_nick,
    s.nome as server_votato,
    TIME(v.data_voto) as ora_voto
FROM sl_votes v
JOIN sl_users u ON v.user_id = u.id
JOIN sl_servers s ON v.server_id = s.id
WHERE DATE(v.data_voto) = CURDATE()
ORDER BY v.data_voto DESC;

-- =====================================================
-- COMPLETATO!
-- =====================================================

SELECT 'Sistema voti aggiornato con successo!' as status,
       'Un voto al giorno per utente - Reset a mezzanotte' as description,
       NOW() as updated_at;