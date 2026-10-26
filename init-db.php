<?php
require_once __DIR__ . '/config.php';

try {
    $db = (new Lab\Database())->connect();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $db->exec($sql);
    echo "Database initialized successfully.\n";
} catch (Exception $e) {
    echo "Initialization error: " . $e->getMessage() . "\n";
}