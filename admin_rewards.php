<?php
/**
 * Admin Rewards Management
 * Gestione ricompense server per amministratori
 */

require_once 'config.php';

// Verifica che l'utente sia admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Gestione azioni
$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';
$message = '';
$message_type = 'info';

try {
    switch ($action) {
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Salva le modifiche
                $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 0;
                $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
                $reward_name = isset($_POST['reward_name']) ? sanitize($_POST['reward_name']) : '';
                $commands = isset($_POST['commands']) ? $_POST['commands'] : '';
                $enabled = isset($_POST['enabled']) ? 1 : 0;
                $cooldown_hours = isset($_POST['cooldown_hours']) ? intval($_POST['cooldown_hours']) : 24;
                
                if ($reward_id > 0) {
                    // Aggiorna ricompensa esistente
                    $stmt = $pdo->prepare("
                        UPDATE sl_server_rewards 
                        SET reward_name = ?, commands = ?, enabled = ?, cooldown_hours = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$reward_name, $commands, $enabled, $cooldown_hours, $reward_id]);
                    $message = "Ricompensa aggiornata con successo!";
                } else {
                    // Crea nuova ricompensa
                    $stmt = $pdo->prepare("
                        INSERT INTO sl_server_rewards (server_id, reward_name, commands, enabled, cooldown_hours) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$server_id, $reward_name, $commands, $enabled, $cooldown_hours]);
                    $message = "Ricompensa creata con successo!";
                }
                $message_type = 'success';
                $action = 'list';
            } else {
                // Mostra form di modifica
                $reward_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $reward = null;
                
                if ($reward_id > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM sl_server_rewards WHERE id = ?");
                    $stmt->execute([$reward_id]);
                    $reward = $stmt->fetch();
                }
                
                // Ottieni lista server
                $servers_stmt = $pdo->query("SELECT id, nome FROM sl_servers ORDER BY nome");
                $servers = $servers_stmt->fetchAll();
                
                include 'admin_rewards_form.php';
                exit();
            }
            break;
            
        case 'delete':
            $reward_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($reward_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM sl_server_rewards WHERE id = ?");
                $stmt->execute([$reward_id]);
                $message = "Ricompensa eliminata con successo!";
                $message_type = 'success';
            }
            $action = 'list';
            break;
            
        case 'toggle':
            $reward_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($reward_id > 0) {
                $stmt = $pdo->prepare("UPDATE sl_server_rewards SET enabled = NOT enabled WHERE id = ?");
                $stmt->execute([$reward_id]);
                $message = "Stato ricompensa modificato con successo!";
                $message_type = 'success';
            }
            $action = 'list';
            break;
            
        case 'stats':
            // Mostra statistiche dettagliate
            $server_id = isset($_GET['server_id']) ? intval($_GET['server_id']) : 0;
            
            $stats_query = "
                SELECT 
                    s.nome as server_name,
                    COUNT(DISTINCT vc.id) as total_codes,
                    COUNT(DISTINCT CASE WHEN vc.status = 'used' THEN vc.id END) as used_codes,
                    COUNT(DISTINCT CASE WHEN vc.status = 'pending' THEN vc.id END) as pending_codes,
                    COUNT(DISTINCT rl.id) as total_rewards,
                    COUNT(DISTINCT CASE WHEN rl.reward_status = 'success' THEN rl.id END) as successful_rewards
                FROM sl_servers s
                LEFT JOIN sl_vote_codes vc ON s.id = vc.server_id
                LEFT JOIN sl_reward_logs rl ON vc.id = rl.vote_code_id
            ";
            
            if ($server_id > 0) {
                $stats_query .= " WHERE s.id = ? ";
                $stats_stmt = $pdo->prepare($stats_query . " GROUP BY s.id, s.nome ORDER BY s.nome");
                $stats_stmt->execute([$server_id]);
            } else {
                $stats_stmt = $pdo->query($stats_query . " GROUP BY s.id, s.nome ORDER BY s.nome");
            }
            
            $stats = $stats_stmt->fetchAll();
            
            include 'admin_rewards_stats.php';
            exit();
            break;
    }
    
    // Lista ricompense (azione di default)
    $rewards_stmt = $pdo->query("
        SELECT 
            sr.*,
            s.nome as server_name,
            COUNT(DISTINCT vc.id) as codes_generated,
            COUNT(DISTINCT CASE WHEN vc.status = 'used' THEN vc.id END) as codes_used
        FROM sl_server_rewards sr
        JOIN sl_servers s ON sr.server_id = s.id
        LEFT JOIN sl_vote_codes vc ON sr.server_id = vc.server_id
        GROUP BY sr.id, s.nome
        ORDER BY s.nome, sr.reward_name
    ");
    $rewards = $rewards_stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = "Errore database: " . $e->getMessage();
    $message_type = 'error';
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-gift"></i> Gestione Ricompense Server</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista Ricompense</h5>
                    <div>
                        <a href="?action=stats" class="btn btn-info btn-sm">
                            <i class="fas fa-chart-bar"></i> Statistiche
                        </a>
                        <a href="?action=edit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nuova Ricompensa
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Server</th>
                                    <th>Nome Ricompensa</th>
                                    <th>Stato</th>
                                    <th>Cooldown</th>
                                    <th>Codes Generati</th>
                                    <th>Codes Usati</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rewards as $reward): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reward['server_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reward['reward_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $reward['enabled'] ? 'success' : 'danger'; ?>">
                                                <?php echo $reward['enabled'] ? 'Attivo' : 'Disabilitato'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $reward['cooldown_hours']; ?> ore</td>
                                        <td><?php echo $reward['codes_generated'] ?: 0; ?></td>
                                        <td><?php echo $reward['codes_used'] ?: 0; ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=toggle&id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-<?php echo $reward['enabled'] ? 'danger' : 'success'; ?>" 
                                               onclick="return confirm('<?php echo $reward['enabled'] ? 'Disabilitare' : 'Abilitare'; ?> questa ricompensa?')">
                                                <i class="fas fa-<?php echo $reward['enabled'] ? 'times' : 'check'; ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Eliminare definitivamente questa ricompensa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>