
<?php

require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h1>AIS Database Connection</h1>";
    echo "<h2>Successfully connected!</h2>";

    echo "<h3>Available database tables:</h3>";

    foreach ($tables as $table) {
        echo htmlspecialchars($table) . "<br>";
    }

} catch (PDOException $e) {
    echo "Error checking database tables.";
    error_log($e->getMessage());
}
