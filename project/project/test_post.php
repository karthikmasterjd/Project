<?php
declare(strict_types=1);

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/admin/includes/storage.php';

try {
    $db = get_db_connection();
    echo "Count before: " . $db->query('SELECT COUNT(*) FROM `products`')->fetchColumn() . "\n";
    
    $refCode = 'coin-gold-07';
    $name = 'AK Computers';
    $category = 'gold';
    $subcategory = 'Coins';
    $subSubcategory = '';
    $metalType = 'gold22';
    $baseWeight = 77.0;
    $wastagePercent = 7.0;
    $purity = '24K (100% Pure Gold)';
    $style = 'Hallmarked Coin';
    $image = 'assets/gold_necklace.png';
    $active = 1;
    $description = 'ddddddddddddddddddddddddddddddddddddddddddddddd';

    $stmt = $db->prepare('INSERT INTO `products` 
        (`ref_code`, `name`, `category`, `subcategory`, `sub_subcategory`, `metal_type`, `base_weight`, `wastage_percent`, `purity`, `style`, `image`, `active`, `description`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $res = $stmt->execute([
        $refCode, $name, $category, $subcategory, $subSubcategory, $metalType,
        $baseWeight, $wastagePercent, $purity, $style, $image,
        $active, $description
    ]);
    
    echo "Execute result: " . ($res ? "TRUE" : "FALSE") . "\n";
    echo "Count after: " . $db->query('SELECT COUNT(*) FROM `products`')->fetchColumn() . "\n";
    
    $row = $db->query("SELECT * FROM `products` WHERE `ref_code` = 'coin-gold-07'")->fetch();
    echo "Fetched row: " . print_r($row, true) . "\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
