<?php
// Vista di modifica server per Admin
// Questo file viene incluso da admin.php quando action=edit_server

if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

$server_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$server = null;

// Assicura che esistano tutte le colonne necessarie
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN website_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN shop_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN discord_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN telegram_url VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN modalita JSON NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN staff_list JSON NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN social_links TEXT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN in_costruzione TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_host VARCHAR(255) NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_port INT DEFAULT 8192"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN votifier_key TEXT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE sl_servers ADD COLUMN data_aggiornamento TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP"); } catch (Exception $e) {}

// Gestione salvataggio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_server_admin'])) {

    $server_id = (int)($_POST['server_id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');
    $ip = sanitize($_POST['ip'] ?? '');
    $versione = sanitize($_POST['versione'] ?? '');
    $tipo_server = sanitize($_POST['tipo_server'] ?? 'Java & Bedrock');
    $descrizione = sanitizeQuillContent($_POST['descrizione'] ?? '');
    $banner_url = sanitize($_POST['banner_url'] ?? '');
    $logo_url = sanitize($_POST['logo_url'] ?? '');
    $modalita_input = $_POST['modalita'] ?? [];
    $staff_list_json = $_POST['staff_list_json'] ?? '';
    $social_links_json = $_POST['social_links_json'] ?? '';
    $votifier_host = sanitize($_POST['votifier_host'] ?? '');
    $votifier_port = isset($_POST['votifier_port']) ? (int)$_POST['votifier_port'] : 8192;
    $votifier_key = trim($_POST['votifier_key'] ?? '');

    // Se è stato passato un CSV, convertilo in array
    if (isset($_POST['modalita_csv']) && is_string($_POST['modalita_csv'])) {
        $csv = trim($_POST['modalita_csv']);
        if ($csv !== '') {
            $modalita_input = array_filter(array_map(function($t){ return trim($t); }, explode(',', $csv)), function($t){ return $t !== ''; });
        }
    }

    $modalita_json = json_encode(array_values($modalita_input));
    
    if ($server_id > 0 && $nome !== '' && $ip !== '' && $versione !== '') {
        try {
            // Prova UPDATE completo con tutti i campi
            try {
                $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ?, website_url = ?, shop_url = ?, discord_url = ?, telegram_url = ?, modalita = ?, staff_list = ?, social_links = ?, in_costruzione = ?, votifier_host = ?, votifier_port = ?, votifier_key = ?, data_aggiornamento = NOW() WHERE id = ?");
                $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $website_url, $shop_url, $discord_url, $telegram_url, $modalita_json, $staff_list_json, $social_links_json, $in_costruzione, $votifier_host, $votifier_port, $votifier_key, $server_id]);
                
                // Log modifiche per admin
                if (file_exists(__DIR__ . '/api_log_activity.php')) {
                    require_once __DIR__ . '/api_log_activity.php';
                    logActivity('server_updated_by_admin', 'server', $server_id, null, ['admin_id' => $_SESSION['user_id'] ?? 0]);
                }
                
                $_SESSION['success_message'] = 'Server aggiornato correttamente.';
                redirect('/admin?action=servers');
            } catch (PDOException $e1) {
                // Fallback: solo campi base se alcuni non esistono
                error_log("Errore UPDATE admin completo: " . $e1->getMessage());
                try {
                    $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ?, modalita = ?, staff_list = ?, social_links = ?, votifier_host = ?, votifier_port = ?, votifier_key = ?, data_aggiornamento = NOW() WHERE id = ?");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $modalita_json, $staff_list_json, $social_links_json, $votifier_host, $votifier_port, $votifier_key, $server_id]);
                    $_SESSION['success_message'] = 'Server aggiornato correttamente (alcuni campi opzionali non disponibili).';
                    redirect('/admin?action=servers');
                } catch (PDOException $e2) {
                    // Ultimo fallback: solo campi essenziali
                    error_log("Errore UPDATE admin fallback: " . $e2->getMessage());
                    $stmt = $pdo->prepare("UPDATE sl_servers SET nome = ?, ip = ?, versione = ?, tipo_server = ?, descrizione = ?, banner_url = ?, logo_url = ?, data_aggiornamento = NOW() WHERE id = ?");
                    $stmt->execute([$nome, $ip, $versione, $tipo_server, $descrizione, $banner_url, $logo_url, $server_id]);
                    $_SESSION['success_message'] = 'Server aggiornato (solo campi base).';
                    redirect('/admin?action=servers');
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Errore durante l\'aggiornamento del server.';
            error_log("Errore UPDATE admin: " . $e->getMessage());
            redirect('/admin?action=servers');
        }
    } else {
        $_SESSION['error_message'] = 'Dati non validi.';
        redirect('/admin?action=servers');
    }
}

