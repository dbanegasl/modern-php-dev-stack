<?php
// ==============================================================================
// Subfolder Breakpoint Diagnostics (prueba-debug)
// ==============================================================================

// Enable errors to view if there's any warning
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #6366f1; margin-bottom: 20px;'>Subfolder Xdebug Test</h1>";
echo "<p>If you opened the <code>src/prueba-debug</code> folder directly in VS Code and started the debugger, set a breakpoint on the lines below!</p>";

// --- SET BREAKPOINT HERE (Lines 16 - 19) ---
$greeting = "Hello from the subfolder system!";
$current_time = date('Y-m-d H:i:s');
$computed_value = 42 * 2;
// -------------------------------------------

echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
echo "<p><strong>Greeting:</strong> " . htmlspecialchars($greeting) . "</p>";
echo "<p><strong>Time:</strong> " . htmlspecialchars($current_time) . "</p>";
echo "<p><strong>Result:</strong> " . htmlspecialchars($computed_value) . "</p>";
echo "</div>";

echo "<p style='margin-top: 20px; font-size: 0.9em; color: #888;'>Visit this page via:<br>";
echo "Nginx HTTP: <a href='http://localhost:8801/prueba-debug/' target='_blank'>http://localhost:8801/prueba-debug/</a><br>";
echo "Apache HTTP: <a href='http://localhost:8802/prueba-debug/' target='_blank'>http://localhost:8802/prueba-debug/</a></p>";
echo "</div>";
