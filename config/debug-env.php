<?php
// Create this file as debug-env.php in your public folder
echo "<h2>Environment Variables Debug</h2>";

echo "<h3>MySQL Environment Variables:</h3>";
echo "<strong>MYSQL_HOST:</strong> " . (getenv('MYSQL_HOST') ?: 'NOT SET') . "<br>";
echo "<strong>MYSQL_PORT:</strong> " . (getenv('MYSQL_PORT') ?: 'NOT SET') . "<br>";
echo "<strong>MYSQL_DATABASE:</strong> " . (getenv('MYSQL_DATABASE') ?: 'NOT SET') . "<br>";
echo "<strong>MYSQL_USER:</strong> " . (getenv('MYSQL_USER') ?: 'NOT SET') . "<br>";
echo "<strong>MYSQL_PASSWORD:</strong> " . (getenv('MYSQL_PASSWORD') ? 'SET (hidden)' : 'NOT SET') . "<br>";

echo "<h3>DATABASE_URL:</h3>";
echo "<strong>DATABASE_URL:</strong> " . (getenv('DATABASE_URL') ? 'SET (hidden)' : 'NOT SET') . "<br>";

echo "<h3>All Environment Variables (filtered):</h3>";
$env_vars = $_ENV;
foreach ($env_vars as $key => $value) {
    if (strpos($key, 'MYSQL') !== false || strpos($key, 'DATABASE') !== false) {
        if (strpos($key, 'PASS') !== false) {
            echo "<strong>$key:</strong> [HIDDEN]<br>";
        } else {
            echo "<strong>$key:</strong> $value<br>";
        }
    }
}

echo "<h3>Test get_DB_CONFIG__From_ENV_File function:</h3>";

// Test your function (copy it here for testing)
function get_DB_CONFIG__From_ENV_File_Test():array {
    $DB_CONFIG_arr = array();
    
    // Check if Railway provides a DATABASE_URL first
    $database_url = getenv("DATABASE_URL");
    if ($database_url) {
        echo "Using DATABASE_URL<br>";
        // Parse the database URL
        $parsed = parse_url($database_url);
        $DB_CONFIG_arr["DB_Host"] = $parsed['host'];
        $DB_CONFIG_arr["DB_Port"] = $parsed['port'] ?? 18598;  // Changed from 3306 to 18598
        $DB_CONFIG_arr["DB_Name"] = ltrim($parsed['path'], '/');
        $DB_CONFIG_arr["DB_User"] = $parsed['user'];
        $DB_CONFIG_arr["DB_Pass"] = $parsed['pass'];
    } else {
        echo "Using individual environment variables<br>";
        // Fallback to individual environment variables
        $DB_CONFIG_arr["DB_Host"] = getenv("MYSQL_HOST");
        $DB_CONFIG_arr["DB_Port"] = getenv("MYSQL_PORT") ?: "18598";
        $DB_CONFIG_arr["DB_Name"] = getenv("MYSQL_DATABASE");
        $DB_CONFIG_arr["DB_User"] = getenv("MYSQL_USER");
        
        $file_path = getenv("MYSQL_PASSWORD_FILE");
        $file_contents = "";
        if ($file_path) {
            $file_contents = file_get_contents($file_path);
        } 
        if ($file_contents) {
            $DB_CONFIG_arr["DB_Pass"] = trim($file_contents);
        } else {
            // Fallback to direct password
            $DB_CONFIG_arr["DB_Pass"] = getenv("MYSQL_PASSWORD");
        }
    }
    
    return $DB_CONFIG_arr;
}

$config = get_DB_CONFIG__From_ENV_File_Test();
echo "<strong>Resulting config:</strong><br>";
echo "DB_Host: " . ($config['DB_Host'] ?? 'NOT SET') . "<br>";
echo "DB_Port: " . ($config['DB_Port'] ?? 'NOT SET') . "<br>";
echo "DB_Name: " . ($config['DB_Name'] ?? 'NOT SET') . "<br>";
echo "DB_User: " . ($config['DB_User'] ?? 'NOT SET') . "<br>";
echo "DB_Pass: " . ($config['DB_Pass'] ? 'SET (hidden)' : 'NOT SET') . "<br>";
?>