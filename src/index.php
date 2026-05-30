<?php
// ==============================================================================
# Modern PHP Dev Environment - Status Dashboard & Diagnostics
// ==============================================================================

// Error reporting settings
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

// 2. Database Connection Test
$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_DATABASE') ?: 'my_app_db';
$db_user = getenv('DB_USER') ?: 'my_app_user';
$db_password = get_db_password();

$db_status_pdo = 'Pending';
$db_status_mysqli = 'Pending';
$db_error = '';

if ($db_password === null) {
    $db_status_pdo = 'Error: Secret File Not Found';
    $db_status_mysqli = 'Error: Secret File Not Found';
} else {
    // Test PDO Connection
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $db_status_pdo = 'Connected successfully';
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
    $web_server_name = 'Nginx (' . ($is_https ? 'Port: 8811' : 'Port: 8801') . ')';
} elseif (preg_match('/apache/i', $server_software) || preg_match('/httpd/i', $server_software)) {
    $web_server_name = 'Apache (' . ($is_https ? 'Port: 8812' : 'Port: 8802') . ')';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern PHP Dev Environment Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-card: rgba(18, 26, 44, 0.6);
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Backgrounds */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: var(--accent-glow);
            filter: blur(120px);
            opacity: 0.15;
            z-index: -1;
        }
        body::before {
            top: -100px;
            left: -100px;
        }
        body::after {
            bottom: -100px;
            right: -100px;
        }

        .dashboard-container {
            width: 100%;
            max-width: 900px;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--accent-glow);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .badges-wrapper {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .badge-software {
            display: inline-flex;
            align-items: center;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #818cf8;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-protocol {
            display: inline-flex;
            align-items: center;
            background: <?php echo $is_https ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)'; ?>;
            border: 1px solid <?php echo $is_https ? 'rgba(16, 185, 129, 0.3)' : 'rgba(245, 158, 11, 0.3)'; ?>;
            color: <?php echo $is_https ? '#34d399' : '#fbbf24'; ?>;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Status Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
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
        .dot-warning { background-color: var(--status-warning); box-shadow: 0 0 10px var(--status-warning); }

        /* Tools & Actions List */
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-left: 4px solid #818cf8;
            padding-left: 0.75rem;
        }

        .tools-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .tool-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .tool-btn:hover {
            background: var(--accent-glow);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
            transform: scale(1.02);
        }

        .tool-icon {
            font-size: 1.2rem;
        }

        /* Server endpoints list */
        .server-endpoints {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 600px) {
            .server-endpoints {
                grid-template-columns: 1fr;
            }
        }

        .server-box {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
        }

        .server-box-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .link-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .link-row:last-child {
            border-bottom: none;
        }

        .endpoint-link {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .endpoint-link:hover {
            text-decoration: underline;
            color: #a855f7;
        }

        /* Diagnostics & Debugging Box */
        .debug-box {
            background: rgba(99, 102, 241, 0.05);
            border: 1px dashed rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 2rem;
        }

        .debug-instructions {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .debug-instructions code {
            background: rgba(0, 0, 0, 0.4);
            color: #f472b6;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
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
        }

        .debug-output {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.2);
        }

        .db-err-box {
            grid-column: 1 / -1;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: monospace;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <div class="header">
            <h1>Environment Active</h1>
            <p>Your ultra-modern, decoupled PHP local stack is ready to build awesome things.</p>
            <div class="badges-wrapper">
                <div class="badge-software">Served by: <?php echo htmlspecialchars($web_server_name); ?></div>
                <div class="badge-protocol">Protocol: <?php echo $protocol_label; ?></div>
            </div>
        </div>

        <div class="section-title">Diagnostics & Services</div>
        <div class="grid">
            <!-- PHP Card -->
            <div class="card">
                <div class="card-title">PHP Version</div>
                <div class="card-value">
                    <span class="status-dot dot-active"></span>
                    v<?php echo htmlspecialchars($php_version); ?>
                </div>
            </div>

            <!-- Xdebug Card -->
            <div class="card">
                <div class="card-title">Xdebug Module</div>
                <div class="card-value">
                    <span class="status-dot <?php echo $xdebug_active ? 'dot-active' : 'dot-error'; ?>"></span>
                    <?php echo $xdebug_active ? 'Active (' . htmlspecialchars($xdebug_mode) . ')' : 'Inactive'; ?>
                </div>
            </div>

            <!-- MariaDB Card -->
            <div class="card">
                <div class="card-title">MariaDB Connections</div>
                <div class="card-value" style="font-size: 0.95rem; flex-direction: column; align-items: flex-start; gap: 0.25rem;">
                    <div>
                        <span class="status-dot <?php echo $db_status_pdo === 'Connected successfully' ? 'dot-active' : 'dot-error'; ?>"></span>
                        <strong>PDO:</strong> <?php echo htmlspecialchars($db_status_pdo); ?>
                    </div>
                    <div>
                        <span class="status-dot <?php echo $db_status_mysqli === 'Connected successfully' ? 'dot-active' : 'dot-error'; ?>"></span>
                        <strong>MySQLi:</strong> <?php echo htmlspecialchars($db_status_mysqli); ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($db_error)): ?>
                <div class="db-err-box">
                    🚨 <strong>Database connection details:</strong><br>
                    <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-title">Active Server Endpoints</div>
        <div class="server-endpoints">
            <!-- Nginx -->
            <div class="server-box">
                <div class="server-box-title">⚡ Nginx Server</div>
                <div class="link-row">
                    <span>Standard (HTTP)</span>
                    <a href="http://localhost:8801/" class="endpoint-link" target="_blank">http://localhost:8801</a>
                </div>
                <div class="link-row">
                    <span>Secure (HTTPS) 🔒</span>
                    <a href="https://localhost:8811/" class="endpoint-link" target="_blank">https://localhost:8811</a>
                </div>
            </div>

            <!-- Apache -->
            <div class="server-box">
                <div class="server-box-title">🦅 Apache Server</div>
                <div class="link-row">
                    <span>Standard (HTTP)</span>
                    <a href="http://localhost:8802/" class="endpoint-link" target="_blank">http://localhost:8802</a>
                </div>
                <div class="link-row">
                    <span>Secure (HTTPS) 🔒</span>
                    <a href="https://localhost:8812/" class="endpoint-link" target="_blank">https://localhost:8812</a>
                </div>
            </div>
        </div>

        <div class="section-title">Developer Tooling</div>
        <ul class="tools-list">
            <li>
                <a href="http://localhost:8804/" target="_blank" class="tool-btn">
                    <span>Database (phpMyAdmin)</span>
                    <span class="tool-icon">📂</span>
                </a>
            </li>
            <li>
                <a href="http://localhost:8805/" target="_blank" class="tool-btn">
                    <span>Mail Capture (Mailpit)</span>
                    <span class="tool-icon">✉️</span>
                </a>
            </li>
            <li>
                <a href="?phpinfo=1" onclick="window.open('?phpinfo=true', 'PHPInfo', 'width=800,height=600'); return false;" class="tool-btn">
                    <span>PHP Settings (phpinfo)</span>
                    <span class="tool-icon">⚙️</span>
                </a>
            </li>
        </ul>

        <div class="section-title">VS Code Xdebug Step-by-Step Testing</div>
        <div class="debug-box">
            <div class="debug-instructions">
                <p>To verify that live, interactive debugging is fully operational:</p>
                <ol style="margin-left: 1.5rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <li>Ensure you have the <strong>PHP Debug</strong> extension installed in VS Code.</li>
                    <li>Open this project workspace in VS Code.</li>
                    <li>Open <code>src/index.php</code> and set a breakpoint on <strong>line 54</strong> (inside the conditional <code>$debug_triggered</code> check).</li>
                    <li>Press <kbd>F5</kbd> (or go to Run & Debug and click <strong>"Listen for Xdebug (Docker)"</strong>).</li>
                    <li>Click the button below to trigger the script. VS Code should instantly pause execution on line 54!</li>
                </ol>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div>Xdebug status:</div>
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
