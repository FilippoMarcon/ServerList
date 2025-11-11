<?php
session_start();

$host = "phpmyadmin.namedhosting.com";
$dbname = "site_5783";
$username = "user_5783";
$password = "1B2SA7vuA4tg0RvIwRqJ5lZdTTzcXVi71jl9nlTPEyc";

$allowed_usernames = [
    "Auraft",
    "Ph1llyOn_",
    "God__Flame"
];

$username_modifications = [
    "Auraft" => "<span style='color: #ff0000;'>Founder ≈ Auraft</span>",
    "God__Flame" => "<span style='color: #ff0000;'>Founder ≈ God__Flame</span>",
    "Ph1llyOn_" => "<span style='color: #d13434;'>Admin ≈ Ph1llyOn_</span>",
    "IslandMC" => "<span style='color: #49adf4;'>IslandMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>",
    "WokMC" => "<span style='color: #49adf4;'>WokMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>"
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nickname'], $_POST['password'])) {
            $nickname = $_POST['nickname'];
            $pass = $_POST['password'];

            if (!in_array($nickname, $allowed_usernames)) {
                $error = "Accesso non autorizzato.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM nl2_users WHERE nickname = :nickname");
                $stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($pass, $user['password'])) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['nickname'] = $user['nickname'];
                    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
                    exit;
                } else {
                    $error = "Credenziali errate.";
                }
            }
        }

        ?>
        <!DOCTYPE html>
        <html lang="it">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login</title>
            <link rel="icon"
                href="https://cdn.discordapp.com/avatars/1398669926394363965/3684e0a7ac3a2e2e32cc05b3538999c8.webp?size=1024">
            <style>
                @font-face {
                    font-family: 'Minecraft';
                    src: url('https://www.fontsaddict.com/fontface/minecraft.eot');
                    src: url('https://www.fontsaddict.com/fontface/minecraft.woff') format('woff'), url('https://www.fontsaddict.com/fontface/minecraft.ttf') format('truetype');
                }

                body {
                    font-family: 'Arial', sans-serif;
                    background-color: #1A1A1E;
                    color: #FFFFFF;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px; /* Added padding for small screens */
                    box-sizing: border-box; /* Added box-sizing to include padding in width */
                }

                .login-box {
                    background-color: #121214;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
                    width: 100%; /* Make it responsive */
                    max-width: 400px; /* Limit the maximum width */
                    text-align: center;
                    border: 1px solid #121214;
                }

                input {
                    width: 100%; /* Full width in the login box */
                    padding: 12px;
                    margin: 12px 0;
                    border: 1px solid #1A1A1E;
                    border-radius: 6px;
                    font-size: 16px;
                    background-color: #1A1A1E;
                    color: #DCDDDE;
                    box-sizing: border-box; /* Added box-sizing to include padding in width */
                }

                input:focus {
                    outline: none;
                    border-color: #7289DA;
                }

                button {
                    width: 100%;
                    padding: 12px;
                    background-color: #7289DA;
                    border: none;
                    color: white;
                    font-size: 18px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                }

                button:hover {
                    background-color: #677bc4;
                }

                .error {
                    color: #f04747;
                    font-size: 14px;
                    margin-top: 10px;
                }

                .mc-avatar {
                    width: 120px;
                    height: 120px;
                    border-radius: 10px;
                    margin: 15px auto;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
                }

                .mc-nickname {
                    font-family: 'Minecraft', sans-serif;
                    font-size: 24px;
                    color: #FFD700;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                }

                .alert {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background-color: #f44336;
                    color: white;
                    padding: 10px;
                    border-radius: 5px;
                    display: none;
                }

                @media (max-width: 600px) {
                    .login-box {
                        padding: 20px;
                    }

                    .mc-avatar {
                        width: 100px;
                        height: 100px;
                    }

                    .mc-nickname {
                        font-size: 20px;
                    }
                }
            </style>
            <script>
                function updateAvatar() {
                    let username = document.getElementById("nickname").value;
                    let avatar = document.getElementById("mc-avatar");
                    let displayNick = document.getElementById("mc-nickname");
                    let modifiedUsername = username;

                    <?php
                    foreach ($username_modifications as $plain => $modified) {
                        echo "if (username === '$plain') {
                            modifiedUsername = '" . str_replace("'", "\\'", $modified) . "';
                        }\n";
                    }
                    ?>

                    if (username.trim() !== "") {
                        avatar.src = "https://mc-heads.net/avatar/" + username;
                        displayNick.innerHTML = modifiedUsername;
                    } else {
                        avatar.src = "https://mc-heads.net/avatar/MHF_Steve";
                        displayNick.textContent = "Username";
                    }
                }

                function checkUsername() {
                    let username = document.getElementById("nickname").value;
                    let alertDiv = document.getElementById("alert");
                    let allowedUsernames = <?php echo json_encode($allowed_usernames); ?>;

                    let found = false;
                    for (let i = 0; i < allowedUsernames.length; i++) {
                        if (username.toLowerCase() === allowedUsernames[i].toLowerCase()) {
                            found = true;
                            if (username !== allowedUsernames[i]) {
                                alertDiv.innerHTML = `
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" />
                                        </svg>
                                        <div>
                                            <strong style="font-size: 16px;">Attenzione!</strong><br>
                                            Devi scrivere il nome utente esattamente come:<br>
                                            <span style="font-family: 'Minecraft', sans-serif; color: #FFD700;">${allowedUsernames[i]}</span>
                                        </div>
                                    </div>
                                `;
                                alertDiv.style.display = "block";
                                alertDiv.style.background = "#242429";
                                alertDiv.style.borderLeft = "4px solid #FFD700";
                                alertDiv.style.padding = "15px";
                                alertDiv.style.color = "#FFFFFF";
                                return;
                            }
                        }
                    }

                    if (!found && username.trim() !== "") {
                        alertDiv.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" />
                                </svg>
                                <div>
                                    <strong style="font-size: 16px;">Attenzione!</strong><br>
                                    Questo username non è autorizzato.
                                </div>
                            </div>
                        `;
                        alertDiv.style.display = "block";
                        alertDiv.style.background = "#242429";
                        alertDiv.style.borderLeft = "4px solid #FF0000";
                        alertDiv.style.padding = "15px";
                        alertDiv.style.color = "#FFFFFF";
                        return;
                    }

                    alertDiv.style.display = "none";
                }
            </script>
        </head>

        <body>
            <div class="alert" id="alert"></div>
            <div class="login-box">
                <h2>Stai accedendo con</h2>
                <img id="mc-avatar" class="mc-avatar" src="https://mc-heads.net/avatar/MHF_Steve"
                    alt="Avatar Minecraft">
                <p id="mc-nickname" class="mc-nickname">Username</p>
                <?php if (isset($error))
                    echo "<p class='error'>$error</p>"; ?>
                <form method="post">
                    <input type="text" id="nickname" name="nickname" placeholder="Username" required
                        onkeyup="updateAvatar(); checkUsername();">
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Accedi</button>
                </form>
            </div>
        </body>

        </html>
        <?php
        exit;
    }
    ?>
    <?php
    if (!isset($_GET['id'])) {
        die("Errore: ID del ticket non fornito.");
    }

    $ticket_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = :id");
    $stmt->bindParam(':id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Errore: Ticket non trovato.");
    }

    $messages = explode("\n", $ticket['transcript']);
} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}
?>
<?php
function makeClickableLinks($text)
{
    $text = preg_replace(
        '/(?<!\]\()https?:\/\/[^\s]+/',
        '<a href="$0" target="_blank" style="text-decoration: underline;">$0</a>',
        $text
    );

    // Aggiunta per il link al Forum
    $text = preg_replace(
        '/\[(.*?)\]\((https?:\/\/[^\s]+)\)/',
        '<a href="$2" target="_blank" style="text-decoration: underline;">$1</a>',
        $text
    );

    // Aggiunta per trasformare "- " in "• " per le liste
    $text = preg_replace('/^-\s+(.*)$/m', '• $1', $text);

    // Aggiunta per il grassetto
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    $replacements = [
        '&lt;@608692450730442795&gt;' => '<a href="https://www.discordapp.com/users/608692450730442795" target="_blank">@Auraft</a>',
        '&lt;@1252232142000357452&gt;' => '<a href="https://www.discordapp.com/users/1252232142000357452" target="_blank">@BubuRoyal</a>',
        '&lt;@597856359672971264&gt;' => '<a href="https://www.discordapp.com/users/597856359672971264" target="_blank">@Ph1llyOn_</a>',
        '&lt;@1377828272854925424&gt;' => '<a href="https://www.discordapp.com/users/1377828272854925424" target="_blank">@God__Flame</a>',
        '&lt;@690592577036222545&gt;' => '<a href="https://www.discordapp.com/users/690592577036222545" target="_blank">@Koi_II_</a>',
        '&lt;@1355256786403459346&gt;' => '<a href="https://www.discordapp.com/users/1355256786403459346" target="_blank">@FELPA_AZZURRA</a>',
        '&lt;@804368092930637824&gt;' => '<a href="https://www.discordapp.com/users/804368092930637824" target="_blank">@Xx_Th3Creep_xX</a>',
        '&lt;@719651974739394571&gt;' => '<a href="https://www.discordapp.com/users/719651974739394571" target="_blank">@oFound</a>',
        '&lt;@870260934998913035&gt;' => '<a href="https://www.discordapp.com/users/870260934998913035" target="_blank">@AccaDD</a>',
        '&lt;@358298329446350848&gt;' => '<a href="https://www.discordapp.com/users/358298329446350848" target="_blank">@HumenRose</a>',
    ];

    foreach ($replacements as $search => $replace) {
        $text = str_replace($search, $replace, $text);
    }

    return $text;
}

$ticketTypeColors = [
    '🟢 Minecraft' => '#2ecc70',
    '❓ Altro' => '#e74d3c',
    '🧪 Candidatura Betatester' => '#3498db',
    '🛒 Store' => '#ffa600',
    '📸 Media' => '#9c59b6',
];

$ticketType = strtolower($ticket['ticket_type']);
$embedColor = isset($ticketTypeColors[$ticket['ticket_type']]) ? $ticketTypeColors[$ticket['ticket_type']] : '#7289DA';

$currentTicketId = intval($_GET['id']);

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$showImportant = isset($_GET['important']) && $_GET['important'] == '1';

$prevStmt = $pdo->prepare("SELECT id FROM tickets WHERE id < :current_id ORDER BY id DESC LIMIT 1");
$prevStmt->bindParam(':current_id', $currentTicketId, PDO::PARAM_INT);
$prevStmt->execute();
$prevTicket = $prevStmt->fetch(PDO::FETCH_ASSOC);
$prevTicketId = ($prevTicket) ? $prevTicket['id'] : null;

$nextStmt = $pdo->prepare("SELECT id FROM tickets WHERE id > :current_id ORDER BY id ASC LIMIT 1");
$nextStmt->bindParam(':current_id', $currentTicketId, PDO::PARAM_INT);
$nextStmt->execute();
$nextTicket = $nextStmt->fetch(PDO::FETCH_ASSOC);
$nextTicketId = ($nextTicket) ? $nextTicket['id'] : null;

$importantTickets = [];
if ($showImportant) {
    $importantStmt = $pdo->prepare("SELECT id, closed_at FROM tickets WHERE is_important = 1 ORDER BY closed_at DESC");
    $importantStmt->execute();
    $importantTickets = $importantStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcript Ticket #
        <?php echo $ticket['id']; ?>
    </title>
    <link rel="icon"
        href="https://cdn.discordapp.com/avatars/1398669926394363965/3684e0a7ac3a2e2e32cc05b3538999c8.webp?size=1024">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #23272A;
            color: #FFFFFF;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            height: 100%;
            /* fallback for browsers that don't support calc() */
            height: calc(100vh);
        }

        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            background-color: #1A1A1E;
            padding: 30px;
            box-sizing: border-box;
        }

        h2 {
            color: #7289DA;
            font-size: 28px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            text-align: center;
            /* Centra il titolo */
        }

        p {
            color: #B9BBBE;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .chat {
            width: 100%;
            max-width: 900px;
            margin-top: 20px;
            background-color: #121214;
            padding: 20px;
            border-radius: 8px;
            overflow-y: auto;
            max-height: 75vh;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .message {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border-bottom: 1px solid #1A1A1E;
            margin-bottom: 10px;
            background-color: #1A1A1E;
            border-radius: 5px;
            transition: transform 0.2s ease-in-out;
        }

        .message:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .chat .message:last-child {
            margin-bottom: 0;
        }

        .avatar img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .message-content {
            max-width: 75%;
        }

        .username {
            font-weight: bold;
            color: #00e7ff;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.4);
        }

        .timestamp {
            font-size: 12px;
            color: #B9BBBE;
            margin-left: 10px;
        }

        .content {
            color: #DCDDDE;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
            margin-top: 5px;
        }

        a {
            text-decoration: none;
            transition: color 0.2s ease;
            background-color: #383756;
            color: #a0b1f3;
            padding: 3px;
            border-radius: 5px;
        }

        a:hover {
            text-decoration: underline;
            color: #4dbfff;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1A1A1E;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #7289DA;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #5B6E9A;
        }

        .discord-embed {
            background-color: #242429;
            border-left: 4px solid
                <?php echo $embedColor; ?>
            ;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .discord-embed-title {
            color: #FFFFFF;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .discord-embed-description {
            color: #DCDDDE;
            font-size: 14px;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 900px;
            margin-bottom: 20px;
        }

        .navigation a {
            color: #7289DA;
            text-decoration: none;
            font-size: 20px;
            transition: color 0.2s ease;
        }

        .navigation a:hover {
            color: #4dbfff;
        }

        .search-container {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            flex-direction: row;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: #242429;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #121214;
            width: 60%;
            margin-right: 10px;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            border: none;
            background-color: transparent;
            color: #DCDDDE;
            flex-grow: 1;
            font-size: 16px;
            outline: none;
        }

        .search-bar button {
            background-color: #7289DA;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        .search-bar button:hover {
            background-color: #677bc4;
        }

        .search-results {
            width: 38%;
            background-color: #242429;
            border-radius: 5px;
            padding: 10px;
            color: #DCDDDE;
            box-sizing: border-box;
            margin-top: 0;
        }

        .search-results ul {
            list-style: none;
            padding: 0;
        }

        .search-results li {
            margin-bottom: 5px;
        }

        .search-results li a {
            color: #7289DA;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            background-color: #121214;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .search-results li a:hover {
            background-color: #4F545C;
        }

        .important-tickets {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            background-color: #242429;
            border-radius: 5px;
            padding: 10px;
            color: #DCDDDE;
            box-sizing: border-box;
        }

        .important-tickets h3 {
            color: #7289DA;
            margin-bottom: 10px;
        }

        .important-tickets ul {
            list-style: none;
            padding: 0;
        }

        .important-tickets li {
            margin-bottom: 5px;
        }

        .important-tickets li a {
            color: #7289DA;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            background-color: #121214;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .important-tickets li a:hover {
            background-color: #4F545C;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            h2 {
                font-size: 24px;
            }

            .navigation {
                flex-direction: column;
                align-items: center;
            }

            .navigation a {
                margin-bottom: 10px;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar {
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }

            .search-results {
                width: 100%;
            }

            .chat {
                padding: 10px;
            }

            .message-content {
                max-width: 100%;
            }
        }

        /* Stili aggiuntivi per un design più professionale */
        .container::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(44, 47, 51, 0.9), rgba(35, 39, 42, 0.9));
            /* Sfumatura di sfondo */
            z-index: -1;
            /* Messo dietro al contenuto */
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 900px;
            margin-bottom: 20px;
            padding: 10px 0;
            /* Aggiunto padding per respiro */
            border-bottom: 1px solid #121214;
            /* Linea di separazione */
        }

        .navigation a {
            color: #7289DA;
            text-decoration: none;
            font-size: 18px;
            /* Ridotto leggermente la dimensione del font */
            transition: color 0.2s ease;
            padding: 8px 16px;
            /* Aggiunto padding per i link */
            border-radius: 5px;
            /* Bordi arrotondati */
            background-color: #242429;
            /* Sfondo leggermente più scuro */
        }

        .navigation a:hover {
            color: #4dbfff;
            background-color: #4F545C;
            /* Sfondo più scuro all'hover */
        }

        .search-container {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            flex-direction: row;
            gap: 10px;
            /* Spazio tra gli elementi */
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: #242429;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #121214;
            width: 60%;
            margin-right: 0;
            /* Rimosso margin-right */
        }

        .search-bar input[type="text"] {
            padding: 10px;
            border: none;
            background-color: transparent;
            color: #DCDDDE;
            flex-grow: 1;
            font-size: 16px;
            outline: none;
        }

        .search-bar button {
            background-color: #7289DA;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        .search-bar button:hover {
            background-color: #677bc4;
        }

        .search-results {
            width: 38%;
            background-color: #242429;
            border-radius: 5px;
            padding: 10px;
            color: #DCDDDE;
            box-sizing: border-box;
            margin-top: 0;
        }

        .search-results h3 {
            font-size: 18px;
            /* Dimensione del font per l'header dei risultati */
            margin-bottom: 10px;
            /* Spazio sotto l'header */
        }

        .search-results ul {
            list-style: none;
            padding: 0;
        }

        .search-results li {
            margin-bottom: 5px;
        }

        .search-results li a {
            color: #7289DA;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            background-color: #121214;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .search-results li a:hover {
            background-color: #4F545C;
        }

        .important-tickets {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            background-color: #242429;
            border-radius: 5px;
            padding: 10px;
            color: #DCDDDE;
            box-sizing: border-box;
        }

        .important-tickets h3 {
            color: #7289DA;
            margin-bottom: 10px;
        }

        .important-tickets ul {
            list-style: none;
            padding: 0;
        }

        .important-tickets li {
            margin-bottom: 5px;
        }

        .important-tickets li a {
            color: #7289DA;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            background-color: #121214;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .important-tickets li a:hover {
            background-color: #4F545C;
        }

        /* Aggiunto stile per le informazioni del ticket */
        .ticket-info {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            padding: 15px;
            background-color: #242429;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .ticket-info p {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .ticket-info strong {
            color: #7289DA;
            /* Colore per le etichette */
        }

        /* Stile per il footer */
        footer {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            padding: 15px;
            text-align: center;
            color: #B9BBBE;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="navigation">
            <?php if ($prevTicketId): ?>
                <a href="?id=<?php echo $prevTicketId;
                if (!empty($searchTerm))
                    echo '&search=' . urlencode($searchTerm);
                if ($showImportant)
                    echo '&important=1'; ?>"><i class="fas fa-arrow-left"></i> Transcript #
                    <?php echo $prevTicketId; ?>
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <?php if ($nextTicketId): ?>
                <a href="?id=<?php echo $nextTicketId;
                if (!empty($searchTerm))
                    echo '&search=' . urlencode($searchTerm);
                if ($showImportant)
                    echo '&important=1'; ?>">Transcript #
                    <?php echo $nextTicketId; ?> <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </div>

        <h2>Transcript del Ticket #
            <?php echo $ticket['id']; ?>
        </h2>

        <form method="get" class="filter-form">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
            <div class="filter-toggle">
                <label class="switch">
                    <input type="checkbox" name="important" value="1" <?php echo $showImportant ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <span class="slider round"></span>
                </label>
                <span class="filter-label">Mostra solo ticket importanti</span>
            </div>
            <?php if (!empty($searchTerm)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <?php endif; ?>
        </form>

        <style>
            .filter-form {
                margin: 15px 0;
                padding: 10px;
                background: #1A1A1E;
                border-radius: 8px;
            }
            
            .filter-toggle {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .filter-label {
                color: #fff;
                font-size: 14px;
            }
            
            .switch {
                position: relative;
                display: inline-block;
                width: 40px;
                height: 20px;
            }
            
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            
            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 2px;
                bottom: 2px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            input:checked + .slider {
                background-color: #7289da;
            }
            
            input:checked + .slider:before {
                transform: translateX(20px);
            }
            
            .slider.round {
                border-radius: 34px;
            }
            
            .slider.round:before {
                border-radius: 50%;
            }
        </style>

        <?php
        $searchResults = [];

        if (!empty($searchTerm)) {
            $search_term_like = '%' . strtolower($searchTerm) . '%';

            $searchStmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id LIKE :search_term OR LOWER(transcript) LIKE :search_term2");
            $searchStmt->bindParam(':search_term', $search_term_like, PDO::PARAM_STR);
            $searchStmt->bindParam(':search_term2', $search_term_like, PDO::PARAM_STR);
            $searchStmt->execute();

            $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);

            $searchResults = array_filter($searchResults, function ($result) use ($pdo, $searchTerm) {
                $ticket_id = $result['id'];
                $stmt = $pdo->prepare("SELECT transcript FROM tickets WHERE id = :id");
                $stmt->bindParam(':id', $ticket_id, PDO::PARAM_INT);
                $stmt->execute();
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ticket) {
                    $transcript = $ticket['transcript'];
                    $messages = explode("\n", $transcript);
                    foreach ($messages as $msg) {
                        $parts = explode(": ", $msg, 2);
                        if (count($parts) >= 2) {
                            $username = trim($parts[0]);
                            if (stripos($username, $searchTerm) !== false) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            });

            $searchResults = array_values($searchResults);
        }
        ?>

        <div class="search-container">
            <div class="search-bar">
                <form method="get" style="display: flex; align-items: center; width: 100%;">
                    <input type="text" name="search" placeholder="Cerca per Username..."
                        value="<?php echo htmlspecialchars($searchTerm); ?>" style="border-radius: 0; flex-grow: 1;">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
                    <?php if ($showImportant): ?>
                        <input type="hidden" name="important" value="1">
                    <?php endif; ?>
                    <button type="submit" style="border-radius: 0;">Cerca</button>
                </form>
            </div>

            <?php if (!empty($searchResults)): ?>
                <div class="search-results">
                    <h3>Risultati ricerca per "<?php echo htmlspecialchars($searchTerm); ?>":</h3>
                    <?php if (count($searchResults) > 0): ?>
                        <ul>
                            <?php foreach ($searchResults as $result): ?>
                                <li><a href="?id=<?php echo $result['id'];
                                if (!empty($searchTerm))
                                    echo '&search=' . urlencode($searchTerm);
                                if ($showImportant)
                                    echo '&important=1'; ?>">Ticket #<?php echo $result['id']; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nessun ticket trovato per questo User ID o Username.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <p><strong>User ID:</strong> <a href="https://www.discordapp.com/users/<?php echo $ticket['user_id']; ?>"
                target="_blank" style="color: #7289DA;"><?php echo $ticket['user_id']; ?></a></p>
        <p><strong>Data Creazione:</strong> <?php echo $ticket['created_at']; ?></p>
        <p><strong>Tipologia Ticket:</strong> <?php echo htmlspecialchars($ticket['ticket_type']); ?></p>

        <?php if ($showImportant): ?>
            <div class="important-tickets">
                <h3>Ticket Importanti</h3>
                <?php if (count($importantTickets) > 0): ?>
                    <ul>
                        <?php foreach ($importantTickets as $result): ?>
                            <li>
                                <a href="?id=<?php echo $result['id'];
                                if (!empty($searchTerm))
                                    echo '&search=' . urlencode($searchTerm);
                                if ($showImportant)
                                    echo '&important=1'; ?>">
                                    Ticket #<?php echo $result['id']; ?>
                                </a>
                                - Chiuso il: <?php echo $result['closed_at']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Nessun ticket importante trovato.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="chat">
            <?php
            $firstMessage = true;
            $lastUsername = "";
            $lastAvatarUrl = "";
            $outputBuffer = "";

            foreach ($messages as $msg):
                $parts = explode(": ", $msg, 2);
                if (count($parts) < 2) {
                    $username = $lastUsername;
                    $message = $parts[0];
                } else {
                    $username = $parts[0];
                    $message = $parts[1];
                }

                if ($username == "auraft1") {
                    $username = "<span style='color: #ff0000;'>Founder ≈ Auraft</span>";

                } elseif ($username == "god__flame") {
                    $username = "<span style='color: #ff0000;'>Founder ≈ God__Flame</span>";

                } elseif ($username == "ph1llyon") {
                    $username = "<span style='color: #d13434;'>Admin ≈ Ph1llyOn_</span>";

                } elseif ($username == "IslandMC") {
                    $username = "<span style='color: #49adf4;'>IslandMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>";

                } elseif ($username == "WokMC") {
                    $username = "<span style='color: #49adf4;'>WokMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>";

                }

                $userId = $ticket['user_id'];

                if ($username == "<span style='color: #ff0000;'>Founder ≈ Auraft</span>") {
                    $avatarUrl = "https://cdn.discordapp.com/avatars/608692450730442795/3ac3f23f7034860ce98a230e32526eb8.webp?size=1024";
                    
                } elseif ($username == "<span style='color: #ff0000;'>Founder ≈ God__Flame</span>") {
                    $avatarUrl = "https://cdn.discordapp.com/avatars/1377828272854925424/8f31814f2da07b17ffab18f551fc1913.webp?size=1024";

                } elseif ($username == "<span style='color: #d13434;'>Admin ≈ Ph1llyOn_</span>") {
                    $avatarUrl = "https://cdn.discordapp.com/avatars/597856359672971264/670967cd1115e0c993e1743536e9d3a1.webp?size=1024";

                } elseif ($username == "<span style='color: #49adf4;'>IslandMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>") {
                    $avatarUrl = "https://cdn.discordapp.com/avatars/1231880449890979890/513251066e73d0525c4036c73b25f363.webp?size=1024";

                } elseif ($username == "<span style='color: #49adf4;'>WokMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>") {
                    $avatarUrl = "https://cdn.discordapp.com/avatars/1398669926394363965/3684e0a7ac3a2e2e32cc05b3538999c8.webp?size=1024";
                    
                } else {
                    $avatarUrl = "https://cdn.discordapp.com/embed/avatars/0.png";
                }

                if (
                    $firstMessage && (
                        $username == "<span style='color: #49adf4;'>IslandMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>" ||
                        $username == "<span style='color: #49adf4;'>WokMC</span> <span style='background-color: #5865f2; padding: 3px; border-radius: 5px; color: white; font-size: 10px;'>APP</span>"
                    )
                ) {
                    $firstMessage = false;
                    $embed_user = '';
                    $i = 1;
                    while (isset($messages[$i])) {
                        $next_parts = explode(": ", $messages[$i], 2);
                        $next_username = $next_parts[0];

                        $is_staff = false;
                        $is_username_modified = false;
                        foreach ($username_modifications as $plain => $modified) {
                            if (strtolower($next_username) == strtolower($plain)) {
                                $is_staff = true;
                                $is_username_modified = true;
                                break;
                            }
                        }

                        // Check if the username is not modified (not a staff member)
                        if (!$is_staff) {
                            $embed_user = $next_username;
                            break;
                        }
                        $i++;
                    }

                    if ($embed_user == '') {
                        $embed_user = 'Sconosciuto';
                    }
                    ?>
                    <div class="discord-embed">
                        <div class="discord-embed-title">Ticket Aperto - <?php echo htmlspecialchars($ticket['ticket_type']); ?>
                        </div>
                        <div class="discord-embed-description">
                            Grazie per aver aperto il ticket.<br />
                            Descrivi il tuo problema e attendi una risposta.<br />
                            <br />
                            Utente: <a href="https://www.discordapp.com/users/<?php echo $ticket['user_id']; ?>" target="_blank"
                                style="color: #7289DA;">@<?php echo $embed_user; ?></a><br />
                            <br />
                            Non taggare lo Staff!
                        </div>
                    </div>
                    <?php
                    continue;
                }

                $messageContent = "<p class='content'>" . nl2br(makeClickableLinks(htmlspecialchars($message))) . "</p>";
                

                if ($username == $lastUsername) {
                    $outputBuffer .= $messageContent;
                } else {
                    if ($outputBuffer !== "") {
                        echo '<div class="message">
                            <div class="avatar">
                                <img src="' . $lastAvatarUrl . '" alt="Avatar">
                            </div>
                            <div class="message-content">
                                <span class="username">' . $lastUsername . '</span>
                                ' . $outputBuffer . '
                            </div>
                        </div>';
                    }

                    $outputBuffer = $messageContent;
                    $lastUsername = $username;
                     $lastAvatarUrl = $avatarUrl;
                }

            endforeach;

            if ($outputBuffer !== "") {
                echo '<div class="message">
                    <div class="avatar">
                        <img src="' . $lastAvatarUrl . '" alt="Avatar">
                    </div>
                    <div class="message-content">
                        <span class="username">' . $lastUsername . '</span>
                        ' . $outputBuffer . '
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>
</body>

</html>
<?php
