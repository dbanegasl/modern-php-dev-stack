<?php
// ==============================================================================
# Modern PHP Dev Environment - Premium Status Dashboard & Orchestrator
// ==============================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to read docker secrets securely
function get_db_password() {
    $password_file = getenv('DB_PASSWORD_FILE');
    if ($password_file && file_exists($password_file)) {
        return trim(file_get_contents($password_file));
    }
    return null;
}

// 1. Diagnostics information
$php_version = PHP_VERSION;
$xdebug_active = extension_loaded('xdebug');
$xdebug_mode = ini_get('xdebug.mode');

// 2. Database Connection Test & Version Query
$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_DATABASE') ?: 'my_app_db';
$db_user = getenv('DB_USER') ?: 'my_app_user';
$db_password = get_db_password();

$db_status_pdo = 'Pending';
$db_status_mysqli = 'Pending';
$mariadb_version = 'Offline';
$db_error = '';

if ($db_password === null) {
    $db_status_pdo = 'Error: Secret File Not Found';
    $db_status_mysqli = 'Error: Secret File Not Found';
} else {
    // Test PDO Connection & query MariaDB version
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $db_status_pdo = 'Connected';
        
        // Dynamically fetch active database server version
        $mariadb_version = $pdo->query('SELECT @@version')->fetchColumn();
        if (preg_match('/mariadb/i', $mariadb_version)) {
            $mariadb_version = preg_replace('/-mariadb.*/i', ' (MariaDB)', $mariadb_version);
        }
    } catch (PDOException $e) {
        $db_status_pdo = 'Failed';
        $db_error .= "PDO Error: " . $e->getMessage() . " | ";
    }

    // Test MySQLi Connection
    try {
        $conn = @new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        $db_status_mysqli = 'Connected';
        $conn->close();
    } catch (Exception $e) {
        $db_status_mysqli = 'Failed';
        $db_error .= "MySQLi Error: " . $e->getMessage();
    }
}

// 3. Xdebug Breakpoint Test Trigger
$debug_triggered = false;
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    $debug_triggered = true;
    
    // --- BREAKPOINT TRIGGER ---
    // If you have configured VS Code and pressed F5, place a breakpoint on the line below!
    $test_variable = "Xdebug Connection Working Successfully!";
    $test_array = ['status' => 'debugging', 'time' => time()];
    // --------------------------
}

// 4. Server Port & SSL Detection
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$protocol_label = $is_https ? 'HTTPS 🔒' : 'HTTP 🔓';

$web_server_name = 'Unknown';
if (preg_match('/nginx/i', $server_software)) {
    $web_server_name = 'Nginx';
} elseif (preg_match('/apache/i', $server_software) || preg_match('/httpd/i', $server_software)) {
    $web_server_name = 'Apache';
}

