<?php
declare(strict_types=1);

require_once __DIR__ . '/admin/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = get_db_connection();
    echo "--- PHP DATABASE CONNECTION DIAGNOSTIC ---\n";
    echo "Configured DB_HOST: " . DB_HOST . "\n";
    echo "Configured DB_PORT: " . DB_PORT . "\n";
    echo "Configured DB_NAME: " . DB_NAME . "\n";
    
    $port = $db->query('SELECT @@port')->fetchColumn();
    $version = $db->query('SELECT @@version')->fetchColumn();
    $database = $db->query('SELECT DATABASE()')->fetchColumn();
    
    echo "Connected MySQL Port: " . $port . "\n";
    echo "Connected MySQL Version: " . $version . "\n";
    echo "Connected Database Name: " . $database . "\n";
    
    $count = $db->query('SELECT COUNT(*) FROM `products`')->fetchColumn();
    echo "Products Table Count: " . $count . "\n";
    
    $products = $db->query('SELECT id, ref_code, name FROM `products`')->fetchAll();
    echo "Products List:\n";
    foreach ($products as $p) {
        echo " - ID: {$p['id']} | Ref: {$p['ref_code']} | Name: {$p['name']}\n";
    }
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
