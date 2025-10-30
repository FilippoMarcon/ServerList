<?php
/**
 * Gestione Eventi Server
 * Permette agli owner di aggiungere eventi per i loro server
 */

require_once 'config.php';

$page_title = "Gestione Eventi Server";

$message = '';
$error = '';

// Verifica che l'utente sia loggato
if (!isLoggedIn()) {
    redirect('/login?next=/eventi-server');
}

// Gestione POST per aggiungere/modificare eventi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta o token CSRF non valido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_event') {
            $server_id = (int)($_POST['server_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date = $_POST['event_date'] ?? '';
            $event_time = $_POST['event_time'] ?? '';
            
            if ($server_id <= 0 || $title === '' || $event_date === '') {
                $error = 'Compila tutti i campi obbligatori.';
            } else {
                try {
                    // Verifica che il server appartenga all'utente
                    $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE id = ? AND owner_id = ?");
                    $stmt->execute([$server_id, $_SESSION['user_id']]);
                    if (!$stmt->fetch()) {
                        $error = 'Server non trovato o non hai i permessi per gestirlo.';
                    } else {
                        // Inserisci l'evento
                        $stmt = $pdo->prepare("INSERT INTO sl_server_events (server_id, title, description, event_date, event_time) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$server_id, $title, $description, $event_date, $event_time ?: null]);
                        $message = 'Evento aggiunto con successo!';
                    }
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta dell\'evento.';
                }
            }
        } elseif ($action === 'edit_event') {
            $event_id = (int)($_POST['event_id'] ?? 0);
            $server_id = (int)($_POST['server_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date = $_POST['event_date'] ?? '';
            $event_time = $_POST['event_time'] ?? '';
            
            if ($event_id <= 0 || $server_id <= 0 || $title === '' || $event_date === '') {
                $error = 'Compila tutti i campi obbligatori.';
            } else {
                try {
                    // Verifica che l'evento e il server appartengano all'utente
                    $stmt = $pdo->prepare("
                        SELECT e.id 
                        FROM sl_server_events e 
                        JOIN sl_servers s ON e.server_id = s.id 
                        WHERE e.id = ? AND s.owner_id = ?
                    ");
                    $stmt->execute([$event_id, $_SESSION['user_id']]);
                    if (!$stmt->fetch()) {
                        $error = 'Evento non trovato o non hai i permessi per modificarlo.';
                    } else {
                        // Verifica che il nuovo server appartenga all'utente
                        $stmt = $pdo->prepare("SELECT id FROM sl_servers WHERE id = ? AND owner_id = ?");
                        $stmt->execute([$server_id, $_SESSION['user_id']]);
                        if (!$stmt->fetch()) {
                            $error = 'Server non valido o non hai i permessi per usarlo.';
                        } else {
                            // Aggiorna l'evento
                            $stmt = $pdo->prepare("
                                UPDATE sl_server_events 
                                SET server_id = ?, title = ?, description = ?, event_date = ?, event_time = ? 
                                WHERE id = ?
                            ");
                            $stmt->execute([$server_id, $title, $description, $event_date, $event_time ?: null, $event_id]);
                            $message = 'Evento modificato con successo!';
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica dell\'evento.';
                }
            }
        } elseif ($action === 'delete_event') {
            $event_id = (int)($_POST['event_id'] ?? 0);
            try {
                // Verifica che l'evento appartenga a un server dell'utente
                $stmt = $pdo->prepare("DELETE e FROM sl_server_events e JOIN sl_servers s ON e.server_id = s.id WHERE e.id = ? AND s.owner_id = ?");
                $stmt->execute([$event_id, $_SESSION['user_id']]);
                $message = 'Evento eliminato con successo!';
            } catch (PDOException $e) {
                $error = 'Errore durante l\'eliminazione dell\'evento.';
            }
        }
    }
}

// Ottieni i server dell'utente
$user_servers = [];
try {
    $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE owner_id = ? AND is_active = 1 ORDER BY nome ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $user_servers = $stmt->fetchAll();
} catch (PDOException $e) {
    $user_servers = [];
}

// Ottieni gli eventi dell'utente
$user_events = [];
try {
    $stmt = $pdo->prepare("
        SELECT e.*, s.nome as server_name, s.logo_url
        FROM sl_server_events e 
        JOIN sl_servers s ON e.server_id = s.id 
        WHERE s.owner_id = ? 
        ORDER BY e.event_date DESC, e.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_events = $stmt->fetchAll();
} catch (PDOException $e) {
    $user_events = [];
}

include 'header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="events-page">
                <div class="page-header text-center mb-4">
                    <div class="events-icon mb-3">
                        <i class="bi bi-calendar-event" style="font-size: 4rem; color: var(--accent-purple);"></i>
                    </div>
                    <h1 class="page-title">Gestione Eventi Server</h1>
                    <p class="page-subtitle">Aggiungi eventi per i tuoi server e aumenta l'engagement</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($user_servers)): ?>
                    <div class="no-servers-prompt text-center">
                        <i class="bi bi-server" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <h3>Nessun Server Disponibile</h3>
                        <p>Non hai ancora aggiunto un server. Aggiungi il tuo server per poter creare eventi.</p>
                        <a href="/profile" class="btn btn-hero">
                            <i class="bi bi-plus-circle"></i> Aggiungi Server
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Form Aggiungi Evento -->
                    <div class="add-event-section mb-5">
                        <h3 class="section-title">
                            <i class="bi bi-plus-circle"></i> Aggiungi Nuovo Evento
                        </h3>
                        <form method="POST" action="/eventi-server" class="event-form">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="add_event">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="server_id" class="form-label">Server</label>
                                    <select name="server_id" id="server_id" class="form-control" required>
                                        <option value="">-- Seleziona un server --</option>
                                        <?php foreach ($user_servers as $srv): ?>
                                            <option value="<?php echo $srv['id']; ?>">
                                                <?php echo htmlspecialchars($srv['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Titolo Evento</label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="Es: Torneo PvP, Evento Build..." required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label">Data Evento</label>
                                    <input type="date" name="event_date" id="event_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="event_time" class="form-label">Ora (opzionale)</label>
                                    <input type="time" name="event_time" id="event_time" class="form-control">
                                </div>
                                
                                <div class="col-12">
                                    <label for="description" class="form-label">Descrizione (opzionale)</label>
                                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Descrivi l'evento..."></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-hero">
                                        <i class="bi bi-calendar-plus"></i> Aggiungi Evento
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Lista Eventi -->
                    <div class="events-list-section">
                        <h3 class="section-title">
                            <i class="bi bi-list-ul"></i> I Tuoi Eventi
                        </h3>
                        
                        <?php if (empty($user_events)): ?>
                            <div class="no-events text-center">
                                <i class="bi bi-calendar-x" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <p>Non hai ancora creato eventi. Aggiungi il primo evento per i tuoi server!</p>
                            </div>
                        <?php else: ?>
                            <div class="events-grid">
                                <?php foreach ($user_events as $event): ?>
                                    <?php
                                    $event_date = new DateTime($event['event_date']);
                                    $today = new DateTime();
                                    $is_past = $event_date < $today;
                                    ?>
                                    <div class="event-card <?php echo $is_past ? 'event-past' : 'event-upcoming'; ?>">
                                        <div class="event-header">
                                            <div class="event-date-display">
                                                <div class="event-day"><?php echo $event_date->format('d'); ?></div>
                                                <div class="event-month"><?php echo $event_date->format('M'); ?></div>
                                            </div>
                                            <div class="event-info">
                                                <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                                <div class="event-server-with-logo">
                                                    <?php if (!empty($event['logo_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($event['logo_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($event['server_name']); ?>" 
                                                             class="event-server-logo"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <div class="event-server-logo-fallback" style="display: none;">
                                                            <i class="bi bi-server"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="event-server-logo-fallback">
                                                            <i class="bi bi-server"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="event-server-name"><?php echo htmlspecialchars($event['server_name']); ?></span>
                                                </div>
                                                <?php if (!empty($event['event_time'])): ?>
                                                    <div class="event-time">
                                                        <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="event-actions">
                                                <?php if (!$is_past): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editEvent(<?php echo $event['id']; ?>)" title="Modifica evento">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php endif; ?>
                                                <form method="POST" action="/eventi-server" class="delete-form d-inline">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="action" value="delete_event">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questo evento?');">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php if (!empty($event['description'])): ?>
                                            <div class="event-description">
                                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="event-status">
                                            <?php if ($is_past): ?>
                                                <span class="status-badge status-past">
                                                    <i class="bi bi-check-circle"></i> Terminato
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-upcoming">
                                                    <i class="bi bi-calendar-event"></i> Prossimo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifica Evento -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-color);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title" id="editEventModalLabel" style="color: var(--text-primary);">
                    <i class="bi bi-pencil-square"></i> Modifica Evento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <form method="POST" action="/eventi-server" id="editEventForm">
                <div class="modal-body">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="action" value="edit_event">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_server_id" class="form-label" style="color: var(--text-primary); font-weight: 600;">Server</label>
                            <select name="server_id" id="edit_server_id" class="form-control" required style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                                <option value="">-- Seleziona un server --</option>
                                <?php foreach ($user_servers as $srv): ?>
                                    <option value="<?php echo $srv['id']; ?>">
                                        <?php echo htmlspecialchars($srv['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_title" class="form-label" style="color: var(--text-primary); font-weight: 600;">Titolo Evento</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_event_date" class="form-label" style="color: var(--text-primary); font-weight: 600;">Data Evento</label>
                            <input type="date" name="event_date" id="edit_event_date" class="form-control" required style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_event_time" class="form-label" style="color: var(--text-primary); font-weight: 600;">Ora (opzionale)</label>
                            <input type="time" name="event_time" id="edit_event_time" class="form-control" style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary);">
                        </div>
                        
                        <div class="col-12">
                            <label for="edit_description" class="form-label" style="color: var(--text-primary); font-weight: 600;">Descrizione (opzionale)</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: var(--primary-bg); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        <i class="bi bi-x-circle"></i> Annulla
                    </button>
                    <button type="submit" class="btn btn-hero">
                        <i class="bi bi-check-circle"></i> Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.events-page {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 2rem;
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: var(--accent-purple);
}

.event-form {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.event-form .form-label {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.event-form .form-control {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 1rem;
}

.event-form .form-control::placeholder {
    color: #cbd5e1;
    opacity: 1;
    font-weight: 500;
}

.event-form .form-control:focus {
    border-color: var(--accent-purple);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    outline: none;
    background: var(--secondary-bg);
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.event-card {
    background: var(--primary-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
}

.event-upcoming {
    border-left: 4px solid var(--accent-purple);
}

.event-past {
    border-left: 4px solid var(--text-muted);
    opacity: 0.7;
}

.event-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.event-date-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--gradient-primary);
    color: white;
    border-radius: 8px;
    padding: 0.75rem;
    min-width: 60px;
}

.event-day {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.event-month {
    font-size: 0.8rem;
    text-transform: uppercase;
    margin-top: 2px;
}

.event-info {
    flex: 1;
}

.event-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.event-server-with-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.event-server-logo {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid var(--border-color);
}

.event-server-logo-fallback {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background: var(--accent-purple);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
    border: 1px solid var(--border-color);
}

.event-server-name {
    color: var(--text-secondary);
    font-weight: 600;
}

.event-time {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.event-description {
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: var(--card-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.event-status {
    display: flex;
    justify-content: flex-end;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-upcoming {
    background: rgba(124, 58, 237, 0.1);
    color: var(--accent-purple);
    border: 1px solid rgba(124, 58, 237, 0.3);
}

.status-past {
    background: rgba(156, 163, 175, 0.1);
    color: var(--text-muted);
    border: 1px solid rgba(156, 163, 175, 0.3);
}

.no-servers-prompt, .no-events {
    padding: 2rem;
    background: var(--primary-bg);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.no-events p {
    color: var(--text-secondary);
    margin-bottom: 0;
}

/* Miglioramento visibilità pulsanti e testo */
.btn-hero {
    background: var(--gradient-primary);
    border: none;
    color: #ffffff !important;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-hero:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: #ffffff !important;
}

/* Miglioramento contrasto per i form */
.event-form .form-control {
    font-weight: 500;
}

.event-form .form-label {
    font-size: 1rem;
    font-weight: 700;
}

/* Supporto tema chiaro */
[data-theme="light"] .event-form .form-control::placeholder {
    color: #64748b;
    opacity: 1;
}

[data-theme="light"] .event-form .form-control {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #0f172a;
}

[data-theme="light"] .event-form .form-control:focus {
    background: #ffffff;
    border-color: var(--accent-purple);
}

@media (max-width: 768px) {
    .events-page {
        padding: 1rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .event-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

.event-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.modal-content .form-control:focus {
    border-color: var(--accent-purple);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    outline: none;
}

/* Supporto tema chiaro per modal */
[data-theme="light"] .modal-content {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
}

[data-theme="light"] .modal-header,
[data-theme="light"] .modal-footer {
    border-color: #e2e8f0 !important;
}

[data-theme="light"] .modal-content .form-control {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #0f172a !important;
}
</style>

<script>
// Dati degli eventi per JavaScript
const eventsData = <?php echo json_encode($user_events); ?>;

function editEvent(eventId) {
    // Trova l'evento nei dati
    const event = eventsData.find(e => e.id == eventId);
    if (!event) {
        alert('Evento non trovato');
        return;
    }
    
    // Popola il form di modifica
    document.getElementById('edit_event_id').value = event.id;
    document.getElementById('edit_server_id').value = event.server_id;
    document.getElementById('edit_title').value = event.title;
    document.getElementById('edit_event_date').value = event.event_date;
    document.getElementById('edit_event_time').value = event.event_time || '';
    document.getElementById('edit_description').value = event.description || '';
    
    // Mostra il modal
    const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
    modal.show();
}

// Gestione focus per i form controls nel modal
document.addEventListener('DOMContentLoaded', function() {
    const modalControls = document.querySelectorAll('#editEventModal .form-control');
    modalControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.style.background = 'var(--secondary-bg)';
        });
        
        control.addEventListener('blur', function() {
            this.style.background = 'var(--primary-bg)';
        });
    });
});
</script>

<?php include 'footer.php'; ?>