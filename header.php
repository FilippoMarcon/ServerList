<?php
/**
 * Header template per tutte le pagine
 * Header template for all pages
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <?php
        // Meta SEO dinamici
        $page_description = isset($page_description) ? $page_description : (defined('SITE_NAME') ? ('Scopri i migliori server Minecraft su ' . SITE_NAME) : 'Scopri i migliori server Minecraft');
        $base_url = (defined('SITE_URL') ? SITE_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']));
        $page_url = $base_url . ($_SERVER['REQUEST_URI'] ?? '/');
        $og_image = isset($og_image) ? $og_image : ($base_url . '/logo.png');
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($page_url); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <script>
    (function() {
        try {
            var saved = localStorage.getItem('theme');
            var theme = saved || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            window.toggleTheme = function() {
                var current = document.documentElement.getAttribute('data-theme') || 'dark';
                var next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                try { localStorage.setItem('theme', next); } catch(e) {}
                var icon = document.getElementById('themeToggleIcon');
                if (icon) {
                    icon.className = next === 'dark' ? 'bi bi-moon-stars' : 'bi bi-brightness-high';
                }
            };
        } catch (e) {}
    })();
    </script>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo htmlspecialchars(isset($page_favicon) ? $page_favicon : 'logo.png'); ?>" type="image/png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Mobile-only CSS -->
    <link rel="stylesheet" href="mobile.css" media="(max-width: 768px)">
    <!-- Custom CSS -->

    <style>
        :root {
            --primary-bg: #0f0f23;
            --secondary-bg: #1a1a2e;
            --card-bg: #16213e;
            --accent-purple: #7c3aed;
            --accent-cyan: #06b6d4;
            --accent-pink: #ec4899;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #334155;
            --hover-bg: #1e293b;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        /* Tema chiaro: override variabili */
        [data-theme="light"] {
            --primary-bg: #f8fafc;
            --secondary-bg: #ffffff;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --hover-bg: #f1f5f9;
        }

        /* Fix visibilità in light mode */
        [data-theme="light"] .server-stats,
        [data-theme="light"] .server-stats span,
        [data-theme="light"] .server-stats .stat-chip {
            color: var(--text-primary) !important;
        }

        [data-theme="light"] .thread-link {
            color: var(--text-primary) !important;
        }

        [data-theme="light"] .thread-link:hover {
            color: var(--accent-purple) !important;
        }
        
        body {
            background: var(--primary-bg);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }
        
        /* Navbar */
        .navbar-mc {
            background: var(--secondary-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1000;
        }
        
        .navbar-brand {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        
        .nav-link {
            color: var(--text-secondary) !important;
            padding: 0.75rem 1.25rem !important;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .nav-link:hover::before, .nav-link.active::before {
            opacity: 1;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Profile dropdown button */
        .navbar .dropdown-toggle {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .navbar .dropdown-toggle:hover {
            background: var(--hover-bg);
            border-color: var(--accent-purple);
            transform: translateY(-2px);
        }
        
        .navbar .dropdown-toggle img {
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .navbar .dropdown-toggle:hover img {
            border-color: var(--accent-purple);
        }

        /* Dropdown menu styled like the navbar */
        .navbar-mc .dropdown-menu {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            padding: 0.5rem;
            color: var(--text-primary);
        }

        .navbar-mc .dropdown-item {
            color: var(--text-secondary);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }

        .navbar-mc .dropdown-item:hover,
        .navbar-mc .dropdown-item:focus {
            background: var(--hover-bg);
            color: var(--text-primary);
        }

        .navbar-mc .dropdown-divider {
            border-color: var(--border-color);
            opacity: 0.6;
        }

        .navbar-mc .dropdown-header {
            color: var(--text-secondary);
        }
        
        /* Main container */
        .container {
            max-width: 1400px;
        }
        

        
        /* Server list header */
        .server-list-header {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: none;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        
        .server-list-header h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .search-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-left: 2rem;
        }
        
        .search-input {
            background: var(--primary-bg);
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            width: 280px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .search-input::placeholder {
            color: var(--text-muted);
        }
        
        .filters-btn {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .filters-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Server cards */
        .server-list {
            border-radius: 0 0 16px 16px;
            /* Permette ai dropdown di sovrapporsi correttamente */
            overflow: visible;
            backdrop-filter: blur(20px);
        }
        
        .server-card {
            background: transparent;
            border: none;
            border-bottom: 1px solid var(--border-color);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .server-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.6s ease;
        }
        
        .server-card:hover::before {
            left: 100%;
        }
        
        .server-card:hover {
            background: var(--hover-bg);
            transform: translateX(8px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .server-card:last-child {
            border-bottom: none;
        }
        
        .server-rank {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            min-width: 80px;
            text-align: center;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .server-rank.gold {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a1a2e;
        }
        
        .server-rank.silver {
            background: linear-gradient(135deg, #c0c0c0 0%, #e5e5e5 100%);
            color: #1a1a2e;
        }
        
        .server-rank.bronze {
            background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
            color: white;
        }
        
        /* Server rank container */
        .server-rank-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Rank number above the badge */
        .rank-number-top {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-primary);
            text-align: center;
            line-height: 1;
        }
        
        /* Adjust server-rank for consistent sizing */
        .server-rank {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-width: 80px;
            min-height: 40px;
        }

        .server-rank.outlined {
            background: transparent !important;
            border: 2px solid #f093fb !important;
            background-origin: border-box !important;
            background-clip: content-box, border-box !important;
            color: var(--text-primary) !important;
            box-shadow: none !important;
            border-radius: 12px !important;
        }
        
        .server-rank .bi-trophy-fill {
            margin-right: 6px !important;
        }

        /* Server Edit Form Styles */
        .server-edit-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-row > * {
            min-width: 0;
            max-width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            max-width: 100%;
            width: 100%;
        }

        .form-group label {
            color: #fff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 12px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Stili per la selezione modalità */
        .modalita-selection {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        .modalita-checkbox {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin: 0;
        }

        .modalita-checkbox input[type="checkbox"] {
            display: none;
        }

        .modalita-tag {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
            color: #fff;
            transition: all 0.3s ease;
            user-select: none;
        }

        .modalita-checkbox input[type="checkbox"]:checked + .modalita-tag {
            background: #4CAF50;
            border-color: #4CAF50;
            color: #fff;
        }

        .modalita-tag:hover {
            background: rgba(76, 175, 80, 0.3);
            border-color: #4CAF50;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }

        .btn-admin-edit {
            background: linear-gradient(135deg, #ff9800, #f57c00) !important;
            color: white !important;
        }

        .btn-admin-edit:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00) !important;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }

        /* Sponsored Servers Styles */
        .sponsored-servers-section {
            margin-bottom: 2rem;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.05));
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }



        .sponsored-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .sponsored-header h3 {
            color: #ffffff;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        .sponsored-header .bi-star-fill {
            color: #FFD700;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .sponsored-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .sponsored-servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .sponsored-server-card {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 215, 0, 0.2);
            border-radius: 12px;
            padding: 12px;
            position: relative;
            overflow: hidden;
            max-height: 140px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .sponsored-server-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.15), transparent);
            pointer-events: none;
            animation: sponsorShine 3s ease-in-out infinite;
        }

        @keyframes sponsorShine {
            0% {
                left: -100%;
            }
            50% {
                left: 100%;
            }
            100% {
                left: -100%;
            }
        }

        .sponsored-overlay {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 2;
        }

        .sponsored-server-card .server-logo {
            width: 48px;
            height: 48px;
            margin-bottom: 0;
            border: 2px solid rgba(255, 215, 0, 0.3);
            flex-shrink: 0;
        }

        .sponsored-server-card .server-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 48px;
        }

        .sponsored-server-card .server-info h4 {
            color: #FFD700;
            font-weight: 700;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .sponsored-server-card .server-ip {
            color: rgba(255, 255, 255, 0.8);
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
            font-size: 11px;
            width: fit-content;
        }



        .sponsored-server-card .server-stats {
            display: flex;
            gap: 8px;
            margin-bottom: 0;
            flex-wrap: wrap;
        }

        .sponsored-server-card .server-stats span {
            color: rgba(255, 255, 255, 0.8);
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .sponsored-server-card .votes-count .bi-heart-fill {
            color: #ff4757;
        }

        .sponsored-server-card .server-version .bi-gear {
            color: #70a1ff;
        }



        .sponsored-cta {
            text-align: center;
            margin-top: 16px;
        }

        .sponsored-cta-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .sponsored-cta-link {
            font-size: 0.8rem;
            color: #FFD700;
            text-decoration: none;
        }

        .sponsored-cta-link:hover {
            color: #ffcc33;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .sponsored-servers-grid {
                grid-template-columns: 1fr;
            }
            
            .sponsored-header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            
            /* Mobile: riorganizza ordine elementi sponsor */
            .sponsored-server-card .server-info {
                display: flex;
                flex-direction: column;
            }
            
            .sponsored-server-card .server-info h4 {
                order: 1;
                margin-bottom: 0.5rem;
            }
            
            .sponsored-server-card .server-info .server-ip {
                order: 2;
                margin-bottom: 0.5rem;
            }
            
            .sponsored-server-card .server-info .server-stats {
                order: 3;
                flex-direction: column;
                gap: 4px;
                align-items: flex-start;
            }
            
            .sponsored-server-card .server-stats .server-version {
                order: 1;
            }
            
            .sponsored-server-card .server-stats .votes-count {
                order: 2;
            }
        }
        
        .server-logo {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: block;
        }
        
        .server-card:hover .server-logo {
            transform: scale(1.05);
            border-color: var(--accent-purple);
        }
        
        .server-info {
            flex: 1;
        }
        
        .server-name {
            color: var(--text-primary);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
        }
        
        .sponsored-indicator {
            display: inline-block;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
            vertical-align: middle;
            box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
            animation: sparkle 2s infinite;
        }
        
        .sponsored-indicator .bi-star-fill {
            margin-right: 3px;
            font-size: 0.6rem;
        }
        
        @keyframes sparkle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .server-name:hover {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Evita che indicatori e versioni spariscano durante l'hover del nome */
        .server-name:hover .sponsored-indicator,
        .server-name:hover .sponsored-indicator * {
            -webkit-text-fill-color: initial !important;
            color: #000 !important;
            background-clip: border-box;
            -webkit-background-clip: border-box;
            opacity: 1 !important;
        }

        .server-name:hover span {
            -webkit-text-fill-color: initial !important;
            color: var(--text-muted) !important;
            opacity: 1 !important;
        }
        
        .server-ip {
            background: var(--gradient-accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .server-ip:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }
        
        .server-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .server-tag {
            background: var(--primary-bg);
            color: var(--text-secondary);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 1px solid var(--border-color);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .server-tag:hover {
            background: var(--accent-purple);
            color: white;
            border-color: var(--accent-purple);
        }

        .server-tag-more {
            background: var(--accent-purple);
            color: white;
            border-color: var(--accent-purple);
            font-weight: 700;
            cursor: help;
        }

        .server-tag-more:hover {
            background: var(--accent-pink);
            border-color: var(--accent-pink);
        }
        
        .server-players {
            text-align: right;
            color: var(--text-primary);
            min-width: 120px;
        }
        
        .player-count {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .player-status {
            color: var(--accent-green);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Sidebar filters */
        .filters-sidebar {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
        }
        
        .filters-sidebar h4 {
            color: var(--text-primary);
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .filter-group {
            margin-bottom: 2rem;
        }
        
        .filter-group h5 {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .filter-tag {
            background: var(--primary-bg);
            color: var(--text-secondary);
            padding: 0.6rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
            width: 47%;
        }
        
        .filter-tag::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .filter-tag:hover::before, .filter-tag.active::before {
            opacity: 1;
        }
        
        .filter-tag:hover, .filter-tag.active {
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .clear-filters {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        .clear-filters:hover {
            background: var(--gradient-secondary);
            color: white;
            border-color: transparent;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
        }
        
        /* Filters header alignment */
        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .filters-header h4 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                max-width: 100%;
                padding: 0 2rem;
            }
        }
        
        @media (max-width: 992px) {
            .server-card {
                padding: 1.5rem;
                gap: 1rem;
            }
            
            .server-logo {
                width: 64px;
                height: 64px;
            }
            
            .filters-sidebar {
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            
            .server-list {
                margin: 0;
                padding: 0;
                border-radius: 0 0 16px 16px;
                overflow-x: hidden;
                max-width: 100%;
            }
            
            .server-card {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
                padding: 2rem 1rem;
                margin: 0;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .server-info {
                text-align: center;
            }
            
            .server-players {
                text-align: center;
            }
            
            .search-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-input {
                width: 100%;
            }
            
            .server-list-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .social-links {
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 0 1rem;
            }
            
            .server-card {
                padding: 1.5rem 1rem;
            }
            
            .server-name {
                font-size: 1.2rem;
            }
            
            .filter-tags {
                gap: 0.5rem;
            }
            
            .filter-tag {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
        
        /* Dropdown menus */
        .dropdown-menu {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 999999 !important;
            padding: 0.5rem;
            margin-top: 0.5rem;
            backdrop-filter: none;
            min-width: 200px;
        }
        
        .dropdown-item {
            color: #111;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background: #f2f4f7;
            color: #000;
            transform: translateX(4px);
        }
        
        .dropdown-divider {
            border-color: rgba(0, 0, 0, 0.08);
            margin: 0.5rem 0;
        }
        
        /* Navbar dropdown specific */
        .navbar .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            z-index: 9999 !important;
        }
        
        .navbar .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        /* Toast notifications */
        .toast {
            background-color: var(--darker-bg) !important;
            border: 1px solid var(--border-color);
            color: var(--text-light) !important;
        }
        
        .toast.bg-success {
            background-color: var(--accent-green) !important;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--darkest-bg);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }
        
        /* Modern Footer */
        .modern-footer {
            background: var(--secondary-bg);
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .modern-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-primary);
        }
        
        .footer-content {
            padding: 4rem 0 2rem;
        }
        
        .footer-brand .brand-name {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .footer-brand .brand-description {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-link {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .social-link.discord {
            background: linear-gradient(135deg, #5865f2 0%, #7289da 100%);
        }
        
        .social-link.youtube {
            background: linear-gradient(135deg, #ff0000 0%, #ff4444 100%);
        }
        
        .social-link.twitter {
            background: linear-gradient(135deg, #1da1f2 0%, #0d8bd9 100%);
        }
        
        .social-link.github {
            background: linear-gradient(135deg, #333 0%, #555 100%);
        }
        
        .social-link.telegram {
            background: linear-gradient(135deg, #2AABEE 0%, #229ED9 100%);
        }
        
        .social-link.instagram {
            background: linear-gradient(135deg, #f58529 0%, #dd2a7b 50%, #8134af 75%, #515bd4 100%);
        }
        
        .social-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .footer-section .section-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .footer-section .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--gradient-primary);
            border-radius: 1px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding-left: 1rem;
        }
        
        .footer-links a::before {
            content: '→';
            position: absolute;
            left: 0;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--text-primary);
            padding-left: 1.5rem;
        }
        
        .footer-links a:hover::before {
            opacity: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--primary-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            border-color: var(--accent-purple);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .footer-bottom {
            background: var(--primary-bg);
            padding: 1.5rem 0;
            border-top: 1px solid var(--border-color);
        }
        
        .copyright, .made-with {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.9rem;
        }
        
        .heart {
            color: #ec4899;
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Authentication Pages Styles */
        .auth-page-container {
            min-height: 100vh;
            background: var(--primary-bg);
            position: relative;
            overflow: hidden;
            margin-bottom: -4rem;
        }
        
        .auth-page-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-primary);
            opacity: 0.1;
            z-index: 1;
        }
        
        .auth-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 2;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .auth-title {
            color: var(--text-primary);
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
            margin: 0;
            font-size: 1rem;
        }
        
        .auth-body {
            margin-bottom: 2rem;
        }
        
        .auth-alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .auth-alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .auth-alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-input {
            background: var(--primary-bg);
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.875rem 1rem;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--text-muted);
        }
        
        .form-hint {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .password-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--text-primary);
            background: var(--hover-bg);
        }
        
        .captcha-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .captcha-question {
            background: var(--hover-bg);
            color: var(--text-primary);
            padding: 0.875rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            white-space: nowrap;
            border: 2px solid var(--border-color);
        }
        
        .captcha-input {
            flex: 1;
            min-width: 0;
        }
        
        .auth-button {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .auth-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .auth-switch-text {
            color: var(--text-secondary);
            margin: 0 0 1rem 0;
        }
        
        .auth-switch-link {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .auth-switch-link:hover {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .auth-demo-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--primary-bg);
            border-radius: 12px;
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: 1px solid var(--border-color);
        }
        
        /* Responsive Auth Pages */
        @media (max-width: 768px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
                border-radius: 16px;
            }
            
            .auth-logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .auth-title {
                font-size: 1.5rem;
            }
            
            .captcha-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .captcha-question {
                text-align: center;
            }
        }
        
        /* Profile Page Styles */
        .profile-page-container {
            min-height: 80vh;
        }
        
        .profile-header-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .profile-avatar-section {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .profile-avatar {
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            border-color: var(--accent-purple);
            transform: scale(1.05);
        }
        
        .profile-name {
            color: var(--text-primary);
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .profile-join-date {
            color: var(--text-secondary);
            margin: 0 0 1rem 0;
            font-size: 1rem;
        }
        
        .admin-badge {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .section-title {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-servers-section {
            margin-bottom: 2rem;
        }
        
        .servers-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .server-card-profile {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            overflow: hidden;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }
        
        .server-card-profile:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-color: var(--accent-purple);
        }
        
        .server-card-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .server-logo-small {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .server-logo-small.default-logo {
            background: var(--accent-green);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .server-info-small {
            flex: 1;
        }
        
        .server-name-small {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .server-name-small a {
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .server-name-small a:hover {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .server-ip-small {
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            margin: 0;
            word-break: break-all;
            overflow-wrap: break-word;
        }
        
        .server-stats-small {
            text-align: center;
        }
        
        .stat-item-small {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-number-small {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-green);
        }
        
        .stat-label-small {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .server-card-body {
            padding: 0 1.5rem 1.5rem;
        }
        
        .server-description-small {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .server-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-view-server, .btn-edit-server, .btn-admin-edit {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-view-server {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-edit-server {
            background: var(--primary-bg);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        
        .btn-view-server:hover, .btn-edit-server:hover, .btn-admin-edit:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-edit-server:hover {
            background: var(--accent-orange);
            border-color: var(--accent-orange);
        }
        
        .no-servers-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .no-servers-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .no-servers-section h3 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .no-servers-section p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .btn-add-server {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-add-server:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .profile-stats-card, .profile-info-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .profile-stats-card h4, .profile-info-card h4 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .stat-item-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--primary-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-item-profile:hover {
            transform: translateX(4px);
            border-color: var(--accent-purple);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* ===== NUOVA PAGINA PROFILO RIPROGETTATA ===== */
        
        /* Container principale */
        .profile-container {
            min-height: 100vh;
            background: var(--bg-primary);
            padding: 2rem 0;
            overflow-x: hidden;
            max-width: 100%;
        }
        
        .profile-container .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 15px;
            padding-right: 15px;
            overflow-x: hidden;
        }
        
        /* Header del profilo */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: var(--primary-bg);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .profile-avatar {
            position: relative;
            flex-shrink: 0;
        }
        
        .avatar-img {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            border: 3px solid var(--accent-purple);
            box-shadow: 0 4px 16px rgba(139, 69, 255, 0.3);
            object-fit: cover;
            object-position: center;
            display: block;
            background: var(--secondary-bg);
            image-rendering: pixelated;
            image-rendering: -moz-crisp-edges;
            image-rendering: crisp-edges;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-nickname {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .profile-join-date {
            color: var(--text-secondary);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* Colori specifici per i diversi ruoli */
        .admin-badge.user-role {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .admin-badge.owner-role {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .admin-badge.admin-role {
            background: var(--gradient-primary);
        }
        
        /* Navigazione principale */
        .profile-navigation {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--primary-bg);
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: fit-content;
        }
        
        .nav-btn:hover {
            background: var(--secondary-bg);
            border-color: var(--accent-purple);
            color: var(--text-primary);
            transform: translateY(-2px);
        }
        
        .nav-btn.active {
            background: var(--gradient-primary);
            border-color: var(--accent-purple);
            color: white;
            box-shadow: 0 4px 16px rgba(139, 69, 255, 0.3);
        }
        
        /* Contenuto delle sezioni */
        .profile-content {
            background: var(--primary-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .content-section {
            display: none;
            padding: 2rem;
            max-width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        
        .content-section.active {
            display: block;
        }
        
        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-header p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        /* Sezione Il mio profilo */
        .profile-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--secondary-bg);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-purple);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Voto giornaliero */
        .daily-vote-section {
            margin-top: 2rem;
        }
        
        .daily-vote-section h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .daily-vote-card {
            background: var(--secondary-bg);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        .daily-vote-card.voted {
            border-color: var(--success-color);
            background: rgba(34, 197, 94, 0.1);
        }
        
        .daily-vote-card.not-voted {
            border-color: var(--warning-color);
            background: rgba(251, 191, 36, 0.1);
        }
        
        .vote-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-primary);
            flex: 1;
        }
        
        .vote-info i {
            font-size: 1.2rem;
        }
        
        .voted .vote-info i {
            color: var(--success-color);
        }
        
        .not-voted .vote-info i {
            color: var(--warning-color);
        }
        
        .vote-time {
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .vote-btn {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(139, 69, 255, 0.3);
            color: white;
        }
        
        /* Sezione Elenco Voti */
        .votes-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .vote-item {
            background: var(--secondary-bg);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .vote-item:hover {
            transform: translateX(4px);
            border-color: var(--accent-purple);
        }
        
        .vote-server-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .server-logo-small {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
        }
        
        .server-logo-small.default-logo {
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .server-details {
            flex: 1;
        }
        
        .server-name {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .server-name a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .server-name a:hover {
            color: var(--accent-purple);
        }
        
        .server-ip {
            margin: 0;
            color: #000000;
            font-size: 0.9rem;
        }
        
        .vote-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .no-votes-section {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .no-votes-icon {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .no-votes-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
        }
        
        .no-votes-section p {
            color: var(--text-secondary);
            margin: 0 0 2rem 0;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Form per nuovo server */
        .server-request-form {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            background: var(--secondary-bg);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--secondary-bg);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(139, 69, 255, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* Gestione Server */
        .edit-server-form {
            background: var(--secondary-bg);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .edit-server-form h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .servers-section,
        .licenses-section {
            margin-bottom: 3rem;
        }
        
        .servers-section h3,
        .licenses-section h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .servers-grid,
        .licenses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
            gap: 1.5rem;
            width: 100%;
            max-width: 100%;
            margin-top: 1.5rem;
            align-items: start;
            box-sizing: border-box;
        }
        
        @media (min-width: 768px) {
            .servers-grid,
            .licenses-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .profile-container .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .content-section {
                padding: 2rem;
            }
            
            /* Ripristina margini standard su desktop */
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .row {
                margin-left: -15px;
                margin-right: -15px;
            }
            
            .col-lg-9,
            .col-lg-3 {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        @media (min-width: 1200px) {
            .servers-grid,
            .licenses-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Stile base per tutte le server-card */
        .server-card {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color) !important;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .server-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-purple);
        }
        
        /* Nuova classe per le server-card della homepage */
        .homepage-server-card {
            background: var(--card-bg);
            border: none !important;
            border-radius: 12px;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 1.2rem 1.5rem;
            min-height: 60px;
            height: auto;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 1px 4px rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(20px);
        }
        
        .homepage-server-card:hover {
            background: var(--card-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .homepage-server-card:last-child {
            margin-bottom: 0;
        }
        
        /* Stile per le server-card del profile (layout verticale) */
        .profile-servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }
        
        .profile-servers-grid .server-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 280px;
        }
        
        .profile-servers-grid .server-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .server-card-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Header per homepage-server-card */
        .homepage-server-card .server-card-header {
            padding: 0;
            border-bottom: none;
            flex: 1;
        }
        
        /* Stats per homepage-server-card */
        .homepage-server-card .server-stats {
            margin-left: auto;
            flex-shrink: 0;
        }
        
        /* Spaziatura migliorata per homepage-server-card */
        .homepage-server-card .server-rank-container {
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        
        .homepage-server-card .server-logo {
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        
        .homepage-server-card .server-info {
            flex: 1;
            margin-right: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .homepage-server-card .server-basic-info {
            flex: 1;
        }
        
        .homepage-server-card .server-tags {
            flex-shrink: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        
        .homepage-server-card .server-players {
            flex-shrink: 0;
            text-align: right;
        }
        
        .server-logo {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            display: block;
        }
        
        .server-logo.default-logo {
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .server-info {
            flex: 1;
            min-width: 0;
        }
        
        .server-name {
            margin: 0 0 0.75rem 0;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .server-name a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .server-name a:hover {
            color: var(--accent-purple);
        }
        
        .server-ip {
            margin: 0;
            color: #000000;
            font-size: 0.9rem;
        }
        
        .server-stats {
            display: flex;
            gap: 1rem;
            text-align: center;
            align-items: center;
        }
        

        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            min-width: 60px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-purple);
            line-height: 1;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .server-card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .server-description {
            color: var(--text-secondary);
            margin: 0 0 1.5rem 0;
            line-height: 1.5;
        }
        
        .server-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-view-server,
        .btn-edit-server {
            flex: 1;
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-view-server {
            background: var(--secondary-bg);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-view-server:hover {
            background: var(--primary-bg);
            color: var(--text-primary);
            transform: translateY(-2px);
            border-color: var(--accent-purple);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-edit-server {
            background: var(--gradient-primary);
            color: white;
            border: 2px solid var(--accent-purple);
            position: relative;
        }
        
        .btn-edit-server::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-edit-server:hover::before {
            left: 100%;
        }
        
        .btn-edit-server:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 69, 255, 0.4);
            color: white;
        }
        
        /* Stili per server in attesa di approvazione */
        .pending-server {
            opacity: 0.8;
            border: 2px solid #f39c12;
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.05), rgba(243, 156, 18, 0.02));
        }
        
        .pending-server .server-name {
            color: var(--text-secondary);
            cursor: default;
        }
        
        .pending-status {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
            box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3);
        }
        
        .pending-server-info {
            padding: 1rem;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        
        .pending-message {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Stili per server disabilitati */
        .disabled-server {
            opacity: 0.7;
            border: 2px solid #e74c3c;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.05), rgba(231, 76, 60, 0.02));
        }
        
        .disabled-server .server-name {
            color: var(--text-secondary);
            cursor: default;
        }
        
        .disabled-server-info {
            padding: 1rem;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }
        
        .disabled-message {
            margin: 0 0 0.5rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            align-items: center;
            gap: 0.5rem;
        }
        
        .disabled-message a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .disabled-message a:hover {
            text-decoration: underline;
        }
        
        .disabled-date {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-style: italic;
        }
        
        .pending-message i {
            color: #f39c12;
            font-size: 1rem;
        }
        
        .no-servers-section,
        .no-licenses-section {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .no-servers-icon,
        .no-licenses-icon {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .no-servers-section h3,
        .no-licenses-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
        }
        
        .no-servers-section p,
        .no-licenses-section p {
            color: var(--text-secondary);
            margin: 0;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Licenze */
        .license-card {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }
        
        .license-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-purple);
        }
        
        .license-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .license-server-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .license-server-name {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .license-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .license-status.active {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }
        
        .license-status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        .license-card-body {
            padding: 1.5rem;
        }
        
        .license-key-display {
            margin-bottom: 1.5rem;
        }
        
        .license-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .license-key-value {
            background: var(--primary-bg);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--text-primary);
            display: block;
            word-break: break-all;
            overflow-wrap: break-word;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .license-dots {
            color: var(--text-secondary);
        }
        
        .license-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .license-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .license-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .view-license-btn,
        .copy-license-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--primary-bg);
            color: var(--text-primary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .view-license-btn:hover,
        .copy-license-btn:hover {
            background: var(--secondary-bg);
            border-color: var(--accent-purple);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            /* Previeni overflow globale */
            * {
                max-width: 100%;
                box-sizing: border-box;
            }
            
            body {
                overflow-x: hidden;
                max-width: 100vw;
            }
            
            html {
                overflow-x: hidden;
                max-width: 100vw;
            }
            
            /* Fix homepage server cards */
            .homepage-server-card {
                margin-left: 0;
                margin-right: 0;
                padding: 0.75rem;
                max-width: 100%;
                width: 100%;
                box-sizing: border-box;
            }
            
            .homepage-server-card .server-rank-container,
            .homepage-server-card .server-logo,
            .homepage-server-card .server-info {
                margin-right: 0.5rem;
            }
            
            .server-list {
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }
            
            .server-list-header {
                padding: 1rem 0.5rem;
                margin: 0;
            }
            
            /* Fix colonne Bootstrap su mobile */
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                max-width: 100%;
            }
            
            .col-lg-9,
            .col-lg-3 {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }
            
            .row {
                margin-left: 0;
                margin-right: 0;
            }
            
            .profile-container .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .profile-container {
                padding: 1rem 0;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 1rem;
                margin-left: 0;
                margin-right: 0;
            }
            
            .profile-navigation {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .nav-btn {
                justify-content: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .servers-grid,
            .licenses-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0;
                margin-left: 0;
                margin-right: 0;
            }
            
            .profile-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .vote-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .vote-server-info {
                width: 100%;
            }
            
            .server-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-view-server,
            .btn-edit-server,
            .btn-admin-edit {
                width: 100%;
                justify-content: center;
            }
            
            .license-actions {
                flex-direction: column;
            }
            
            /* Ottimizza card server per mobile */
            .server-card-profile,
            .license-card {
                margin-bottom: 1rem;
                margin-left: 0;
                margin-right: 0;
            }
            
            .server-card-header,
            .license-card-header {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .license-card-body {
                padding: 1rem;
            }
            
            .server-logo-small {
                width: 80px;
                height: 80px;
            }
            
            .server-info-small {
                width: 100%;
            }
            
            .server-name-small {
                font-size: 1.1rem;
            }
            
            .server-ip-small {
                font-size: 0.85rem;
                word-break: break-all;
            }
            
            .server-stats-small {
                margin-top: 1rem;
            }
            
            .server-card-body {
                padding: 1rem;
            }
            
            /* Form ottimizzato per mobile */
            .server-request-form,
            .edit-server-form {
                padding: 0;
                max-width: 100%;
            }
            
            .content-section {
                padding: 1rem;
            }
            
            .profile-content {
                margin: 0 0.5rem;
            }
            
            .form-group label {
                font-size: 0.9rem;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px; /* Previene zoom su iOS */
            }
            
            .form-actions {
                margin-top: 1.5rem;
            }
            
            .form-actions button {
                width: 100%;
                padding: 1rem;
                font-size: 1rem;
            }
            
            /* Modalità selection ottimizzata */
            .modalita-selection {
                gap: 8px;
            }
            
            .modalita-tag {
                font-size: 11px;
                padding: 6px 10px;
            }
            
            /* Staff list editor mobile */
            .stafflist-editor,
            #sociallinks-editor {
                padding: 0.75rem !important;
            }
            
            .stafflist-actions,
            .sociallinks-actions {
                flex-direction: column;
            }
            
            .stafflist-actions button,
            .sociallinks-actions button {
                width: 100%;
            }
            
            /* Rank groups mobile */
            .staff-rank-group {
                padding: 0.75rem !important;
            }
            
            .rank-title-input,
            .member-name-input {
                width: 100% !important;
                min-width: auto !important;
            }
            
            /* Social links mobile */
            .social-row {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }
            
            .social-title-input,
            .social-url-input {
                width: 100% !important;
                min-width: auto !important;
            }
        }
        
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .status-active {
            color: var(--accent-green) !important;
        }
        
        /* Responsive Design for Profile */
        @media (max-width: 992px) {
            .profile-avatar-section {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
        }
        
        /* Ottimizzazioni extra per schermi molto piccoli */
        @media (max-width: 480px) {
            .profile-header {
                padding: 1rem;
            }
            
            .profile-avatar img {
                width: 80px;
                height: 80px;
            }
            
            .profile-nickname {
                font-size: 1.5rem;
            }
            
            .profile-navigation {
                padding: 0.5rem;
            }
            
            .nav-btn {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .nav-btn i {
                font-size: 1rem;
            }
            
            .section-header h2 {
                font-size: 1.3rem;
            }
            
            .section-header p {
                font-size: 0.85rem;
            }
            
            .server-logo-small {
                width: 60px;
                height: 60px;
            }
            
            .stat-number-small {
                font-size: 1.2rem;
            }
            
            .stat-label-small {
                font-size: 0.7rem;
            }
            
            .stat-item {
                padding: 0.75rem;
                margin: 0;
            }
            
            .profile-stats-grid {
                gap: 0.75rem;
            }
        }
        
        @media (max-width: 992px) {
            .servers-grid,
            .licenses-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.25rem;
            }
            
            .server-stats {
                min-width: 70px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 1200px) {
            .servers-grid,
            .licenses-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .servers-grid,
            .licenses-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
                margin-top: 1rem;
            }
            
            .server-card-header {
                flex-direction: column;
                text-align: center;
                gap: 1.25rem;
                align-items: center;
            }
            
            .server-info {
                text-align: center;
            }
            
            .server-stats {
                order: -1;
                align-self: stretch;
                margin-bottom: 1rem;
            }
            
            .server-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn-view-server,
            .btn-edit-server {
                padding: 1rem 1.5rem;
            }
            
            .profile-header-card {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .servers-grid,
            .licenses-grid {
                gap: 1rem;
                margin-top: 0.75rem;
            }
            
            .server-card {
                min-height: 260px;
            }
            
            .server-card-header {
                padding: 1.25rem;
            }
            
            .server-card-body {
                padding: 1.25rem;
            }
            
            .server-logo {
                width: 60px;
                height: 60px;
            }
            
            .server-name {
                font-size: 1.2rem;
            }
        }
        
        /* Server Page Styles */
        .server-page-container {
            margin-top: -2rem;
        }
        
        .server-header {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .server-banner-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .server-banner-bg.default-banner {
            background: var(--gradient-primary);
        }
        
        .server-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.4) 100%);
        }
        
        .server-header-content {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem 0;
            gap: 2rem;
        }
        
        .server-main-info {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .server-logo-large {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            object-fit: cover;
        }
        
        .server-logo-large.default-logo {
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-size: 3rem;
        }
        
        .server-title {
            color: white;
            font-size: 3rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .server-rank-badge {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a1a2e;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            display: inline-block;
            margin: 1rem 0;
            font-size: 1.1rem;
        }
        
        .server-ip-display {
            background: var(--accent-green);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .server-ip-display:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .server-vote-section {
            text-align: center;
            color: white;
            min-width: 200px;
            flex-shrink: 0;
        }
        
        .vote-button {
            background: var(--accent-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .vote-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .vote-count {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .vote-increment {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .vote-increment:hover {
            transform: scale(1.1);
        }
        
        .vote-button.vote-disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .vote-button.vote-disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .vote-info {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            padding: 0.75rem;
            background: var(--primary-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            line-height: 1.4;
        }
        
        .vote-info strong {
            color: var(--accent-green);
        }
        
        .server-content {
            margin-top: 2rem;
        }
        
        .server-nav-tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: 12px 12px 0 0;
            padding: 0.5rem;
            margin-bottom: 0;
        }
        
        .tab-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }
        
        .tab-btn.active {
            background: var(--gradient-primary);
            color: white;
        }
        
        .tab-content-container {
            background: var(--card-bg);
            border-radius: 0 0 12px 12px;
            padding: 2rem;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .content-banner {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .content-banner-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .banner-text-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 2rem;
        }
        
        .banner-text-overlay h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 0 1rem 0;
        }
        
        .banner-text-overlay p {
            font-size: 1.2rem;
            margin: 0;
            opacity: 0.9;
        }
        
        .server-description {
            color: var(--text-primary);
            line-height: 1.8;
        }
        
        .description-text {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .description-highlight {
            background: var(--accent-green);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 2rem 0;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .description-detail {
            font-size: 1rem;
            margin-bottom: 2rem;
            color: var(--text-secondary);
        }
        
        .gameplay-highlight {
            text-align: center;
            font-size: 1.3rem;
            margin: 2rem 0;
        }
        
        .gameplay-highlight em {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .gameplay-highlight strong {
            color: var(--accent-green);
            font-weight: 700;
        }
        
        .server-info-card, .server-social-card, .recent-voters-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .server-info-card h4, .server-social-card h4, .recent-voters-card h4 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .server-tags-section {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .server-tag-modern {
            background: var(--primary-bg);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid var(--border-color);
            display: inline-block;
            width: 140px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .social-links-modern {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .social-link-modern {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--primary-bg);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .social-link-modern:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
            transform: translateX(4px);
        }
        
        .social-link-modern i {
            font-size: 1.2rem;
            width: 20px;
        }
        
        .social-link-modern.website i {
            color: var(--accent-cyan);
        }
        
        .social-link-modern.shop i {
            color: var(--accent-orange);
        }
        
        .social-link-modern.discord i {
            color: #5865f2;
        }
        
        .social-link-modern.telegram i {
            color: #0088cc;
        }
        
        .social-url {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-left: auto;
        }
        
        .voters-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0.5rem;
            padding-top: 2rem;
            overflow: visible;
        }
        
        .voters-grid.scrollable {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: var(--accent-color) transparent;
        }
        
        .voters-grid.scrollable::-webkit-scrollbar {
            width: 6px;
        }
        
        .voters-grid.scrollable::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .voters-grid.scrollable::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 3px;
        }
        
        .voters-grid.scrollable::-webkit-scrollbar-thumb:hover {
            background: var(--accent-hover);
        }
        
        .voter-avatar-modern {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        
        .voter-avatar-modern:hover {
            transform: scale(1.1);
            border-color: var(--accent-green);
            z-index: 10;
        }
        
        .voter-avatar-modern img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Custom Tooltip for Voters - Improved Version */
        .voter-avatar-modern {
            position: relative;
        }
        
        .voter-avatar-modern::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 12px);
            left: 50%;
            transform: translateX(-50%);
            background: #000;
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .voter-avatar-modern::after {
            content: '';
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 10000;
        }
        
        .voter-avatar-modern:hover::before,
        .voter-avatar-modern:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        .voter-avatar-modern:hover::before {
            transform: translateX(-50%) translateY(-4px);
        }
        
        /* Ensure voters grid doesn't clip tooltips */
        .voters-grid {
            overflow: visible;
            padding-top: 2.5rem;
        }
        
        .recent-voters-card {
            overflow: visible;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            background: var(--primary-bg);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-green);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Responsive Design for Server Page */
        @media (max-width: 992px) {
            .server-header {
                height: 300px;
            }
            
            .server-header-content {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }
            
            .server-main-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            .server-title {
                font-size: 2rem;
            }
            
            .server-logo-large {
                width: 80px;
                height: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .server-nav-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                padding: 0.75rem 1rem;
            }
            
            .voters-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            .banner-text-overlay h2 {
                font-size: 1.8rem;
            }
            
            .banner-text-overlay p {
                font-size: 1rem;
            }
        }
        
        /* === IMPROVEMENTS.CSS CONTENT === */
        /* Profile.php - Indicatore server votato */
        .voted-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            font-size: 0.85rem;
        }
        
        .voted-text {
            color: var(--success-color, #28a745);
            font-weight: 500;
        }
        
        /* Server.php - Layout migliorato header */
        .server-details-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .server-ip-display {
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .server-ip-display:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Vote info posizionata meglio */
        .vote-info-below {
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            text-align: center;
        }
        
        /* Index.php - Status filtri */
        .filter-status {
            margin-bottom: 0.75rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            border-left: 3px solid var(--primary-color, #007bff);
        }
        
        .filter-status small {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: #4b5563 !important;
        }
        
        .filter-status small.text-muted {
            color: #4b5563 !important;
        }
        
        .filter-status small.text-primary {
            color: #2563eb !important;
        }
        
        /* Override specifico per Bootstrap */
        .filters-sidebar .filter-status .text-muted {
            color: #4b5563 !important;
            opacity: 1 !important;
        }
        
        /* Nuove classi per status filtri */
        .filter-status-text {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: #6b7280 !important;
        }
        
        .filter-status-text.filter-status-active {
            color: #2563eb !important;
        }
        
        /* Tooltip per votanti */
        .voter-avatar-modern {
            position: relative;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .voter-avatar-modern:hover {
            transform: scale(1.1);
            z-index: 10;
        }
        
        .voter-tooltip {
            animation: fadeInTooltip 0.2s ease-in-out;
        }
        
        @keyframes fadeInTooltip {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        /* Enhanced toast notifications for license actions */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .toast-notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .toast-notification.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .toast-notification i {
            font-size: 1.25rem;
        }
        
        @keyframes slideInToastEnhanced {
            from {
                opacity: 0;
                transform: translateX(100%) translateY(-20px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(0) translateY(0) scale(1);
            }
        }
        
        .toast-slide-out {
            animation: slideOutToastEnhanced 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        @keyframes slideOutToastEnhanced {
            from {
                opacity: 1;
                transform: translateX(0) translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateX(100%) translateY(-20px) scale(0.9);
            }
        }
        
        /* Animazioni per toast personalizzato */
        @keyframes slideInToast {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutToast {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
         }
         
         /* === RESTO DI IMPROVEMENTS.CSS === */
         .form-input {
             background: var(--primary-bg) !important;
             border: 2px solid var(--border-color) !important;
             color: var(--text-primary) !important;
             padding: 1rem 1.25rem !important;
             border-radius: 16px !important;
             font-size: 1rem !important;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
             width: 100% !important;
         }
         
         .form-input:focus {
             outline: none !important;
             border-color: var(--accent-purple) !important;
             box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1) !important;
             transform: translateY(-2px) !important;
         }
         
         .form-input::placeholder {
             color: var(--text-muted) !important;
         }
         
         .password-input-group {
             position: relative !important;
             display: flex !important;
             align-items: center !important;
         }
         
         .password-toggle {
             position: absolute !important;
             right: 1rem !important;
             background: none !important;
             border: none !important;
             color: var(--text-muted) !important;
             cursor: pointer !important;
             padding: 0.75rem !important;
             border-radius: 8px !important;
             transition: all 0.3s ease !important;
             z-index: 10 !important;
         }
         
         .password-toggle:hover {
             color: var(--text-primary) !important;
             background: var(--hover-bg) !important;
         }
         
         .captcha-group {
             display: flex !important;
             align-items: center !important;
             gap: 1.25rem !important;
             flex-wrap: wrap !important;
         }
         
         .captcha-question {
             background: var(--hover-bg) !important;
             color: var(--text-primary) !important;
             padding: 1rem 1.25rem !important;
             border-radius: 16px !important;
             font-weight: 600 !important;
             white-space: nowrap !important;
             border: 2px solid var(--border-color) !important;
             min-width: 140px !important;
             text-align: center !important;
         }
         
         .captcha-input {
             flex: 1 !important;
             min-width: 100px !important;
         }
         
         .form-hint {
             color: var(--text-muted) !important;
             font-size: 0.9rem !important;
             font-style: italic !important;
         }
         
         .auth-button {
             background: var(--gradient-primary) !important;
             color: white !important;
             border: none !important;
             padding: 1.25rem 2rem !important;
             border-radius: 16px !important;
             font-size: 1.1rem !important;
             font-weight: 600 !important;
             cursor: pointer !important;
             transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
             display: flex !important;
             align-items: center !important;
             justify-content: center !important;
             gap: 0.75rem !important;
             margin-top: 1rem !important;
             position: relative !important;
             overflow: hidden !important;
         }
         
         .auth-button::before {
             content: '' !important;
             position: absolute !important;
             top: 0 !important;
             left: -100% !important;
             width: 100% !important;
             height: 100% !important;
             background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent) !important;
             transition: left 0.6s ease !important;
         }
         
         .auth-button:hover::before {
             left: 100% !important;
         }
         
         .auth-button:hover {
             transform: translateY(-3px) !important;
             box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4) !important;
         }
         
         .auth-button:active {
             transform: translateY(-1px) !important;
         }
         
         .auth-footer {
             text-align: center !important;
             margin-top: 2.5rem !important;
             padding-top: 2rem !important;
             border-top: 1px solid var(--border-color) !important;
         }
         
         .auth-switch-text {
             color: var(--text-secondary) !important;
             margin: 0 0 1rem 0 !important;
             font-size: 1rem !important;
         }
         
         .auth-switch-link {
             color: var(--accent-purple) !important;
             text-decoration: none !important;
             font-weight: 600 !important;
             font-size: 1.1rem !important;
             display: inline-flex !important;
             align-items: center !important;
             gap: 0.5rem !important;
             padding: 0.75rem 1.5rem !important;
             border-radius: 12px !important;
             transition: all 0.3s ease !important;
             border: 2px solid transparent !important;
         }
         
         .auth-switch-link:hover {
             color: white !important;
             background: var(--gradient-primary) !important;
             transform: translateY(-2px) !important;
             text-decoration: none !important;
         }
         
         .auth-demo-info {
             margin-top: 1.5rem !important;
             padding: 1rem !important;
             background: rgba(102, 126, 234, 0.1) !important;
             border-radius: 12px !important;
             color: var(--accent-purple) !important;
             font-size: 0.9rem !important;
             display: flex !important;
             align-items: center !important;
             justify-content: center !important;
             gap: 0.5rem !important;
         }
         
         .auth-alert {
             display: flex !important;
             align-items: center !important;
             gap: 1rem !important;
             padding: 1.25rem !important;
             border-radius: 16px !important;
             margin-bottom: 2rem !important;
             font-weight: 500 !important;
             animation: authAlertSlideIn 0.5s ease-out !important;
         }
         
         @keyframes authAlertSlideIn {
             from {
                 opacity: 0;
                 transform: translateX(-20px);
             }
             to {
                 opacity: 1;
                 transform: translateX(0);
             }
         }
         
         .auth-alert-success {
             background: rgba(16, 185, 129, 0.15) !important;
             color: var(--accent-green) !important;
             border: 2px solid rgba(16, 185, 129, 0.3) !important;
         }
         
         .auth-alert-error {
              background: rgba(239, 68, 68, 0.15) !important;
              color: #ef4444 !important;
              border: 2px solid rgba(239, 68, 68, 0.3) !important;
          }
          
          /* === AUTH-IMPROVEMENTS.CSS CONTENT === */
          /* Enhanced Auth Pages Styling - Desktop First */
          .auth-page-container {
              min-height: 100vh !important;
              position: relative !important;
              display: flex !important;
              align-items: center !important;
              justify-content: center !important;
              padding: 2rem 0 !important;
          }
          
          .auth-page-container::before {
              display: none !important;
          }
          
          .auth-card {
              background: var(--card-bg) !important;
              border-radius: 24px !important;
              padding: 3rem 4rem !important;
              box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5) !important;
              border: 1px solid var(--border-color) !important;
              backdrop-filter: blur(20px) !important;
              position: relative !important;
              z-index: 2 !important;
              max-width: 900px !important;
              width: 100% !important;
              margin: 0 auto !important;
              animation: authCardSlideIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) !important;
          }
          
          @keyframes authCardSlideIn {
              from {
                  opacity: 0;
                  transform: translateY(30px) scale(0.95);
              }
              to {
                  opacity: 1;
                  transform: translateY(0) scale(1);
              }
          }
          
          /* Desktop Layout - Two Column */
          @media (min-width: 769px) {
              .auth-card {
                  display: grid !important;
                  grid-template-columns: 1fr 1fr !important;
                  gap: 3rem !important;
                  align-items: center !important;
                  max-width: 1100px !important;
                  padding: 4rem !important;
              }
              
              .auth-header {
                  text-align: center !important;
                  margin-bottom: 0 !important;
              }
              
              .auth-body {
                  display: flex !important;
                  flex-direction: column !important;
                  justify-content: center !important;
                  margin-bottom: 0 !important;
              }
              
              .auth-footer {
                  grid-column: 1 / -1 !important;
                  text-align: center !important;
                  margin-top: 2rem !important;
                  padding-top: 2rem !important;
                  border-top: 1px solid var(--border-color) !important;
              }
          }
          
          .auth-header {
              text-align: center !important;
              margin-bottom: 2.5rem !important;
          }
          
          /* Force center alignment for all screen sizes */
          .auth-card .auth-header {
              text-align: center !important;
          }
          
          .auth-card .auth-body {
              margin-bottom: 0 !important;
          }
          
          /* Force logo centering */
          .auth-card .auth-logo {
              margin: 0 auto 2rem auto !important;
              display: flex !important;
              margin-left: auto !important;
              margin-right: auto !important;
          }
          
          .auth-logo {
              width: 90px !important;
              height: 90px !important;
              background: var(--gradient-primary) !important;
              border-radius: 24px !important;
              display: flex !important;
              align-items: center !important;
              justify-content: center !important;
              margin: 0 auto 2rem !important;
              font-size: 2.5rem !important;
              color: white !important;
              box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4) !important;
              animation: authLogoFloat 3s ease-in-out infinite !important;
          }
          
          @keyframes authLogoFloat {
              0%, 100% { transform: translateY(0px); }
              50% { transform: translateY(-5px); }
          }
          
          .auth-title {
              color: var(--text-primary) !important;
              font-size: 2.2rem !important;
              font-weight: 800 !important;
              margin: 0 0 0.75rem 0 !important;
              background: var(--gradient-primary) !important;
              -webkit-background-clip: text !important;
              -webkit-text-fill-color: transparent !important;
              background-clip: text !important;
              line-height: 1.2 !important;
          }
          
          .auth-subtitle {
              color: var(--text-secondary) !important;
              margin: 0 !important;
              font-size: 1.1rem !important;
              font-weight: 500 !important;
          }
          
          .auth-form {
              display: flex !important;
              flex-direction: column !important;
              gap: 1.5rem !important;
          }
          
          .form-group {
              display: flex !important;
              flex-direction: column !important;
              gap: 0.75rem !important;
          }
          
          .form-label {
              color: var(--text-primary) !important;
              font-weight: 600 !important;
              font-size: 1rem !important;
              display: flex !important;
              align-items: center !important;
              gap: 0.75rem !important;
          }
          
          .form-label i {
              color: var(--accent-purple) !important;
              font-size: 1.1rem !important;
          }
          
          .form-input {
              background: var(--input-bg) !important;
              border: 2px solid var(--border-color) !important;
              border-radius: 16px !important;
              padding: 1rem 1.25rem !important;
              color: var(--text-primary) !important;
              font-size: 1rem !important;
              font-weight: 500 !important;
              transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
              outline: none !important;
              width: 100% !important;
              box-sizing: border-box !important;
          }
          
          .form-input:focus {
              border-color: var(--accent-purple) !important;
              box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15) !important;
              transform: translateY(-1px) !important;
          }
          
          .form-input::placeholder {
              color: var(--text-muted) !important;
              font-weight: 400 !important;
          }
          
          .password-input-group {
              position: relative !important;
              display: flex !important;
              align-items: center !important;
          }
          
          .password-input-group .form-input {
              padding-right: 3.5rem !important;
          }
          
          .password-toggle {
              position: absolute !important;
              right: 1rem !important;
              background: none !important;
              border: none !important;
              color: var(--text-muted) !important;
              cursor: pointer !important;
              padding: 0.5rem !important;
              border-radius: 8px !important;
              transition: all 0.2s ease !important;
              z-index: 10 !important;
          }
          
          .password-toggle:hover {
              color: var(--accent-purple) !important;
              background: rgba(102, 126, 234, 0.1) !important;
          }
          
          .captcha-group {
              display: flex !important;
              flex-direction: column !important;
              gap: 1rem !important;
          }
          
          .captcha-input-group {
              display: flex !important;
              gap: 1rem !important;
              align-items: flex-end !important;
          }
          
          .captcha-input-group .form-input {
              flex: 1 !important;
          }
          
          .captcha-image {
              background: var(--card-bg) !important;
              border: 2px solid var(--border-color) !important;
              border-radius: 12px !important;
              padding: 0.75rem !important;
              cursor: pointer !important;
              transition: all 0.3s ease !important;
              min-width: 120px !important;
              text-align: center !important;
          }
          
          .captcha-image:hover {
              border-color: var(--accent-purple) !important;
              transform: scale(1.02) !important;
          }
          
          .captcha-image img {
              border-radius: 8px !important;
              max-width: 100% !important;
              height: auto !important;
          }
          
          .recaptcha-container {
              display: flex !important;
              justify-content: center !important;
              margin: 0.5rem 0 !important;
          }
          
          .auth-button {
              background: var(--gradient-primary) !important;
              border: none !important;
              border-radius: 16px !important;
              padding: 1rem 2rem !important;
              color: white !important;
              font-size: 1.1rem !important;
              font-weight: 700 !important;
              cursor: pointer !important;
              transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
              position: relative !important;
              overflow: hidden !important;
              text-transform: uppercase !important;
              letter-spacing: 0.5px !important;
              box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3) !important;
              width: 100% !important;
              margin-top: 1rem !important;
          }
          
          .auth-button:hover {
              transform: translateY(-2px) !important;
              box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4) !important;
          }
          
          .auth-button:active {
              transform: translateY(0) !important;
          }
          
          .auth-button:disabled {
              opacity: 0.6 !important;
              cursor: not-allowed !important;
              transform: none !important;
          }
          
          .auth-footer {
              text-align: center !important;
              margin-top: 2rem !important;
              padding-top: 2rem !important;
              border-top: 1px solid var(--border-color) !important;
          }
          
          .auth-switch-text {
              color: var(--text-secondary) !important;
              margin: 0 !important;
              font-size: 1rem !important;
          }
          
          .auth-switch-link {
              color: var(--accent-purple) !important;
              text-decoration: none !important;
              font-weight: 600 !important;
              transition: all 0.2s ease !important;
          }
          
          .auth-switch-link:hover {
              color: var(--accent-blue) !important;
              text-decoration: underline !important;
          }
          
          .auth-demo-info {
              background: rgba(102, 126, 234, 0.1) !important;
              border: 1px solid rgba(102, 126, 234, 0.2) !important;
              border-radius: 12px !important;
              padding: 1rem !important;
              margin-top: 1.5rem !important;
              text-align: center !important;
          }
          
          .auth-demo-info h6 {
              color: var(--accent-purple) !important;
              margin: 0 0 0.5rem 0 !important;
              font-size: 0.9rem !important;
              font-weight: 700 !important;
              text-transform: uppercase !important;
              letter-spacing: 0.5px !important;
          }
          
          .auth-demo-info p {
              color: var(--text-secondary) !important;
              margin: 0 !important;
              font-size: 0.85rem !important;
              line-height: 1.4 !important;
          }
          
          .auth-alert {
              padding: 1rem 1.25rem !important;
              border-radius: 12px !important;
              margin-bottom: 1.5rem !important;
              border: 2px solid transparent !important;
              font-weight: 500 !important;
              display: flex !important;
              align-items: center !important;
              gap: 0.75rem !important;
          }
          
          .auth-alert i {
              font-size: 1.2rem !important;
          }
          
          .auth-alert-success {
              background: rgba(34, 197, 94, 0.15) !important;
              color: #22c55e !important;
              border-color: rgba(34, 197, 94, 0.3) !important;
          }
          
          .auth-alert-error {
              background: rgba(239, 68, 68, 0.15) !important;
              color: #ef4444 !important;
              border-color: rgba(239, 68, 68, 0.3) !important;
          }
          
          /* Mobile Responsive Adjustments */
          @media (max-width: 768px) {
              .auth-page-container {
                  padding: 1rem !important;
                  min-height: 100vh !important;
              }
              
              .auth-card {
                  padding: 2rem 1.5rem !important;
                  border-radius: 20px !important;
                  margin: 0 !important;
                  max-width: none !important;
                  width: 100% !important;
                  box-sizing: border-box !important;
              }
              
              .auth-logo {
                  width: 70px !important;
                  height: 70px !important;
                  font-size: 2rem !important;
                  margin-bottom: 1.5rem !important;
              }
              
              .auth-title {
                  font-size: 1.8rem !important;
              }
              
              .auth-subtitle {
                  font-size: 1rem !important;
              }
              
              .auth-header {
                  margin-bottom: 2rem !important;
              }
              
              .form-input {
                  padding: 0.875rem 1rem !important;
                  font-size: 16px !important;
              }
              
              .auth-button {
                  padding: 0.875rem 1.5rem !important;
                  font-size: 1rem !important;
              }
              
              .captcha-input-group {
                  flex-direction: column !important;
                  align-items: stretch !important;
              }
              
              .captcha-image {
                  min-width: auto !important;
                  align-self: center !important;
              }
          }
          
          @media (max-width: 480px) {
              .auth-card {
                  padding: 1.5rem 1rem !important;
                  border-radius: 16px !important;
              }
              
              .auth-title {
                  font-size: 1.6rem !important;
              }
              
              .auth-logo {
                  width: 60px !important;
                  height: 60px !important;
                  font-size: 1.8rem !important;
              }
          }
          
          /* License Buttons Styles */
          .view-license-btn,
          .copy-license-btn {
              padding: 0.5rem 1rem;
              border: none;
              border-radius: 8px;
              font-size: 0.875rem;
              font-weight: 600;
              cursor: pointer;
              transition: all 0.3s ease;
              display: inline-flex;
              align-items: center;
              gap: 0.5rem;
              text-decoration: none;
              margin: 0.25rem;
          }
          
          .view-license-btn {
              background: var(--gradient-primary);
              color: white;
          }
          
          .view-license-btn:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
              color: white;
          }
          
          .view-license-btn:active {
              transform: translateY(0);
          }
          
          .copy-license-btn {
              background: var(--accent-green);
              color: white;
          }
          
          .copy-license-btn:hover {
              background: #059669;
              transform: translateY(-2px);
          }
          
          .copy-license-btn:active {
              transform: translateY(0);
          }
          
          .view-license-btn i,
          .copy-license-btn i {
              font-size: 1rem;
          }
          
          .view-license-btn:hover i,
          .copy-license-btn:hover i {
              transform: scale(1.1);
          }
          
          .view-license-btn:focus,
          .copy-license-btn:focus {
              outline: none;
              box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
          }
          
          .license-actions .view-license-btn,
          .license-actions .copy-license-btn {
              margin: 0.125rem;
          }
          
          @media (max-width: 768px) {
              .view-license-btn,
              .copy-license-btn {
                  padding: 0.375rem 0.75rem;
                  font-size: 0.8rem;
              }
          }
          
          /* Server Licenses Section Styles */
          .server-licenses-section {
              margin-top: 3rem;
              padding: 2rem 0;
              border-top: 1px solid var(--border-color);
          }

          .server-licenses-section .section-title {
              color: var(--text-primary);
              font-size: 1.5rem;
              font-weight: 600;
              margin-bottom: 2rem;
              display: flex;
              align-items: center;
              gap: 0.75rem;
          }

          .server-licenses-section .section-title i {
              color: var(--accent-purple);
              font-size: 1.3rem;
          }

          .licenses-grid {
              display: grid;
              grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
              gap: 2rem;
          }

          .license-card {
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              border-radius: 12px;
              padding: 1.5rem;
              transition: all 0.3s ease;
              position: relative;
              overflow: hidden;
          }

          .license-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
              border-color: var(--accent-purple);
          }

          .license-card-header {
              display: flex;
              justify-content: space-between;
              align-items: flex-start;
              margin-bottom: 1.5rem;
              gap: 1rem;
          }

          .license-server-info {
              display: flex;
              align-items: center;
              gap: 1rem;
          }

          .license-server-name {
              margin: 0;
              font-size: 1.1rem;
              font-weight: 600;
              color: var(--text-primary);
              line-height: 1.3;
          }

          .license-status {
              display: inline-flex;
              align-items: center;
              gap: 0.4rem;
              padding: 0.3rem 0.75rem;
              border-radius: 20px;
              font-size: 0.8rem;
              font-weight: 500;
              white-space: nowrap;
          }

          .license-status.active {
              background: rgba(16, 185, 129, 0.15);
              color: #059669;
              border: 1px solid rgba(16, 185, 129, 0.3);
          }

          .license-status.inactive {
              background: rgba(239, 68, 68, 0.15);
              color: #dc2626;
              border: 1px solid rgba(239, 68, 68, 0.3);
          }

          .license-card-body {
              display: flex;
              flex-direction: column;
              gap: 1.25rem;
          }

          .license-key-display {
              background: var(--primary-bg);
              border: 1px solid var(--border-color);
              border-radius: 8px;
              padding: 1rem;
              display: flex;
              flex-direction: column;
              gap: 0.5rem;
              transition: all 0.3s ease;
          }

          .license-dots {
              font-family: monospace;
              font-size: 1.1rem;
              letter-spacing: 2px;
              color: var(--text-secondary, #94a3b8);
              user-select: none;
          }

          .license-text {
              font-family: monospace;
              font-size: 0.9rem;
              color: var(--text-primary, #e2e8f0);
              word-break: break-all;
              user-select: all;
              background: rgba(0, 0, 0, 0.2);
              padding: 0.25rem 0.5rem;
              border-radius: 4px;
              border: 1px solid rgba(255, 255, 255, 0.1);
          }

          .license-label {
              font-size: 0.8rem;
              font-weight: 600;
              color: var(--text-secondary);
              text-transform: uppercase;
              letter-spacing: 0.5px;
          }

          .license-key-value {
              font-family: 'Courier New', monospace;
              font-size: 0.95rem;
              font-weight: 600;
              color: var(--text-primary);
              background: rgba(0, 0, 0, 0.05);
              padding: 0.5rem;
              border-radius: 4px;
              word-break: break-all;
              line-height: 1.4;
          }

          .license-meta {
              display: flex;
              flex-direction: column;
              gap: 0.75rem;
          }

          .license-meta-item {
              display: flex;
              align-items: center;
              gap: 0.75rem;
              font-size: 0.85rem;
              color: var(--text-secondary);
          }

          .license-meta-item i {
              color: var(--accent-purple);
              font-size: 0.9rem;
              width: 16px;
              text-align: center;
          }

          .license-actions {
              display: flex;
              gap: 0.75rem;
              margin-top: 1rem;
          }

          .no-licenses-section {
              text-align: center;
              padding: 3rem 2rem;
              background: var(--primary-bg);
              border: 2px dashed var(--border-color);
              border-radius: 16px;
              margin: 2rem 0;
          }

          .no-licenses-content {
              display: flex;
              flex-direction: column;
              align-items: center;
              gap: 1rem;
          }

          .no-licenses-icon {
              font-size: 3rem;
              color: var(--text-secondary);
              opacity: 0.7;
          }

          .no-licenses-content h3 {
              color: var(--text-primary);
              font-size: 1.3rem;
              font-weight: 600;
              margin: 0;
          }

          .no-licenses-content p {
              color: var(--text-secondary);
              font-size: 0.95rem;
              max-width: 400px;
              line-height: 1.5;
              margin: 0;
          }

          /* License Key Container with Copy Button */
          .license-key-container {
              display: flex;
              align-items: center;
              gap: 0.75rem;
              background: var(--primary-bg);
              border: 1px solid var(--border-color);
              border-radius: 8px;
              padding: 0.75rem;
              transition: all 0.3s ease;
          }

          .license-key-container:hover {
              border-color: var(--accent-purple);
              background: rgba(102, 126, 234, 0.05);
          }

          .license-key-container .copy-license-btn {
              margin: 0;
              padding: 0.5rem;
              min-width: auto;
              border-radius: 6px;
              background: var(--accent-green);
              border: none;
              color: white;
              cursor: pointer;
              transition: all 0.3s ease;
              display: flex;
              align-items: center;
              justify-content: center;
          }

          .license-key-container .copy-license-btn:hover {
              background: #059669;
              transform: scale(1.05);
          }

          .license-key-container .copy-license-btn i {
              font-size: 0.9rem;
          }

          .license-key-container .license-key-value {
              flex: 1;
              background: transparent;
              border: none;
              padding: 0;
              margin: 0;
              font-family: 'Courier New', monospace;
              font-size: 0.9rem;
              color: var(--text-primary);
          }

          /* Admin Dashboard Styles */
          .admin-dashboard {
              background: var(--primary-bg);
              min-height: 100vh;
              padding: 2rem 0;
          }

          .admin-sidebar {
              background: var(--card-bg);
              border-radius: 16px;
              padding: 1.5rem;
              height: fit-content;
              position: sticky;
              top: 2rem;
              border: 1px solid var(--border-color);
              box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
          }

          .admin-content {
              background: var(--card-bg);
              border-radius: 16px;
              padding: 2rem;
              border: 1px solid var(--border-color);
              box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
              min-height: 600px;
          }

          .admin-nav-item {
              display: flex;
              align-items: center;
              padding: 0.75rem 1rem;
              margin: 0.25rem 0;
              border-radius: 10px;
              color: var(--text-secondary);
              text-decoration: none;
              transition: all 0.3s ease;
              border: 1px solid transparent;
          }

          .admin-nav-item:hover {
              background: var(--hover-bg);
              color: var(--text-primary);
              transform: translateX(4px);
          }

          .admin-nav-item.active {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: white;
              border-color: var(--accent-purple);
          }

          .admin-nav-item i {
              width: 20px;
              margin-right: 0.75rem;
              text-align: center;
          }

          .stats-grid {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
              gap: 1.5rem;
              margin-bottom: 2rem;
          }

          .stat-card {
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              border-radius: 12px;
              padding: 1.5rem;
              text-align: center;
              transition: transform 0.2s ease, box-shadow 0.2s ease;
              position: relative;
              overflow: hidden;
          }

          .stat-card:hover {
              transform: translateY(-4px);
              box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
          }

          .stat-card::before {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              height: 4px;
              background: var(--gradient-primary);
          }

          .stat-number {
              font-size: 2.5rem;
              font-weight: 700;
              color: var(--text-primary);
              margin-bottom: 0;
          }

          .stat-label {
              color: var(--text-secondary);
              font-size: 0.875rem;
              font-weight: 500;
          }

          /* Notifiche Dashboard */
          .admin-notifications-section {
              margin-bottom: 2rem;
          }
          
          .notifications-grid {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
              gap: 1rem;
          }
          
          .notification-card {
              background: var(--card-bg);
              border-radius: 12px;
              padding: 1.5rem;
              border-left: 4px solid;
              display: flex;
              gap: 1rem;
              transition: all 0.3s ease;
              box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          }
          
          .notification-card:hover {
              transform: translateY(-2px);
              box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
          }
          
          .notification-warning {
              border-left-color: #f59e0b;
              background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, transparent 100%);
          }
          
          .notification-danger {
              border-left-color: #ef4444;
              background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, transparent 100%);
          }
          
          .notification-info {
              border-left-color: #3b82f6;
              background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, transparent 100%);
          }
          
          .notification-success {
              border-left-color: #10b981;
              background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
          }
          
          .notification-icon {
              width: 48px;
              height: 48px;
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 1.5rem;
              flex-shrink: 0;
          }
          
          .notification-warning .notification-icon {
              background: rgba(245, 158, 11, 0.1);
              color: #f59e0b;
          }
          
          .notification-danger .notification-icon {
              background: rgba(239, 68, 68, 0.1);
              color: #ef4444;
          }
          
          .notification-info .notification-icon {
              background: rgba(59, 130, 246, 0.1);
              color: #3b82f6;
          }
          
          .notification-success .notification-icon {
              background: rgba(16, 185, 129, 0.1);
              color: #10b981;
          }
          
          .notification-content {
              flex: 1;
          }
          
          .notification-title {
              font-size: 1rem;
              font-weight: 700;
              color: var(--text-primary);
              margin: 0 0 0.5rem 0;
          }
          
          .notification-message {
              font-size: 0.9rem;
              color: var(--text-secondary);
              margin: 0 0 1rem 0;
          }
          
          .notification-action {
              display: inline-flex;
              align-items: center;
              gap: 0.5rem;
              padding: 0.5rem 1rem;
              background: var(--gradient-primary);
              color: white;
              text-decoration: none;
              border-radius: 8px;
              font-size: 0.875rem;
              font-weight: 600;
              transition: all 0.3s ease;
          }
          
          .notification-action:hover {
              transform: translateX(4px);
              box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
              color: white;
          }

          .stat-icon {
              width: 60px;
              height: 60px;
              min-width: 60px;
              min-height: 60px;
              max-width: 60px;
              max-height: 60px;
              font-size: 1.8rem;
              margin: 0 auto;
              opacity: 0.8;
              background: rgba(255, 255, 255, 0.1);
              border-radius: 12px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              backdrop-filter: blur(10px);
              border: 1px solid rgba(255, 255, 255, 0.2);
              flex-shrink: 0;
              box-sizing: border-box;
          }

          .dashboard-section {
              background: var(--secondary-bg);
              border: 1px solid var(--border-color);
              border-radius: 12px;
              padding: 1.5rem;
              margin-bottom: 1.5rem;
          }

          .section-header {
              display: flex;
              align-items: center;
              justify-content: space-between;
              margin-bottom: 1.5rem;
              padding-bottom: 1rem;
              border-bottom: 1px solid var(--border-color);
          }

          .section-title {
              color: var(--text-primary);
              font-size: 1.25rem;
              font-weight: 600;
              margin: 0;
              display: flex;
              align-items: center;
              gap: 0.5rem;
          }

          .activity-item {
              display: flex;
              align-items: center;
              padding: 0.75rem;
              margin: 0.5rem 0;
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              border-radius: 8px;
              transition: all 0.2s ease;
          }

          .activity-item:hover {
              background: var(--hover-bg);
              transform: translateX(4px);
          }

          .activity-icon {
              width: 40px;
              height: 40px;
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              margin-right: 1rem;
              font-size: 1.1rem;
          }

          .activity-vote {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              color: white;
          }

          .activity-user {
              background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
              color: white;
          }

          .quick-actions {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
              gap: 1rem;
              margin-top: 1.5rem;
          }

          .quick-action-btn {
              display: flex;
              align-items: center;
              justify-content: center;
              padding: 1rem;
              background: var(--card-bg);
              border: 2px solid var(--border-color);
              border-radius: 10px;
              color: var(--text-primary);
              text-decoration: none;
              transition: all 0.3s ease;
              font-weight: 500;
          }

          .quick-action-btn:hover {
              background: var(--hover-bg);
              border-color: var(--accent-purple);
              transform: translateY(-2px);
              color: var(--text-primary);
          }

          .quick-action-btn i {
              margin-right: 0.5rem;
              font-size: 1.1rem;
          }

          @media (max-width: 768px) {
              .admin-dashboard {
                  padding: 1rem 0;
              }
              
              .stats-grid {
                  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                  gap: 1rem;
              }
              
              .admin-sidebar {
                  margin-bottom: 1.5rem;
                  position: static;
              }
              
              .quick-actions {
                  grid-template-columns: 1fr;
              }
          }

          /* Admin Tables and Forms Styling */
          .admin-content .table {
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              border-radius: 12px;
              overflow: hidden;
              margin-bottom: 0;
          }

          .admin-content .table thead th {
              background: var(--secondary-bg);
              border-color: var(--border-color);
              color: var(--text-primary);
              font-weight: 600;
              padding: 1rem;
              border-bottom: 2px solid var(--border-color);
          }

          .admin-content .table tbody td {
              background: var(--card-bg);
              border-color: var(--border-color);
              color: var(--text-primary);
              padding: 1rem;
              vertical-align: middle;
          }

          .admin-content .table tbody tr:hover {
              background: var(--hover-bg);
          }

          .admin-content .table tbody tr:hover td {
              background: var(--hover-bg);
          }

          /* Form Controls */
          .admin-content .form-control,
          .admin-content .form-select {
              background: var(--secondary-bg);
              border: 1px solid var(--border-color);
              border-radius: 8px;
              color: var(--text-primary);
              padding: 0.75rem 1rem;
              transition: all 0.3s ease;
          }

          .admin-content .form-control:focus,
          .admin-content .form-select:focus {
              background: var(--card-bg);
              border-color: var(--accent-purple);
              box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
              color: var(--text-primary);
          }

          .admin-content .form-control::placeholder {
              color: var(--text-secondary);
          }

          /* Buttons */
          .admin-content .btn-outline-primary {
              color: var(--accent-purple);
              border-color: var(--accent-purple);
              background: transparent;
              border-radius: 8px;
              padding: 0.5rem 1rem;
              font-weight: 500;
              transition: all 0.3s ease;
          }

          .admin-content .btn-outline-primary:hover {
              background: var(--accent-purple);
              border-color: var(--accent-purple);
              color: white;
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
          }

          .admin-content .btn-primary {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              border: none;
              border-radius: 8px;
              padding: 0.75rem 1.5rem;
              font-weight: 500;
              transition: all 0.3s ease;
          }

          .admin-content .btn-primary:hover {
              background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
          }

          .admin-content .btn-success {
              background: linear-gradient(135deg, #10b981 0%, #059669 100%);
              border: none;
              border-radius: 8px;
              padding: 0.75rem 1.5rem;
              font-weight: 500;
              transition: all 0.3s ease;
          }

          .admin-content .btn-success:hover {
              background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
          }

          .admin-content .btn-danger {
              background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
              border: none;
              border-radius: 8px;
              padding: 0.75rem 1.5rem;
              font-weight: 500;
              transition: all 0.3s ease;
          }

          .admin-content .btn-danger:hover {
              background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
          }

          .admin-content .btn-warning {
              background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
              border: none;
              border-radius: 8px;
              padding: 0.75rem 1.5rem;
              font-weight: 500;
              color: white;
              transition: all 0.3s ease;
          }

          .admin-content .btn-warning:hover {
              background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
              color: white;
          }

          .admin-content .btn-sm {
              padding: 0.375rem 0.75rem;
              font-size: 0.875rem;
              border-radius: 6px;
          }

          .admin-content .btn-lg {
              padding: 0.875rem 2rem;
              font-size: 1.125rem;
              border-radius: 10px;
          }

          /* Pagination */
          .admin-content .pagination .page-link {
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              color: var(--text-primary);
              padding: 0.5rem 0.75rem;
              margin: 0 0.125rem;
              border-radius: 6px;
              transition: all 0.3s ease;
          }

          .admin-content .pagination .page-link:hover {
              background: var(--hover-bg);
              border-color: var(--accent-purple);
              color: var(--text-primary);
          }

          .admin-content .pagination .page-item.active .page-link {
              background: var(--accent-purple);
              border-color: var(--accent-purple);
              color: white;
          }

          /* Badges */
          .admin-content .badge {
              border-radius: 6px;
              padding: 0.375rem 0.75rem;
              font-weight: 500;
              font-size: 0.75rem;
          }

          .admin-content .badge.bg-success {
              background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
          }

          .admin-content .badge.bg-danger {
              background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
          }

          .admin-content .badge.bg-warning {
              background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
              color: white !important;
          }

          .admin-content .badge.bg-primary {
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
          }

          /* Modals */
          .admin-content .modal-content {
              background: var(--card-bg);
              border: 1px solid var(--border-color);
              border-radius: 12px;
          }

          .admin-content .modal-header {
              border-bottom: 1px solid var(--border-color);
              background: var(--secondary-bg);
              border-radius: 12px 12px 0 0;
          }

          .admin-content .modal-title {
              color: var(--text-primary);
          }

          .admin-content .modal-body {
              background: var(--card-bg);
              color: var(--text-primary);
          }

          .admin-content .modal-footer {
              border-top: 1px solid var(--border-color);
              background: var(--secondary-bg);
              border-radius: 0 0 12px 12px;
          }

          /* Alerts */
          .admin-content .alert {
              border: 1px solid var(--border-color);
              border-radius: 10px;
              padding: 1rem 1.25rem;
              margin-bottom: 1rem;
          }

          .admin-content .alert-success {
              background: rgba(16, 185, 129, 0.1);
              border-color: rgba(16, 185, 129, 0.3);
              color: #10b981;
          }

          .admin-content .alert-danger {
              background: rgba(239, 68, 68, 0.1);
              border-color: rgba(239, 68, 68, 0.3);
              color: #ef4444;
          }

          .admin-content .alert-warning {
              background: rgba(245, 158, 11, 0.1);
              border-color: rgba(245, 158, 11, 0.3);
              color: #f59e0b;
          }

          .admin-content .alert-info {
              background: rgba(59, 130, 246, 0.1);
              border-color: rgba(59, 130, 246, 0.3);
              color: #3b82f6;
          }

          /* Checkboxes */
          .admin-content .form-check-input {
              background: var(--secondary-bg);
              border: 1px solid var(--border-color);
              border-radius: 4px;
          }

          .admin-content .form-check-input:checked {
              background: var(--accent-purple);
              border-color: var(--accent-purple);
          }

          .admin-content .form-check-input:focus {
              box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
          }

          .admin-content .form-check-label {
              color: var(--text-primary);
          }
    </style>
    <!-- Mobile-only CSS (loaded AFTER custom styles to properly override them) -->
    <link rel="stylesheet" href="mobile.css" media="(max-width: 576px)">
</head>
<body>
    
    <!-- Navbar -->
    <?php
    // Calcolo robusto della pagina corrente basato sulla REQUEST_URI,
    // così da gestire correttamente le URL "pulite" (/forum, /annunci, /)
    $request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!$request_path) { $request_path = '/'; }

    // Flag delle sezioni attive
    $is_forum   = (bool)preg_match('#^/(forum|forum\.php)/?$#i', $request_path);
    $is_annunci = (bool)preg_match('#^/(annunci|annunci\.php)/?$#i', $request_path);
    // Home/Lista Server: root, index.php o pagina server dettagli
    $is_home    = $request_path === '/'
               || (bool)preg_match('#^/index\.php$#i', $request_path)
               || (bool)preg_match('#^/server/#i', $request_path);
    // Pagine di autenticazione
    $is_login   = (bool)preg_match('#^/(login|login\.php)/?$#i', $request_path);
    $is_register= (bool)preg_match('#^/(register|register\.php)/?$#i', $request_path);

    // Nickname Minecraft verificato per l'avatar in navbar
    $header_verified_nick = null;
    if (isLoggedIn()) {
        try {
            $stmt = $pdo->prepare("SELECT minecraft_nick FROM sl_minecraft_links WHERE user_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $header_verified_nick = $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            $header_verified_nick = null;
        }
    }
    ?>
    <nav class="navbar navbar-expand-lg navbar-mc">
        <div class="container">
                        <a class="navbar-brand" href="/">
                            <i class="bi bi-boxes"></i> Blocksy
                        </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_forum ? 'active' : ''; ?>" href="/forum">
                            <i class="bi bi-chat-dots"></i> Forum
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_annunci ? 'active' : ''; ?>" href="/annunci">
                            <i class="bi bi-megaphone"></i> Annunci
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_home ? 'active' : ''; ?>" href="/">
                            <i class="bi bi-list-ul"></i> Lista Server
                        </a>
                    </li>

                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // Conta messaggi non letti
                        $unread_messages = 0;
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sl_messages WHERE to_user_id = ? AND is_read = 0");
                            $stmt->execute([$_SESSION['user_id']]);
                            $unread_messages = (int)$stmt->fetchColumn();
                        } catch (PDOException $e) {
                            // Ignora errore se tabella non esiste ancora
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="/messages" title="Messaggi">
                                <i class="bi bi-envelope"></i>
                                <?php if ($unread_messages > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                        <?php echo $unread_messages; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button class="nav-link" type="button" onclick="toggleTheme()" title="Toggle tema">
                            <i id="themeToggleIcon" class="bi bi-moon-stars"></i>
                            Tema
                        </button>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo !empty($header_verified_nick) ? getMinecraftAvatar($header_verified_nick, 32) : '/logo.png'; ?>" 
                                 alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <?php echo htmlspecialchars($_SESSION['minecraft_nick']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profile">
                                <i class="bi bi-person"></i> Profilo
                            </a></li>
                            <li><a class="dropdown-item" href="/utente/<?php echo (int)$_SESSION['user_id']; ?>">
                                <i class="bi bi-person-badge"></i> Profilo Pubblico
                            </a></li>
                            <li><a class="dropdown-item" href="/eventi-server">
                                <i class="bi bi-calendar-event"></i> Gestione Eventi
                            </a></li>
                            <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="/admin">
                                <i class="bi bi-gear-fill"></i> Admin Dashboard
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_login ? 'active' : ''; ?>" href="/login">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_register ? 'active' : ''; ?>" href="/register">
                            <i class="bi bi-person-plus"></i> Registrati
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Toast Container for notifications -->
    <div class="toast-container"></div>
    
    <!-- Main Content -->
    <main>