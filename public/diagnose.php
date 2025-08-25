<?php
// Comprehensive diagnostic script
echo "=== PINBALL TOURNAMENT TRACKER DIAGNOSTICS ===\n\n";

echo "1. BASIC PHP INFO:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Server: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n\n";

echo "2. FILE SYSTEM:\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Vendor exists: " . (file_exists('../vendor/autoload.php') ? 'YES' : 'NO') . "\n";
echo "Bootstrap exists: " . (file_exists('../bootstrap/app.php') ? 'YES' : 'NO') . "\n";
echo ".env exists: " . (file_exists('../.env') ? 'YES' : 'NO') . "\n\n";

echo "3. ENVIRONMENT VARIABLES:\n";
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'not set') . "\n";
echo "APP_DEBUG: " . ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'not set') . "\n";
echo "APP_KEY set: " . (($_ENV['APP_KEY'] ?? getenv('APP_KEY')) ? 'YES' : 'NO') . "\n";
echo "DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'not set') . "\n\n";

echo "4. LARAVEL BOOTSTRAP TEST:\n";
try {
    // Change to Laravel root directory
    chdir('..');
    
    if (!file_exists('vendor/autoload.php')) {
        throw new Exception('Composer autoload not found');
    }
    
    require_once 'vendor/autoload.php';
    echo "✓ Autoload loaded\n";
    
    if (!file_exists('bootstrap/app.php')) {
        throw new Exception('Laravel bootstrap not found');
    }
    
    $app = require_once 'bootstrap/app.php';
    echo "✓ Laravel app created\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ HTTP Kernel created\n";
    
    // Try to create a simple request
    $request = Illuminate\Http\Request::create('/test-internal', 'GET');
    echo "✓ Request created\n";
    
    echo "Laravel bootstrap: SUCCESS\n";
    
} catch (Exception $e) {
    echo "✗ Laravel bootstrap FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "✗ Laravel bootstrap ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n5. LOADED EXTENSIONS:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach (['pdo', 'pdo_pgsql', 'openssl', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json'] as $ext) {
    echo "$ext: " . (in_array($ext, $extensions) ? 'YES' : 'NO') . "\n";
}

echo "\n=== END DIAGNOSTICS ===\n";
