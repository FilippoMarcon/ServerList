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
    <link rel="stylesheet" href="assets/css/improvements.css">
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
        
        /* Main container */
        .container {
            max-width: 1400px;
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
            border: 2px solid;
            border-image: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) 1 !important;
            color: var(--text-primary) !important;
            box-shadow: none !important;
            border-radius: 12px;
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
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            z-index: 9999 !important;
            padding: 0.5rem;
            margin-top: 0.5rem;
            backdrop-filter: blur(20px);
            min-width: 200px;
        }
        
        .dropdown-item {
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background: var(--gradient-primary);
            color: white;
            transform: translateX(4px);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
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
            width: 120px;
            height: 120px;
            border-radius: 20px;
            border: 4px solid var(--border-color);
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
        
        .btn-view-server, .btn-edit-server {
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
        
        .btn-view-server:hover, .btn-edit-server:hover {
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
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
        }
        
        @media (max-width: 768px) {
            .server-card-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .server-actions {
                justify-content: center;
            }
            
            .profile-header-card {
                padding: 1.5rem;
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
            margin-top: 1rem;
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
        }
        
        .server-tag-modern {
            background: var(--primary-bg);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid var(--border-color);
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
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'active' : ''; ?>" href="forum.php">
                            <i class="bi bi-chat-dots"></i> Forum
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'annunci.php' ? 'active' : ''; ?>" href="annunci.php">
                            <i class="bi bi-megaphone"></i> Annunci
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-list-ul"></i> Lista Server
                        </a>
                    </li>

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
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="admin.php">
                                <i class="bi bi-gear-fill"></i> Admin Dashboard
                            </a></li>
                            <?php endif; ?>
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