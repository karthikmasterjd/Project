<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $db = get_db_connection();

    $blogsRaw = $db->query('SELECT * FROM `blogs` WHERE `active` = 1 ORDER BY `id` DESC')->fetchAll();
    $blogs = [];
    foreach ($blogsRaw as $row) {
        $blogs[] = [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'author' => $row['author'],
            'content' => $row['content'],
            'image' => $row['image'] ?: 'assets/gold_necklace.png',
            'createdAt' => $row['created_at'],
        ];
    }

    echo json_encode($blogs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
