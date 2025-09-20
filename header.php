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
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        
        /* Main container */
        .container {
            max-width: 1400px;
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--gradient-primary);
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Server list header */
        .server-list-header {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
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
            gap: 1rem;
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
            background: var(--card-bg);
            border-radius: 0 0 16px 16px;
            overflow: hidden;
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
        
        .server-logo {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
        
        .server-name:hover {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            margin-bottom: 1.5rem;
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
            background: var(--gradient-secondary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .clear-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                max-width: 100%;
                padding: 0 2rem;
            }
        }
        
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
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
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .server-card {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
                padding: 2rem 1rem;
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
            
            .hero-section {
                padding: 2rem 0;
            }
            
            .hero-title {
                font-size: 1.75rem;
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
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
        }
        
        .dropdown-item {
            color: var(--text-light);
        }
        
        .dropdown-item:hover {
            background-color: var(--accent-green);
            color: white;
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
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
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-mc">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-boxes"></i> Blocksy
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-list-ul"></i> Lista Server
                        </a>
                    </li>
                    <?php if (isLoggedIn() && isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo getMinecraftAvatar($_SESSION['minecraft_nick'], 32); ?>" 
                                 alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <?php echo htmlspecialchars($_SESSION['minecraft_nick']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> Profilo
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
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