// Carica dati server
if ($server_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, u.minecraft_nick AS owner_nick FROM sl_servers s LEFT JOIN sl_users u ON s.owner_id = u.id WHERE s.id = ?");
        $stmt->execute([$server_id]);
        $server = $stmt->fetch();
        if (!$server) {
            $error = 'Server non trovato.';
        }
    } catch (PDOException $e) {
        $error = 'Errore nel caricamento del server.';
    }
}

$modalita_array = [];
if (!empty($server['modalita'])) {
    $decoded = json_decode($server['modalita'], true);
    if (is_array($decoded)) { $modalita_array = $decoded; }
}
$staff_list_array = [];
if (!empty($server['staff_list'])) {
    $decoded = json_decode($server['staff_list'], true);
    if (is_array($decoded)) { $staff_list_array = $decoded; }
}
$social_links_array = [];
if (!empty($server['social_links'])) {
    $decoded = json_decode($server['social_links'], true);
    if (is_array($decoded)) { $social_links_array = $decoded; }
}

?>
<div class="admin-section">
    <div class="admin-header d-flex align-items-center justify-content-between">
        <h3><i class="bi bi-pencil-square"></i> Modifica Server</h3>
        <a href="?action=servers" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Torna alla lista</a>
    </div>

    <?php if (!empty($server)): ?>
        <div class="card mb-3" style="background: var(--card-bg); border:1px solid var(--border-color);">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars($server['logo_url'] ?: 'logo.png') ?>" alt="Logo" style="width:48px; height:48px; border-radius:8px; object-fit:cover;">
                    <div>
                        <div class="text-white fw-bold">ID #<?= $server['id'] ?> — <?= htmlspecialchars($server['nome']) ?></div>
                        <div class="text-secondary" style="font-size:12px;">Owner: <?= htmlspecialchars($server['owner_nick'] ?? '-') ?> • Stato: <?= (int)$server['is_active'] === 1 ? 'Attivo' : ((int)$server['is_active'] === 2 ? 'In revisione' : 'Disabilitato') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="?action=edit_server&id=<?= $server['id'] ?>" onsubmit="prepareServerPayload()">
            <?php if (function_exists('csrfInput')) echo csrfInput(); ?>
            <input type="hidden" name="save_server_admin" value="1">
            <input type="hidden" name="server_id" value="<?= $server['id'] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome Server</label>
                    <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($server['nome']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">IP Server</label>
                    <input type="text" class="form-control" name="ip" value="<?= htmlspecialchars($server['ip']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Versione</label>
                    <input type="text" class="form-control" name="versione" value="<?= htmlspecialchars($server['versione'] ?: '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Edizione</label>
                    <select class="form-select" name="tipo_server">
                        <?php $tipo = $server['tipo_server'] ?: 'Java & Bedrock'; ?>
                        <option value="Java & Bedrock" <?= $tipo === 'Java & Bedrock' ? 'selected' : '' ?>>Java & Bedrock</option>
                        <option value="Java" <?= $tipo === 'Java' ? 'selected' : '' ?>>Java</option>
                        <option value="Bedrock" <?= $tipo === 'Bedrock' ? 'selected' : '' ?>>Bedrock</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Logo URL</label>
                    <input type="url" class="form-control" name="logo_url" value="<?= htmlspecialchars($server['logo_url'] ?: '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Banner URL</label>
                    <input type="url" class="form-control" name="banner_url" value="<?= htmlspecialchars($server['banner_url'] ?: '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Descrizione</label>
                    <textarea class="form-control" name="descrizione" rows="6" placeholder="Descrizione dettagliata del server..."><?= htmlspecialchars($server['descrizione'] ?: '') ?></textarea>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="bi bi-link-45deg"></i> Link Social Rapidi</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Sito Web</label>
                    <input type="url" class="form-control" name="website_url" value="<?= htmlspecialchars($server['website_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Shop</label>
                    <input type="url" class="form-control" name="shop_url" value="<?= htmlspecialchars($server['shop_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Discord</label>
                    <input type="url" class="form-control" name="discord_url" value="<?= htmlspecialchars($server['discord_url'] ?? '') ?>" placeholder="https://discord.gg/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telegram</label>
                    <input type="url" class="form-control" name="telegram_url" value="<?= htmlspecialchars($server['telegram_url'] ?? '') ?>" placeholder="https://t.me/...">
                </div>
                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="in_costruzione" id="in_costruzione" value="1" <?= !empty($server['in_costruzione']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="in_costruzione">
                            <i class="bi bi-cone-striped"></i> Server in costruzione (mostra badge)
                        </label>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Modalità di gioco (CSV)</label>
                    <input type="text" class="form-control" id="modalita_csv" name="modalita_csv" value="<?= htmlspecialchars(implode(', ', $modalita_array)) ?>" placeholder="es. survival, skyblock, bedwars">
                    <p class="text-secondary" style="font-size:12px; margin-top:6px;">Separare con virgole. Verranno salvate come tag.</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Anteprima Tag</label>
                    <div id="modalita-preview" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
                </div>
            </div>

            <input type="hidden" name="modalita[]" id="modalita_hidden_holder">

            <hr>
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label">Staff del Server</label>
                        <button type="button" class="btn btn-sm btn-secondary" id="add-rank-btn"><i class="bi bi-award"></i> Aggiungi Rank</button>
                    </div>
                    <div id="stafflist-ranks" style="margin-top:8px;"></div>
                    <input type="hidden" id="staff_list_json" name="staff_list_json" value='<?= htmlspecialchars(json_encode($staff_list_array)) ?>'>
                </div>
            </div>

            <hr>
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label">Link Social</label>
                        <button type="button" class="btn btn-sm btn-secondary" id="add-social-btn"><i class="bi bi-link-45deg"></i> Aggiungi Link</button>
                    </div>
                    <div id="sociallinks-list" style="margin-top:8px;"></div>
                    <input type="hidden" id="social_links_json" name="social_links_json" value='<?= htmlspecialchars(json_encode($social_links_array)) ?>'>
                    <p class="text-secondary" style="font-size:12px; margin-top:6px;">Titoli tipici: Instagram, Discord, YouTube, Sito, Shop...</p>
                </div>
            </div>

            <hr>
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-broadcast" style="font-size:20px; color:var(--accent-purple);"></i>
                        <label class="form-label mb-0">Configurazione Votifier</label>
                    </div>
                    <p class="text-secondary" style="font-size:12px; margin-bottom:12px;">
                        Votifier invia automaticamente i voti al tuo server Minecraft. 
                        <a href="/VOTIFIER_SETUP.md" target="_blank" style="color:var(--accent-purple);">Leggi la guida setup</a>
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Host Votifier</label>
                    <input type="text" class="form-control" name="votifier_host" value="<?= htmlspecialchars($server['votifier_host'] ?? '') ?>" placeholder="es. 123.45.67.89 o play.server.it">
                    <small class="text-secondary">IP o hostname del server Minecraft</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Porta</label>
                    <input type="number" class="form-control" name="votifier_port" value="<?= htmlspecialchars($server['votifier_port'] ?? '8192') ?>" placeholder="8192">
                    <small class="text-secondary">Default: 8192</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Chiave Pubblica RSA</label>
                    <textarea class="form-control" name="votifier_key" rows="3" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----" style="font-family:monospace; font-size:11px;"><?= htmlspecialchars($server['votifier_key'] ?? '') ?></textarea>
                    <small class="text-secondary">Copia da plugins/Votifier/rsa/public.pem</small>
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-outline-info" id="test-votifier-btn">
                        <i class="bi bi-wifi"></i> Testa Connessione Votifier
                    </button>
                    <div id="votifier-test-result" style="margin-top:8px;"></div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salva modifiche</button>
                <a href="?action=servers" class="btn btn-outline-secondary">Annulla</a>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">Nessun server trovato.</div>
    <?php endif; ?>
</div>

<script>
function prepareServerPayload() {
    // Prepara tag modalita → hidden
    const csv = (document.getElementById('modalita_csv').value || '').trim();
    const tags = csv ? csv.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
    // Inserisci come JSON in un hidden per coerenza
    const holder = document.getElementById('modalita_hidden_holder');
    // Non possiamo creare dynamic[] qui; usiamo name="modalita[]" e creiamo inputs
    // Pulisci eventuali inputs già creati
    const form = holder.closest('form');
    Array.from(form.querySelectorAll('input[name="modalita[]"]')).forEach(el => { if (el !== holder) el.remove(); });
    if (tags.length === 0) {
        holder.value = '';
    } else {
        holder.removeAttribute('id'); // holder usato solo come ancoraggio
        tags.forEach(t => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'modalita[]';
            inp.value = t;
            form.appendChild(inp);
        });
    }
}

function renderModalitaPreview() {
    const csv = (document.getElementById('modalita_csv').value || '').trim();
    const tags = csv ? csv.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
    const wrap = document.getElementById('modalita-preview');
    wrap.innerHTML = '';
    tags.forEach(tag => {
        const el = document.createElement('span');
        el.textContent = tag;
        el.className = 'badge bg-secondary';
        el.style.cssText = 'padding:6px 8px; border-radius:8px; font-size:12px;';
        wrap.appendChild(el);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const csvInput = document.getElementById('modalita_csv');
    if (csvInput) {
        csvInput.addEventListener('input', renderModalitaPreview);
        renderModalitaPreview();
    }
});
</script>

<script>
// StaffList Editor JS (basato su profile.php)
document.addEventListener('DOMContentLoaded', function() {
    const AVATAR_API = '<?php echo AVATAR_API; ?>';
    const ranksContainer = document.getElementById('stafflist-ranks');
    const addRankBtn = document.getElementById('add-rank-btn');
    const staffListInput = document.getElementById('staff_list_json');

    const initialStaff = (function(){
        try { return JSON.parse(staffListInput.value || '[]'); } catch (e) { return []; }
    })();

    function renderRanks(data) {
        ranksContainer.innerHTML = '';
        data.forEach((group, idx) => {
            const groupEl = document.createElement('div');
            groupEl.className = 'staff-rank-group';
            groupEl.style.cssText = 'background: var(--card-bg); border:1px solid var(--border-color); padding:10px; border-radius:8px; margin-bottom:10px;';

            const headerEl = document.createElement('div');
            headerEl.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;';
            headerEl.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-award"></i>
                    <input type="text" class="rank-title-input" placeholder="Nome Rank (es. Owner, Admin, Helper)" value="${escapeHtml(group.rank || '')}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:260px;">
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="btn btn-sm btn-secondary add-member-btn"><i class="bi bi-person-plus"></i> Aggiungi Staffer</button>
                    <button type="button" class="btn btn-sm btn-danger remove-rank-btn"><i class="bi bi-trash"></i></button>
                </div>
            `;
            groupEl.appendChild(headerEl);

            const membersEl = document.createElement('div');
            membersEl.className = 'rank-members';
            membersEl.style.cssText = 'display:flex; flex-direction:column; gap:6px;';

            (group.members || []).forEach(member => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex; gap:8px; align-items:center;';
                const nickSafe = escapeHtml(member);
                row.innerHTML = `
                    <img class="member-avatar" src="${AVATAR_API}/${encodeURIComponent(nickSafe || 'MHF_Steve')}" alt="Avatar" width="24" height="24" style="border-radius:50%;">
                    <input type="text" class="member-name-input" placeholder="Nickname staffer" value="${nickSafe}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:240px;">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn"><i class="bi bi-x"></i></button>
                `;
                membersEl.appendChild(row);
            });

            groupEl.appendChild(membersEl);
            ranksContainer.appendChild(groupEl);
        });
        syncHiddenInput();
    }

    function syncHiddenInput() {
        const groups = Array.from(ranksContainer.querySelectorAll('.staff-rank-group')).map(groupEl => {
            const rank = groupEl.querySelector('.rank-title-input').value.trim();
            const members = Array.from(groupEl.querySelectorAll('.member-name-input'))
                .map(inp => inp.value.trim())
                .filter(v => v.length > 0);
            return { rank, members };
        }).filter(g => g.rank.length > 0);
        staffListInput.value = JSON.stringify(groups);
    }

    function escapeHtml(str) { return (str || '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s])); }

    ranksContainer.addEventListener('input', function(e) {
        syncHiddenInput();
        if (e.target && e.target.classList.contains('member-name-input')) {
            const row = e.target.closest('div');
            const img = row ? row.querySelector('.member-avatar') : null;
            if (img) {
                const nick = e.target.value.trim() || 'MHF_Steve';
                img.src = '<?php echo AVATAR_API; ?>/' + encodeURIComponent(nick);
            }
        }
    });
    ranksContainer.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;
        if (target.classList.contains('add-member-btn')) {
            const groupEl = target.closest('.staff-rank-group');
            const membersEl = groupEl.querySelector('.rank-members');
            const row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; align-items:center;';
            row.innerHTML = `
                <img class="member-avatar" src="<?php echo AVATAR_API; ?>/MHF_Steve" alt="Avatar" width="24" height="24" style="border-radius:50%;">
                <input type="text" class="member-name-input" placeholder="Nickname staffer" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:240px;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn"><i class="bi bi-x"></i></button>
            `;
            membersEl.appendChild(row);
            syncHiddenInput();
        } else if (target.classList.contains('remove-member-btn')) {
            const row = target.closest('div');
            row.remove();
            syncHiddenInput();
        } else if (target.classList.contains('remove-rank-btn')) {
            const groupEl = target.closest('.staff-rank-group');
            groupEl.remove();
            syncHiddenInput();
        }
    });

    addRankBtn.addEventListener('click', function() {
        const group = { rank: '', members: [] };
        initialStaff.push(group);
        renderRanks(initialStaff);
    });

    const initData = Array.isArray(initialStaff) && initialStaff.length ? initialStaff : [];
    renderRanks(initData);
});
</script>

<script>
// Social Links Editor JS (basato su profile.php)
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('sociallinks-list');
    const addSocialBtn = document.getElementById('add-social-btn');
    const socialLinksInput = document.getElementById('social_links_json');
    const initialSocial = (function(){
        try { return JSON.parse(socialLinksInput.value || '[]'); } catch (e) { return []; }
    })();

    function renderSocial(data) {
        listContainer.innerHTML = '';
        data.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'social-row';
            row.style.cssText = 'display:flex; gap:8px; align-items:center; margin-bottom:8px;';
            row.innerHTML = `
                <input type="text" class="social-title-input" placeholder="Titolo (es. Instagram)" value="${escapeHtml(item.title || '')}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:200px;">
                <input type="url" class="social-url-input" placeholder="https://..." value="${escapeHtml(item.url || '')}" style="background: var(--card-bg); color:white; border:1px solid var(--border-color); padding:6px 8px; border-radius:6px; min-width:280px;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-social-btn"><i class="bi bi-x"></i></button>
            `;
            listContainer.appendChild(row);
        });
        syncSocialInput();
    }

    function syncSocialInput() {
        const items = Array.from(listContainer.querySelectorAll('.social-row')).map(row => {
            const title = row.querySelector('.social-title-input').value.trim();
            const url = row.querySelector('.social-url-input').value.trim();
            return { title, url };
        }).filter(i => i.url.length > 0);
        socialLinksInput.value = JSON.stringify(items);
    }

    function escapeHtml(str) { return (str || '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s])); }

    listContainer.addEventListener('input', function() { syncSocialInput(); });
    listContainer.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        if (btn.classList.contains('remove-social-btn')) {
            const row = btn.closest('div');
            row.remove();
            syncSocialInput();
        }
    });

    addSocialBtn.addEventListener('click', function() {
        initialSocial.push({ title: '', url: '' });
        renderSocial(initialSocial);
    });

    const initData = Array.isArray(initialSocial) && initialSocial.length ? initialSocial : [];
    renderSocial(initData);
});
</script>

<script>
// Test Votifier Connection
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-votifier-btn');
    const resultDiv = document.getElementById('votifier-test-result');
    
    if (testBtn) {
        testBtn.addEventListener('click', async function() {
            const host = document.querySelector('input[name="votifier_host"]').value.trim();
            const port = document.querySelector('input[name="votifier_port"]').value.trim();
            const key = document.querySelector('textarea[name="votifier_key"]').value.trim();
            
            if (!host || !port || !key) {
                resultDiv.innerHTML = '<div class="alert alert-warning" style="font-size:13px; padding:8px;">Compila tutti i campi Votifier prima di testare.</div>';
                return;
            }
            
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Test in corso...';
            resultDiv.innerHTML = '<div class="alert alert-info" style="font-size:13px; padding:8px;">Connessione a ' + host + ':' + port + '...</div>';
            
            try {
                const response = await fetch('test_votifier.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ host, port, key })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success" style="font-size:13px; padding:8px;"><i class="bi bi-check-circle"></i> Connessione riuscita! Banner: ' + data.banner + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger" style="font-size:13px; padding:8px;"><i class="bi bi-x-circle"></i> Errore: ' + data.error + '</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="alert alert-danger" style="font-size:13px; padding:8px;"><i class="bi bi-x-circle"></i> Errore di rete: ' + error.message + '</div>';
            } finally {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-wifi"></i> Testa Connessione Votifier';
            }
        });
    }
});
</script>