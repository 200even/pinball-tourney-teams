<?php
// Simple test endpoint that bypasses Laravel routing
echo "PHP is working! " . date('Y-m-d H:i:s');
echo "\n\nServer: " . ($_SERVER['HTTP_HOST'] ?? 'unknown');
echo "\nPHP Version: " . phpversion();
echo "\nMemory: " . ini_get('memory_limit');
