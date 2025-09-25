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
            <h2><i class="fas fa-chart-bar"></i> Statistiche Ricompense</h2>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Codici Totali</h5>
                            <h2 class="text-primary">
                                <?php echo array_sum(array_column($stats, 'total_codes')); ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Codici Usati</h5>
                            <h2 class="text-success">
                                <?php echo array_sum(array_column($stats, 'used_codes')); ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Codici Attivi</h5>
                            <h2 class="text-warning">
                                <?php echo array_sum(array_column($stats, 'pending_codes')); ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Ricompense Erogate</h5>
                            <h2 class="text-info">
                                <?php echo array_sum(array_column($stats, 'successful_rewards')); ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dettagli per Server</h5>
                    <div>
                        <a href="admin_rewards.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Torna alle Ricompense
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Server</th>
                                    <th class="text-center">Codici Generati</th>
                                    <th class="text-center">Codici Usati</th>
                                    <th class="text-center">Codici Attivi</th>
                                    <th class="text-center">Ricompense Erogate</th>
                                    <th class="text-center">Tasso di Conversione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_codes = 0;
                                $total_used = 0;
                                $total_pending = 0;
                                $total_rewards = 0;
                                
                                foreach ($stats as $stat): 
                                    $conversion_rate = $stat['total_codes'] > 0 ? 
                                        round(($stat['used_codes'] / $stat['total_codes']) * 100, 1) : 0;
                                    
                                    $total_codes += $stat['total_codes'];
                                    $total_used += $stat['used_codes'];
                                    $total_pending += $stat['pending_codes'];
                                    $total_rewards += $stat['successful_rewards'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($stat['server_name']); ?></strong>
                                        </td>
                                        <td class="text-center"><?php echo $stat['total_codes'] ?: 0; ?></td>
                                        <td class="text-center text-success"><?php echo $stat['used_codes'] ?: 0; ?></td>
                                        <td class="text-center text-warning"><?php echo $stat['pending_codes'] ?: 0; ?></td>
                                        <td class="text-center text-info"><?php echo $stat['successful_rewards'] ?: 0; ?></td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $conversion_rate; ?>%" 
                                                     aria-valuenow="<?php echo $conversion_rate; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $conversion_rate; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($stats) > 1): ?>
                                    <tr class="table-active">
                                        <td><strong>TOTALE</strong></td>
                                        <td class="text-center"><strong><?php echo $total_codes; ?></strong></td>
                                        <td class="text-center text-success"><strong><?php echo $total_used; ?></strong></td>
                                        <td class="text-center text-warning"><strong><?php echo $total_pending; ?></strong></td>
                                        <td class="text-center text-info"><strong><?php echo $total_rewards; ?></strong></td>
                                        <td class="text-center">
                                            <strong>
                                                <?php echo $total_codes > 0 ? round(($total_used / $total_codes) * 100, 1) : 0; ?>%
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($server_id === 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Grafico Andamento</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="rewardsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($server_id === 0): ?>
        // Prepara dati per il grafico
        const serverNames = <?php echo json_encode(array_column($stats, 'server_name')); ?>;
        const totalCodes = <?php echo json_encode(array_column($stats, 'total_codes')); ?>;
        const usedCodes = <?php echo json_encode(array_column($stats, 'used_codes')); ?>;
        const rewards = <?php echo json_encode(array_column($stats, 'successful_rewards')); ?>;
        
        const ctx = document.getElementById('rewardsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: serverNames,
                datasets: [
                    {
                        label: 'Codici Generati',
                        data: totalCodes,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Codici Usati',
                        data: usedCodes,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ricompense Erogate',
                        data: rewards,
                        backgroundColor: 'rgba(255, 159, 64, 0.8)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Statistiche Ricompense per Server'
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    <?php endif; ?>
});
</script>

<?php include 'footer.php'; ?>