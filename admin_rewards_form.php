<?php 
require_once 'config.php';

// Verifica che l'utente sia admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

include 'header.php'; 
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-<?php echo $reward ? 'edit' : 'plus'; ?>"></i> <?php echo $reward ? 'Modifica' : 'Nuova'; ?> Ricompensa</h2>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="?action=edit">
                        <input type="hidden" name="reward_id" value="<?php echo $reward ? $reward['id'] : ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="server_id" class="form-label">Server</label>
                                    <select class="form-select" id="server_id" name="server_id" required <?php echo $reward ? 'disabled' : ''; ?>>
                                        <option value="">Seleziona un server...</option>
                                        <?php foreach ($servers as $server): ?>
                                            <option value="<?php echo $server['id']; ?>" 
                                                    <?php echo ($reward && $reward['server_id'] == $server['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($server['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($reward): ?>
                                        <input type="hidden" name="server_id" value="<?php echo $reward['server_id']; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reward_name" class="form-label">Nome Ricompensa</label>
                                    <input type="text" class="form-control" id="reward_name" name="reward_name" 
                                           value="<?php echo $reward ? htmlspecialchars($reward['reward_name']) : ''; ?>" required>
                                    <div class="form-text">Nome descrittivo per questa ricompensa</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cooldown_hours" class="form-label">Cooldown (ore)</label>
                                    <input type="number" class="form-control" id="cooldown_hours" name="cooldown_hours" 
                                           value="<?php echo $reward ? $reward['cooldown_hours'] : '24'; ?>" min="1" max="168" required>
                                    <div class="form-text">Tempo di attesa tra un voto e l'altro (1-168 ore)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stato</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enabled" name="enabled" 
                                               <?php echo (!$reward || $reward['enabled']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enabled">
                                            Ricompensa attiva
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="commands" class="form-label">Comandi di Ricompensa</label>
                            <textarea class="form-control" id="commands" name="commands" rows="6" required 
                                      placeholder="Inserisci i comandi Minecraft, uno per riga&#10;Usa {player} per il nome del giocatore&#10;Esempio: give {player} diamond 5"><?php echo $reward ? htmlspecialchars($reward['commands']) : ''; ?></textarea>
                            <div class="form-text">
                                <p><strong>Variabili disponibili:</strong></p>
                                <ul>
                                    <li><code>{player}</code> - Nome del giocatore</li>
                                    <li><code>{server}</code> - ID del server votato</li>
                                    <li><code>{vote_code}</code> - Codice voto utilizzato</li>
                                </ul>
                                <p><strong>Esempi comandi:</strong></p>
                                <ul>
                                    <li><code>give {player} diamond 5</code></li>
                                    <li><code>eco give {player} 1000</code></li>
                                    <li><code>broadcast {player} ha votato e ricevuto una ricompensa!</code></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="admin_rewards.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Annulla
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salva Ricompensa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($reward): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Storico Ricompense</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $logs_stmt = $pdo->prepare("
                            SELECT 
                                rl.*,
                                u.username,
                                vc.vote_code
                            FROM sl_reward_logs rl
                            JOIN sl_vote_codes vc ON rl.vote_code_id = vc.id
                            JOIN sl_users u ON vc.user_id = u.id
                            WHERE vc.server_id = ?
                            ORDER BY rl.created_at DESC
                            LIMIT 50
                        ");
                        $logs_stmt->execute([$reward['server_id']]);
                        $logs = $logs_stmt->fetchAll();
                        ?>
                        
                        <?php if (count($logs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Giocatore</th>
                                            <th>Codice</th>
                                            <th>Stato</th>
                                            <th>Dettagli</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                                <td><code><?php echo htmlspecialchars($log['vote_code']); ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['reward_status'] === 'success' ? 'success' : ($log['reward_status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($log['reward_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($log['error_message']): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Nessuna ricompensa ancora distribuita per questo server.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'footer.php'; ?>