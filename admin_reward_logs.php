<?php
/**
 * Admin Reward Logs Viewer
 * Visualizza il log delle ricompense distribuite
 */

require_once 'config.php';

// Verifica che l'utente sia admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Parametri di filtro
$filters = [
    'server_id' => isset($_GET['server_id']) ? intval($_GET['server_id']) : 0,
    'status' => isset($_GET['status']) ? sanitize($_GET['status']) : '',
    'player_name' => isset($_GET['player_name']) ? sanitize($_GET['player_name']) : '',
    'date_from' => isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '',
    'vote_code' => isset($_GET['vote_code']) ? sanitize($_GET['vote_code']) : ''
];

// Paginazione
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

try {
    // Costruisci la query base
    $query = "
        SELECT 
            rl.*,
            s.nome as server_name,
            u.username,
            vc.vote_code
        FROM sl_reward_logs rl
        JOIN sl_servers s ON rl.server_id = s.id
        JOIN sl_users u ON rl.user_id = u.id
        JOIN sl_vote_codes vc ON rl.vote_code_id = vc.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Applica filtri
    if ($filters['server_id'] > 0) {
        $query .= " AND rl.server_id = ?";
        $params[] = $filters['server_id'];
    }
    
    if ($filters['status']) {
        $query .= " AND rl.reward_status = ?";
        $params[] = $filters['status'];
    }
    
    if ($filters['player_name']) {
        $query .= " AND rl.minecraft_nick LIKE ?";
        $params[] = '%' . $filters['player_name'] . '%';
    }
    
    if ($filters['vote_code']) {
        $query .= " AND vc.vote_code LIKE ?";
        $params[] = '%' . $filters['vote_code'] . '%';
    }
    
    if ($filters['date_from']) {
        $query .= " AND DATE(rl.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if ($filters['date_to']) {
        $query .= " AND DATE(rl.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Conta il totale per la paginazione
    $count_query = str_replace('SELECT rl.*, s.nome as server_name, u.username, vc.vote_code', 'SELECT COUNT(*) as total', $query);
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_rows / $per_page);
    
    // Aggiungi ordinamento e limit
    $query .= " ORDER BY rl.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    // Esegui la query principale
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Ottieni lista server per i filtri
    $servers_stmt = $pdo->query("SELECT id, nome FROM sl_servers ORDER BY nome");
    $servers = $servers_stmt->fetchAll();
    
    // Statistiche rapide
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_logs,
            COUNT(CASE WHEN reward_status = 'success' THEN 1 END) as success_count,
            COUNT(CASE WHEN reward_status = 'failed' THEN 1 END) as failed_count,
            COUNT(CASE WHEN reward_status = 'pending' THEN 1 END) as pending_count
        FROM sl_reward_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Errore database: " . $e->getMessage();
    $logs = [];
    $total_pages = 1;
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-history"></i> Log Ricompense</h2>
            
            <!-- Statistiche rapide -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Totali (30gg)</h5>
                            <h2 class="text-primary"><?php echo $stats['total_logs']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Successi</h5>
                            <h2 class="text-success"><?php echo $stats['success_count']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Falliti</h5>
                            <h2 class="text-danger"><?php echo $stats['failed_count']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">In Attesa</h5>
                            <h2 class="text-warning"><?php echo $stats['pending_count']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtri -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filtri</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="server_id" class="form-label">Server</label>
                            <select class="form-select" id="server_id" name="server_id">
                                <option value="">Tutti</option>
                                <?php foreach ($servers as $server): ?>
                                    <option value="<?php echo $server['id']; ?>" 
                                            <?php echo $filters['server_id'] == $server['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($server['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Stato</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tutti</option>
                                <option value="success" <?php echo $filters['status'] == 'success' ? 'selected' : ''; ?>>Successo</option>
                                <option value="failed" <?php echo $filters['status'] == 'failed' ? 'selected' : ''; ?>>Fallito</option>
                                <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>In Attesa</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="player_name" class="form-label">Giocatore</label>
                            <input type="text" class="form-control" id="player_name" name="player_name" 
                                   value="<?php echo htmlspecialchars($filters['player_name']); ?>" 
                                   placeholder="Nome giocatore">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="vote_code" class="form-label">Codice Voto</label>
                            <input type="text" class="form-control" id="vote_code" name="vote_code" 
                                   value="<?php echo htmlspecialchars($filters['vote_code']); ?>" 
                                   placeholder="Codice voto">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Dal</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Al</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Applica Filtri
                            </button>
                            <a href="admin_reward_logs.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Pulisci
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabella logs -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Risultati (<?php echo $total_rows; ?> totali)</h5>
                    <div>
                        <a href="admin_rewards.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Torna alle Ricompense
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($logs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Data/Ora</th>
                                        <th>Server</th>
                                        <th>Giocatore</th>
                                        <th>Codice Voto</th>
                                        <th>Stato</th>
                                        <th>Dettagli</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($log['server_name']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($log['minecraft_nick']); ?></strong>
                                                <?php if ($log['player_uuid']): ?>
                                                    <br><small class="text-muted">UUID: <?php echo substr($log['player_uuid'], 0, 8); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($log['vote_code']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $log['reward_status'] === 'success' ? 'success' : ($log['reward_status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($log['reward_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['error_message']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#errorModal<?php echo $log['id']; ?>">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    
                                                    <!-- Modal dettagli errore -->
                                                    <div class="modal fade" id="errorModal<?php echo $log['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Dettagli Errore</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <h6>Informazioni Generali</h6>
                                                                    <ul>
                                                                        <li><strong>Data:</strong> <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></li>
                                                                        <li><strong>Server:</strong> <?php echo htmlspecialchars($log['server_name']); ?></li>
                                                                        <li><strong>Giocatore:</strong> <?php echo htmlspecialchars($log['minecraft_nick']); ?></li>
                                                                        <li><strong>Codice:</strong> <code><?php echo htmlspecialchars($log['vote_code']); ?></code></li>
                                                                    </ul>
                                                                    
                                                                    <h6>Messaggio di Errore</h6>
                                                                    <div class="alert alert-danger">
                                                                        <?php echo nl2br(htmlspecialchars($log['error_message'])); ?>
                                                                    </div>
                                                                    
                                                                    <?php if ($log['commands_executed']): ?>
                                                                        <h6>Comandi Eseguiti</h6>
                                                                        <pre class="bg-light p-2"><code><?php echo htmlspecialchars($log['commands_executed']); ?></code></pre>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php elseif ($log['commands_executed']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#commandsModal<?php echo $log['id']; ?>">
                                                        <i class="fas fa-terminal"></i>
                                                    </button>
                                                    
                                                    <!-- Modal comandi -->
                                                    <div class="modal fade" id="commandsModal<?php echo $log['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Comandi Eseguiti</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <h6>Informazioni Generali</h6>
                                                                    <ul>
                                                                        <li><strong>Data:</strong> <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></li>
                                                                        <li><strong>Server:</strong> <?php echo htmlspecialchars($log['server_name']); ?></li>
                                                                        <li><strong>Giocatore:</strong> <?php echo htmlspecialchars($log['minecraft_nick']); ?></li>
                                                                    </ul>
                                                                    
                                                                    <h6>Comandi</h6>
                                                                    <pre class="bg-dark text-light p-3 rounded"><code><?php echo htmlspecialchars($log['commands_executed']); ?></code></pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginazione -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginazione">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nessun log trovato con i filtri applicati.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>