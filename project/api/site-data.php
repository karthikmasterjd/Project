<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $db = get_db_connection();

    // 1. Fetch rates and map to camelCase keys
    $ratesRaw = $db->query('SELECT * FROM `rates` WHERE `id` = 1')->fetch() ?: [];
    $rates = [
        'gold22' => (float) ($ratesRaw['gold22'] ?? 0),
        'gold24' => (float) ($ratesRaw['gold24'] ?? 0),
        'gold18' => (float) ($ratesRaw['gold18'] ?? 0),
        'silver' => (float) ($ratesRaw['silver'] ?? 0),
        'platinum' => (float) ($ratesRaw['platinum'] ?? 0),
        'fixGold22' => (bool) ($ratesRaw['fix_gold22'] ?? false),
        'fixGold24' => (bool) ($ratesRaw['fix_gold24'] ?? false),
        'fixGold18' => (bool) ($ratesRaw['fix_gold18'] ?? false),
        'fixSilver' => (bool) ($ratesRaw['fix_silver'] ?? false),
        'fixPlatinum' => (bool) ($ratesRaw['fix_platinum'] ?? false),
        'updatedAt' => $ratesRaw['updated_at'] ?? 'Not yet updated',
    ];

    // 2. Fetch settings and map to camelCase keys
    $settingsRaw = $db->query('SELECT * FROM `settings` WHERE `id` = 1')->fetch() ?: [];
    $settings = [
        'siteName' => $settingsRaw['site_name'] ?? '',
        'rateLabel' => $settingsRaw['rate_label'] ?? '',
        'phone' => $settingsRaw['phone'] ?? '',
        'whatsapp' => $settingsRaw['whatsapp'] ?? '',
        'email' => $settingsRaw['email'] ?? '',
        'showroom' => $settingsRaw['showroom'] ?? '',
        'address' => $settingsRaw['address'] ?? '',
    ];

    // 3. Fetch active products and map to camelCase keys
    $productsRaw = $db->query('SELECT * FROM `products` WHERE `active` = 1 ORDER BY `id` DESC')->fetchAll();
    $products = [];
    foreach ($productsRaw as $row) {
        $products[] = [
            'id' => $row['ref_code'],
            'name' => $row['name'],
            'category' => $row['category'],
            'subcategory' => $row['subcategory'],
            'subSubcategory' => $row['sub_subcategory'],
            'metalType' => $row['metal_type'],
            'baseWeight' => (float) $row['base_weight'],
            'wastagePercent' => (float) $row['wastage_percent'],
            'purity' => $row['purity'],
            'style' => $row['style'],
            'image' => $row['image'],
            'active' => (bool) $row['active'],
            'description' => $row['description'],
        ];
    }

    echo json_encode([
        'rates' => $rates,
        'settings' => $settings,
        'products' => $products,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
