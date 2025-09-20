<?php
require_once 'config.php';
require_once 'header.php';

// Controlla se l'utente è loggato
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Recupera i dati dell'utente dal database
try {
    $stmt = $pdo->prepare("SELECT username, email, minecraft_nick FROM sl_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Utente non trovato, reindirizza o mostra un errore
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Errore nel recupero dati utente: " . $e->getMessage());
    // Potresti voler mostrare un messaggio di errore all'utente
    $user = null;
}

?>

<div class="container mt-5">
    <h2>Profilo Utente</h2>
    <?php if ($user): ?>
        <div class="text-center mb-4">
            <img src="https://cravatar.eu/avatar/<?php echo htmlspecialchars($user['minecraft_nick'] ?: 'MHF_Steve'); ?>/128.png" alt="Avatar" class="rounded-circle" width="128" height="128">
        </div>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Nickname Minecraft:</strong> 
            <?php if (!empty($user['minecraft_nick'])): ?>
                <?php echo htmlspecialchars($user['minecraft_nick']); ?>
            <?php else: ?>
                Non impostato
            <?php endif; ?>
        </p>
        <!-- Aggiungi qui altre informazioni del profilo se necessario -->
    <?php else: ?>
        <p class="alert alert-danger">Impossibile caricare i dati del profilo. Si prega di riprovare più tardi o contattare l'assistenza.</p>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>