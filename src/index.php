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
        $db_status_pdo = 'Connected successfully';
        
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
        $db_status_mysqli = 'Connected successfully';
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #080c14;
            --bg-card: rgba(15, 23, 42, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --accent-glow: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
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
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: var(--accent-glow);
            filter: blur(140px);
            opacity: 0.12;
            z-index: -1;
        }
        body::before {
            top: -100px;
            left: -100px;
        }
        body::after {
            bottom: 200px;
            right: -100px;
        }

        /* Top System Status Bar */
        .system-top-bar {
            background: rgba(10, 15, 30, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
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

        /* Main Container */
        .main-wrapper {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Main Header Card */
        .header-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-card h1 {
            font-size: 2.4rem;
            font-weight: 700;
            background: var(--accent-glow);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header-card p {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 300;
            margin-bottom: 1.5rem;
        }

        /* Floating Toolbar */
        .toolbar-group {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .toolbar-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.15);
        }

        /* Tabs Interface */
        .tabs-header {
            display: flex;
            background: rgba(10, 15, 30, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0.4rem;
            margin-bottom: 2rem;
            gap: 0.25rem;
        }

        .tab-trigger {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0.8rem 1rem;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .tab-trigger:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.02);
        }

        .tab-trigger.active {
            color: #ffffff;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Container Cards */
        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border-left: 4px solid #818cf8;
            padding-left: 0.75rem;
        }

        /* Projects Tab Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .project-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .project-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .project-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .project-icon-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .project-folder-icon {
            font-size: 1.8rem;
        }

        .project-badges {
            display: flex;
            gap: 0.4rem;
        }

        .badge-git {
            background: rgba(240, 80, 51, 0.15);
            border: 1px solid rgba(240, 80, 51, 0.3);
            color: #f05033;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-vscode {
            background: rgba(0, 122, 204, 0.15);
            border: 1px solid rgba(0, 122, 204, 0.3);
            color: #007acc;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .project-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-main);
            word-break: break-all;
        }

        .project-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .project-actions {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .proj-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .proj-nginx {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .proj-nginx:hover {
            background: #10b981;
            color: white;
            border-color: transparent;
        }

        .proj-apache {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .proj-apache:hover {
            background: #f59e0b;
            color: white;
            border-color: transparent;
        }

        .no-projects {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            border: 1px dashed var(--border-color);
            border-radius: 20px;
            color: var(--text-muted);
        }

        /* Diagnostics Tab Styles */
        .diag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .diag-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
        }

        .diag-card-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .diag-card-value {
            font-size: 1.35rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-active { background-color: var(--status-success); box-shadow: 0 0 10px var(--status-success); }
        .dot-error { background-color: var(--status-error); box-shadow: 0 0 10px var(--status-error); }

        .diagnostics-endpoints {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
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
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .endpoint-row:last-child {
            border-bottom: none;
        }

        /* Debug Tab Styles */
        .debug-instructions {
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .debug-instructions ol {
            margin-left: 1.5rem;
            margin-top: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .debug-instructions code {
            background: rgba(0, 0, 0, 0.4);
            color: #f472b6;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .debug-tester-box {
            background: rgba(99, 102, 241, 0.04);
            border: 1px dashed rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .debug-status-pill {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .pill-active { background: rgba(16, 185, 129, 0.15); color: var(--status-success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .pill-inactive { background: rgba(239, 68, 68, 0.15); color: var(--status-error); border: 1px solid rgba(239, 68, 68, 0.3); }

        .btn-trigger {
            display: inline-block;
            background: linear-gradient(90deg, #6366f1, #a855f7);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .btn-trigger:hover {
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.5);
            opacity: 0.95;
            transform: scale(1.02);
        }

        .debug-output {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.2);
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
        
        <!-- 2. Header Card -->
        <div class="header-card">
            <h1>Modern Developer Stack</h1>
            <p>A fast, decoupled, microservices local suite tailored for modern web apps.</p>
            
            <div class="toolbar-group">
                <a href="http://localhost:8804/" target="_blank" class="toolbar-btn">
                    <span>📂 phpMyAdmin</span>
                </a>
                <a href="http://localhost:8805/" target="_blank" class="toolbar-btn">
                    <span>✉️ Mailpit Panel</span>
                </a>
                <a href="?phpinfo=1" onclick="window.open('?phpinfo=true', 'PHPInfo', 'width=800,height=600'); return false;" class="toolbar-btn">
                    <span>⚙️ phpinfo()</span>
                </a>
            </div>
        </div>

        <!-- 3. Tabs Header Navigation -->
        <div class="tabs-header">
            <button class="tab-trigger active" onclick="switchTab(event, 'tab-projects')">
                📁 My Projects (<?php echo count($projects); ?>)
            </button>
            <button class="tab-trigger" onclick="switchTab(event, 'tab-diagnostics')">
                ⚙️ Status & Diagnostics
            </button>
            <button class="tab-trigger" onclick="switchTab(event, 'tab-debug')">
                🐛 Xdebug Guide
            </button>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 1: Projects Navigator -->
        <!-- =================================================================== -->
        <div id="tab-projects" class="tab-content active">
            <div class="section-card">
                <h2 class="section-title">First-Level Directory Projects</h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem; font-weight: 300;">
                    Below are the subfolders dynamically scanned inside your local <code>src/</code> mount. Click any server button to open the project instantly.
                </p>
                
                <div class="projects-grid">
                    <?php if (empty($projects)): ?>
                        <div class="no-projects">
                            📁 No subprojects found inside <code>src/</code> yet.<br>
                            <span style="font-size: 0.9rem; font-weight: 300; display: inline-block; margin-top: 0.5rem; color: #888;">
                                Create directories (e.g. <code>src/my-web/</code>) or clone git repos to see them listed here instantly!
                            </span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <div class="project-card">
                                <div>
                                    <div class="project-header">
                                        <div class="project-icon-group">
                                            <span class="project-folder-icon">📂</span>
                                            <span class="project-name"><?php echo htmlspecialchars($project['name']); ?></span>
                                        </div>
                                        <div class="project-badges">
                                            <?php if ($project['is_git']): ?>
                                                <span class="badge-git">GIT</span>
                                            <?php endif; ?>
                                            <?php if ($project['has_vscode']): ?>
                                                <span class="badge-vscode">VSCODE</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="project-meta">Last modified: <?php echo htmlspecialchars($project['updated_time']); ?></div>
                                </div>

                                <div class="project-actions">
                                    <a href="./<?php echo urlencode($project['name']); ?>/" class="proj-link proj-nginx" target="_blank">
                                        ⚡ Nginx (8801)
                                    </a>
                                    <a href="http://localhost:8802/<?php echo urlencode($project['name']); ?>/" class="proj-link proj-apache" target="_blank">
                                        🦅 Apache (8802)
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 2: Diagnostics & Status -->
        <!-- =================================================================== -->
        <div id="tab-diagnostics" class="tab-content">
            <div class="section-card">
                <h2 class="section-title">Environment Diagnostics</h2>
                
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
                        <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.95rem; font-weight: 600;">
                            <div>
                                <span class="status-dot <?php echo $db_status_pdo === 'Connected successfully' ? 'dot-active' : 'dot-error'; ?>"></span>
                                PDO Link: <?php echo htmlspecialchars($db_status_pdo); ?>
                            </div>
                            <div>
                                <span class="status-dot <?php echo $db_status_mysqli === 'Connected successfully' ? 'dot-active' : 'dot-error'; ?>"></span>
                                MySQLi Link: <?php echo htmlspecialchars($db_status_mysqli); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($db_error)): ?>
                    <div class="db-err-box" style="margin-bottom: 2rem;">
                        🚨 <strong>Database connection reports:</strong><br>
                        <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>

                <h3 class="section-title" style="font-size: 1.15rem; margin-top: 2rem;">Address Mapping</h3>
                <div class="diagnostics-endpoints">
                    <div class="endpoints-grid">
                        <div>
                            <h4 style="margin-bottom: 0.5rem; font-size: 0.95rem; color: #818cf8;">⚡ Nginx Local Ports</h4>
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
                            <h4 style="margin-bottom: 0.5rem; font-size: 0.95rem; color: #fbbf24;">🦅 Apache Local Ports</h4>
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
                <h2 class="section-title">VS Code Xdebug Step-by-Step Testing</h2>
                
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
