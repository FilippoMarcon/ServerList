<?php
/**
 * Script per generare licenze server univoche
 * Genera codici di 24 caratteri alfanumerici
 */

require_once 'config.php';

/**
 * Genera una licenza server univoca di 24 caratteri
 */
function generateServerLicense() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $license = '';
    
    for ($i = 0; $i < 24; $i++) {
        $license .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $license;
}

/**
 * Crea o rigenera la licenza per un server
 */
function createServerLicense($server_id) {
    global $pdo;
    
    try {
        // Verifica che il server esista
        $stmt = $pdo->prepare("SELECT id, nome FROM sl_servers WHERE id = ?");
        $stmt->execute([$server_id]);
        $server = $stmt->fetch();
        
        if (!$server) {
            return ['success' => false, 'error' => 'Server non trovato'];
        }
        
        // Genera una licenza univoca
        $max_attempts = 10;
        $license_key = '';
        
        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $license_key = generateServerLicense();
            
            // Verifica che non esista già
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_server_licenses WHERE license_key = ?");
            $stmt->execute([$license_key]);
            
            if ($stmt->fetchColumn() == 0) {
                break; // Licenza univoca trovata
            }
            
            if ($attempt === $max_attempts - 1) {
                return ['success' => false, 'error' => 'Impossibile generare una licenza univoca'];
            }
        }
        
        // Inserisci o aggiorna la licenza
        $stmt = $pdo->prepare("
            INSERT INTO sl_server_licenses (server_id, license_key, is_active) 
            VALUES (?, ?, 1) 
            ON DUPLICATE KEY UPDATE 
                license_key = VALUES(license_key),
                is_active = 1,
                created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$server_id, $license_key]);
        
        return [
            'success' => true,
            'license_key' => $license_key,
            'server_id' => $server_id,
            'server_name' => $server['nome']
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Errore database: ' . $e->getMessage()];
    }
}

/**
 * Ottiene la licenza di un server
 */
function getServerLicense($server_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT sl.*, s.nome as server_name 
            FROM sl_server_licenses sl
            JOIN sl_servers s ON sl.server_id = s.id
            WHERE sl.server_id = ? AND sl.is_active = 1
        ");
        $stmt->execute([$server_id]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Verifica la validità di una licenza
 */
function validateLicense($license_key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT sl.*, s.nome as server_name 
            FROM sl_server_licenses sl
            JOIN sl_servers s ON sl.server_id = s.id
            WHERE sl.license_key = ? AND sl.is_active = 1
        ");
        $stmt->execute([$license_key]);
        $license = $stmt->fetch();
        
        if ($license) {
            // Aggiorna last_used e usage_count
            $stmt = $pdo->prepare("
                UPDATE sl_server_licenses 
                SET last_used = CURRENT_TIMESTAMP, usage_count = usage_count + 1
                WHERE license_key = ?
            ");
            $stmt->execute([$license_key]);
        }
        
        return $license;
        
    } catch (PDOException $e) {
        return false;
    }
}

// Gestione richieste API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            $server_id = $_POST['server_id'] ?? 0;
            if (!$server_id) {
                echo json_encode(['success' => false, 'error' => 'Server ID mancante']);
                break;
            }
            
            $result = createServerLicense($server_id);
            echo json_encode($result);
            break;
            
        case 'get':
            $server_id = $_POST['server_id'] ?? 0;
            if (!$server_id) {
                echo json_encode(['success' => false, 'error' => 'Server ID mancante']);
                break;
            }
            
            $license = getServerLicense($server_id);
            if ($license) {
                echo json_encode(['success' => true, 'license' => $license]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Licenza non trovata']);
            }
            break;
            
        case 'validate':
            $license_key = $_POST['license_key'] ?? '';
            if (!$license_key) {
                echo json_encode(['success' => false, 'error' => 'License key mancante']);
                break;
            }
            
            $license = validateLicense($license_key);
            if ($license) {
                echo json_encode(['success' => true, 'license' => $license]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Licenza non valida']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Azione non valida']);
    }
    exit;
}

// Interfaccia web per admin
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Licenze Server</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 120px; font-weight: bold; }
        input, select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .license-display { font-family: monospace; font-size: 18px; background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione Licenze Server</h1>
        
        <h2>Genera Nuova Licenza</h2>
        <form id="generateForm">
            <div class="form-group">
                <label>Server ID:</label>
                <input type="number" name="server_id" required>
                <button type="submit">Genera Licenza</button>
            </div>
        </form>
        
        <h2>Ottieni Licenza Esistente</h2>
        <form id="getForm">
            <div class="form-group">
                <label>Server ID:</label>
                <input type="number" name="server_id" required>
                <button type="submit">Ottieni Licenza</button>
            </div>
        </form>
        
        <h2>Valida Licenza</h2>
        <form id="validateForm">
            <div class="form-group">
                <label>License Key:</label>
                <input type="text" name="license_key" required style="width: 300px;">
                <button type="submit">Valida</button>
            </div>
        </form>
        
        <div id="result"></div>
    </div>

    <script>
        function handleForm(formId, action) {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', action);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('result');
                    if (data.success) {
                        let html = '<div class="result success">';
                        if (data.license_key) {
                            html += '<h3>Licenza Generata!</h3>';
                            html += '<p><strong>Server:</strong> ' + data.server_name + ' (ID: ' + data.server_id + ')</p>';
                            html += '<p><strong>License Key:</strong></p>';
                            html += '<div class="license-display">' + data.license_key + '</div>';
                        } else if (data.license) {
                            html += '<h3>Licenza Trovata</h3>';
                            html += '<p><strong>Server:</strong> ' + data.license.server_name + '</p>';
                            html += '<p><strong>License Key:</strong></p>';
                            html += '<div class="license-display">' + data.license.license_key + '</div>';
                            html += '<p><strong>Attiva:</strong> ' + (data.license.is_active ? 'Sì' : 'No') + '</p>';
                            html += '<p><strong>Utilizzi:</strong> ' + data.license.usage_count + '</p>';
                        }
                        html += '</div>';
                    } else {
                        html = '<div class="result error"><strong>Errore:</strong> ' + data.error + '</div>';
                    }
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result error"><strong>Errore:</strong> ' + error.message + '</div>';
                });
            });
        }
        
        handleForm('generateForm', 'generate');
        handleForm('getForm', 'get');
        handleForm('validateForm', 'validate');
    </script>
</body>
</html>