// 5. Dynamic Project Directories Scanner
$projects = [];
$dir = __DIR__;
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($dir . '/' . $file)) {
            // Check if there is a git repo inside the project
            $is_git = is_dir($dir . '/' . $file . '/.git');
            $has_vscode = is_dir($dir . '/' . $file . '/.vscode');
            
            $projects[] = [
                'name' => $file,
                'is_git' => $is_git,
                'has_vscode' => $has_vscode,
                'updated_time' => date('Y-m-d H:i', filemtime($dir . '/' . $file))
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern PHP Dev Suite Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #060911;
            --bg-card: rgba(13, 20, 35, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --accent-glow: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --text-main: #f3f4f6;
            --text-muted: #8e96a3;
            --status-success: #10b981;
            --status-error: #ef4444;
            --status-warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-main);
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Backgrounds */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: var(--accent-glow);
            filter: blur(140px);
            opacity: 0.1;
            z-index: -1;
        }
        body::before {
            top: -100px;
            left: -100px;
        }
        body::after {
            bottom: 100px;
            right: -100px;
        }

        /* Top System Status Bar */
        .system-top-bar {
            background: rgba(8, 12, 22, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 0.6rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .sys-info-group {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .sys-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-muted);
        }

        .sys-badge strong {
            color: var(--text-main);
            font-weight: 600;
        }

        /* Main Wrapper */
        .main-wrapper {
            max-width: 960px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        /* Clean Header Layout (No heavy card) */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--accent-glow);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 300;
            margin-top: 0.2rem;
        }

        /* Compact Toolbar */
        .toolbar {
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            padding: 0.35rem;
            border-radius: 12px;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            background: transparent;
            border: none;
            color: var(--text-main);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .toolbar-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #818cf8;
        }

        /* Tabs Interface */
        .tabs-header {
            display: flex;
            background: rgba(10, 15, 30, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 0.3rem;
            margin-bottom: 1.5rem;
            gap: 0.2rem;
        }

        .tab-trigger {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0.6rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .tab-trigger:hover {
            color: var(--text-main);
        }

        .tab-trigger.active {
            color: #ffffff;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Compact Containers */
        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* Finder-Style List View (Projects Tab) */
        .finder-container {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.15);
            margin-top: 1rem;
        }

        .finder-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .finder-table th {
            text-align: left;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.6rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .finder-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }

        .finder-row {
            transition: background 0.15s ease;
        }

        .finder-row:hover {
            background: rgba(255, 255, 255, 0.015);
        }

        .finder-row:last-child td {
            border-bottom: none;
        }

        .finder-folder {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 500;
        }

        .finder-folder-icon {
            font-size: 1.3rem;
        }

        .finder-name {
            color: var(--text-main);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .finder-name:hover {
            color: #818cf8;
        }

        .badge-pill {
            display: inline-flex;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            margin-right: 0.3rem;
        }

        .badge-git {
            background: rgba(240, 80, 51, 0.12);
            border: 1px solid rgba(240, 80, 51, 0.25);
            color: #f05033;
        }

        .badge-vscode {
            background: rgba(0, 122, 204, 0.12);
            border: 1px solid rgba(0, 122, 204, 0.25);
            color: #38bdf8;
        }

        .finder-date {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Compact Action Pills */
        .launch-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            text-decoration: none;
            width: 80px;
            transition: all 0.2s ease;
        }

        .launch-nginx {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .launch-nginx:hover {
            background: rgba(16, 185, 129, 0.2);
            transform: translateY(-1px);
        }

        .launch-apache {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .launch-apache:hover {
            background: rgba(245, 158, 11, 0.2);
            transform: translateY(-1px);
        }

        .no-projects {
            text-align: center;
            padding: 2.5rem;
            color: var(--text-muted);
            font-weight: 300;
        }

        /* Diagnostics Tab Styles */
        .diag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .diag-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1.25rem;
        }

        .diag-card-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .diag-card-value {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .diagnostics-endpoints {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .endpoints-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 600px) {
            .endpoints-grid {
                grid-template-columns: 1fr;
            }
        }

        .endpoint-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            font-size: 0.85rem;
        }

        .endpoint-row:last-child {
            border-bottom: none;
        }

        /* Debug Tab Styles */
        .debug-instructions {
            line-height: 1.5;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .debug-instructions ol {
            margin-left: 1.25rem;
            margin-top: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .debug-instructions code {
            background: rgba(0, 0, 0, 0.4);
            color: #f472b6;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .debug-tester-box {
            background: rgba(99, 102, 241, 0.03);
            border: 1px dashed rgba(99, 102, 241, 0.25);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .debug-status-pill {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.3rem;
        }

        .btn-trigger {
            display: inline-block;
            background: linear-gradient(90deg, #6366f1, #a855f7);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            box-shadow: 0 3px 8px rgba(99, 102, 241, 0.2);
        }

        .btn-trigger:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .debug-output {
            margin-top: 1rem;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8rem;
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.15);
        }

        .db-err-box {
            grid-column: 1 / -1;
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            padding: 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: monospace;
        }

        /* Local Pride Footer */
        .footer {
            text-align: center;
            margin-top: 3rem;
            margin-bottom: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 300;
        }

        .footer-link {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.15s ease;
        }

        .footer-link:hover {
            color: #a855f7;
            text-shadow: 0 0 8px rgba(168, 85, 247, 0.3);
        }

        .heart-icon {
            color: #ef4444;
            display: inline-block;
            animation: beat 1.2s infinite alternate;
        }

        @keyframes beat {
            to { transform: scale(1.15); }
        }
    </style>
</head>
<body>

    <!-- 1. Top System Status Bar -->
    <div class="system-top-bar">
        <div class="sys-info-group">
            <div class="sys-badge">🛡️ Web Server: <strong><?php echo htmlspecialchars($web_server_name); ?></strong></div>
            <div class="sys-badge">⚡ PHP Version: <strong>v<?php echo htmlspecialchars($php_version); ?></strong></div>
            <div class="sys-badge">🗃️ Database: <strong><?php echo htmlspecialchars($mariadb_version); ?></strong></div>
        </div>
        <div class="sys-badge">Protocol: <span style="font-weight: 700; color: <?php echo $is_https ? '#34d399' : '#fbbf24'; ?>;"><?php echo $protocol_label; ?></span></div>
    </div>

    <div class="main-wrapper">
        
        <!-- 2. Page Header & Toolbar -->
        <div class="page-header">
            <div class="page-title">
                <h1>Modern Developer Stack</h1>
                <p>Decoupled microservices dev environment</p>
            </div>
            
            <div class="toolbar">
                <a href="http://localhost:8804/" target="_blank" class="toolbar-btn">📂 phpMyAdmin</a>
                <a href="http://localhost:8805/" target="_blank" class="toolbar-btn">✉️ Mailpit</a>
                <a href="?phpinfo=1" onclick="window.open('?phpinfo=true', 'PHPInfo', 'width=800,height=600'); return false;" class="toolbar-btn">⚙️ phpinfo()</a>
            </div>
        </div>

        <!-- 3. Tabs Navigation -->
        <div class="tabs-header">
            <button class="tab-trigger active" onclick="switchTab(event, 'tab-projects')">
                📁 My Projects (<?php echo count($projects); ?>)
            </button>
            <button class="tab-trigger" onclick="switchTab(event, 'tab-diagnostics')">
                ⚙️ Diagnostics
            </button>
            <button class="tab-trigger" onclick="switchTab(event, 'tab-debug')">
                🐛 Xdebug Guide
            </button>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 1: Projects Explorer (Finder-style) -->
        <!-- =================================================================== -->
        <div id="tab-projects" class="tab-content active">
            <div class="section-card">
                <div class="page-title">
                    <h2 style="font-size: 1.25rem; font-weight: 600;">First-Level Directory Projects</h2>
                    <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 300; margin-top: 0.15rem;">
                        Subfolders dynamically scanned inside your local <code>src/</code> mount. Click a server pill to launch.
                    </p>
                </div>
                
                <div class="finder-container">
                    <?php if (empty($projects)): ?>
                        <div class="no-projects">
                            📁 No subprojects found inside <code>src/</code> yet.<br>
                            <span style="font-size: 0.8rem; font-weight: 300; display: inline-block; margin-top: 0.4rem; color: #777;">
                                Create directories or clone git repos to see them listed here instantly!
                            </span>
                        </div>
                    <?php else: ?>
                        <table class="finder-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Tags</th>
                                    <th>Last Modified</th>
                                    <th style="width: 100px; text-align: center;">Nginx (8801)</th>
                                    <th style="width: 100px; text-align: center;">Apache (8802)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr class="finder-row">
                                        <td>
                                            <div class="finder-folder">
                                                <span class="finder-folder-icon">📂</span>
                                                <a href="./<?php echo urlencode($project['name']); ?>/" class="finder-name" target="_blank">
                                                    <?php echo htmlspecialchars($project['name']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($project['is_git']): ?>
                                                <span class="badge-pill badge-git">GIT</span>
                                            <?php endif; ?>
                                            <?php if ($project['has_vscode']): ?>
                                                <span class="badge-pill badge-vscode">VSCODE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="finder-date"><?php echo htmlspecialchars($project['updated_time']); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="./<?php echo urlencode($project['name']); ?>/" class="launch-pill launch-nginx" target="_blank" title="Open with Nginx HTTP (Port 8801)">
                                                ⚡ Open
                                            </a>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="http://localhost:8802/<?php echo urlencode($project['name']); ?>/" class="launch-pill launch-apache" target="_blank" title="Open with Apache HTTP (Port 8802)">
                                                🦅 Open
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 2: Diagnostics & Status -->
        <!-- =================================================================== -->
        <div id="tab-diagnostics" class="tab-content">
            <div class="section-card">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.2rem; border-left: 3px solid #818cf8; padding-left: 0.6rem;">Environment Status</h2>
                
                <div class="diag-grid">
                    <!-- PHP Version -->
                    <div class="diag-card">
                        <div class="diag-card-title">PHP Runtime</div>
                        <div class="diag-card-value">
                            <span class="status-dot dot-active"></span>
                            PHP <?php echo htmlspecialchars($php_version); ?>
                        </div>
                    </div>

                    <!-- Xdebug Status -->
                    <div class="diag-card">
                        <div class="diag-card-title">Xdebug Engine</div>
                        <div class="diag-card-value">
                            <span class="status-dot <?php echo $xdebug_active ? 'dot-active' : 'dot-error'; ?>"></span>
                            <?php echo $xdebug_active ? 'Active (' . htmlspecialchars($xdebug_mode) . ')' : 'Inactive'; ?>
                        </div>
                    </div>

                    <!-- MariaDB Connection -->
                    <div class="diag-card">
                        <div class="diag-card-title">Database Connections</div>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.85rem; font-weight: 600;">
                            <div>
                                <span class="status-dot <?php echo $db_status_pdo === 'Connected' ? 'dot-active' : 'dot-error'; ?>"></span>
                                PDO: <?php echo htmlspecialchars($db_status_pdo); ?>
                            </div>
                            <div>
                                <span class="status-dot <?php echo $db_status_mysqli === 'Connected' ? 'dot-active' : 'dot-error'; ?>"></span>
                                MySQLi: <?php echo htmlspecialchars($db_status_mysqli); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($db_error)): ?>
                    <div class="db-err-box" style="margin-bottom: 1.5rem;">
                        🚨 <strong>Database connection reports:</strong><br>
                        <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>

                <h3 style="font-size: 1.05rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.8rem; border-left: 3px solid #818cf8; padding-left: 0.6rem;">Address Mapping</h3>
                <div class="diagnostics-endpoints">
                    <div class="endpoints-grid">
                        <div>
                            <h4 style="margin-bottom: 0.4rem; font-size: 0.85rem; color: #34d399; font-weight: 600;">⚡ Nginx Local Ports</h4>
                            <div class="endpoint-row">
                                <span>HTTP standard</span>
                                <a href="http://localhost:8801/" target="_blank" style="color: #34d399; font-weight: 600; text-decoration: none;">http://localhost:8801</a>
                            </div>
                            <div class="endpoint-row">
                                <span>HTTPS secure</span>
                                <a href="https://localhost:8811/" target="_blank" style="color: #34d399; font-weight: 600; text-decoration: none;">https://localhost:8811</a>
                            </div>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.4rem; font-size: 0.85rem; color: #fbbf24; font-weight: 600;">🦅 Apache Local Ports</h4>
                            <div class="endpoint-row">
                                <span>HTTP standard</span>
                                <a href="http://localhost:8802/" target="_blank" style="color: #fbbf24; font-weight: 600; text-decoration: none;">http://localhost:8802</a>
                            </div>
                            <div class="endpoint-row">
                                <span>HTTPS secure</span>
                                <a href="https://localhost:8812/" target="_blank" style="color: #fbbf24; font-weight: 600; text-decoration: none;">https://localhost:8812</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 3: Debug Guide -->
        <!-- =================================================================== -->
        <div id="tab-debug" class="tab-content">
            <div class="section-card">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.2rem; border-left: 3px solid #818cf8; padding-left: 0.6rem;">VS Code Xdebug Step-by-Step Testing</h2>
                
                <div class="debug-instructions">
                    <p>To verify that live, interactive debugging is fully operational:</p>
                    <ol>
                        <li>Ensure you have the <strong>PHP Debug</strong> extension installed in VS Code.</li>
                        <li>Open the specific project subfolder in VS Code (e.g. <code>src/prueba-debug</code>).</li>
                        <li>Open its <code>index.php</code> and set a breakpoint on the executable lines (e.g. line 16).</li>
                        <li>Press <kbd>F5</kbd> (and select the corresponding <strong>"Listen for Xdebug (prueba-debug)"</strong> profile).</li>
                        <li>Click the trigger button below to run the test script. VS Code should instantly pause execution!</li>
                    </ol>
                </div>

                <div class="debug-tester-box">
                    <div>
                        <div>Xdebug Status:</div>
                        <span class="debug-status-pill <?php echo $xdebug_active ? 'pill-active' : 'pill-inactive'; ?>">
                            <?php echo $xdebug_active ? '✅ Ready to debug' : '❌ Extension not loaded'; ?>
                        </span>
                    </div>
                    <a href="?debug=1" class="btn-trigger">⚡ Trigger Breakpoint Test</a>
                </div>

                <?php if ($debug_triggered): ?>
                    <div class="debug-output">
                        🎉 Breakpoint check executed! If VS Code was listening, it paused here. Variable $test_variable contents: "<?php echo htmlspecialchars($test_variable); ?>"
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Local Pride Footer -->
        <footer class="footer">
            Hecho en Cuenca con <span class="heart-icon">❤️</span> por <a href="https://duotics.com" target="_blank" class="footer-link">DUOTICS</a>
        </footer>

    </div>

    <!-- JavaScript Tab Switcher -->
    <script>
        function switchTab(evt, tabId) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Remove active class from all tab triggers
            const tabTriggers = document.getElementsByClassName("tab-trigger");
            for (let i = 0; i < tabTriggers.length; i++) {
                tabTriggers[i].classList.remove("active");
            }

            // Show active tab content and add active class to trigger button
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>

    <?php
    // Inline phpinfo overlay handler
    if (isset($_GET['phpinfo'])) {
        ob_clean();
        phpinfo();
        exit;
    }
    ?>
</body>
</html>
