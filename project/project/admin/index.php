<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';

function redirect_admin(string $message = ''): void
{
    $suffix = $message !== '' ? '?message=' . urlencode($message) : '';
    header('Location: index.php' . $suffix);
    exit;
}

function handle_upload(string $fieldName, string $fallback): string
{
    if (empty($_FILES[$fieldName]['name']) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $fallback;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        if ($_FILES[$fieldName]['error'] === UPLOAD_ERR_INI_SIZE) {
            throw new RuntimeException('Image upload failed: The file size exceeds the server upload limit. Please upload a smaller image (under 2MB) or configure upload_max_filesize in php.ini.');
        }
        throw new RuntimeException('Image upload failed with error code: ' . $_FILES[$fieldName]['error']);
    }

    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must be 5MB or smaller.');
    }

    $extension = strtolower(pathinfo((string) $_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    $uploadDir = project_root() . '/assets/products';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Failed to create upload directory: ' . $uploadDir);
        }
    }

    $extension = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = 'product-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded image to destination. Verify permissions for: ' . $uploadDir);
    }

    return 'assets/products/' . $filename;
}

function handle_upload_blog(string $fieldName, string $fallback): string
{
    if (empty($_FILES[$fieldName]['name']) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $fallback;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        if ($_FILES[$fieldName]['error'] === UPLOAD_ERR_INI_SIZE) {
            throw new RuntimeException('Blog image upload failed: The file size exceeds the server upload limit. Please upload a smaller image.');
        }
        throw new RuntimeException('Blog image upload failed with error code: ' . $_FILES[$fieldName]['error']);
    }

    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Blog image must be 5MB or smaller.');
    }

    $extension = strtolower(pathinfo((string) $_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed for blogs.');
    }

    $uploadDir = project_root() . '/assets/blogs';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Failed to create blog upload directory: ' . $uploadDir);
        }
    }

    $extension = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = 'blog-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded blog image to destination.');
    }

    return 'assets/blogs/' . $filename;
}

if (isset($_GET['message'])) {
    $message = clean_text((string) $_GET['message'], 180);
}

try {
    $db = get_db_connection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'setup') {
            $username = clean_text((string) ($_POST['username'] ?? ''), 80);
            $password = (string) ($_POST['password'] ?? '');
            if ($username === '' || strlen($password) < 10) {
                throw new RuntimeException('Use a username and a password with at least 10 characters.');
            }
            create_admin($username, $password);
            attempt_login($username, $password);
            redirect_admin('Admin account created.');
        }

        if ($action === 'login') {
            $username = clean_text((string) ($_POST['username'] ?? ''), 80);
            $password = (string) ($_POST['password'] ?? '');
            if (!attempt_login($username, $password)) {
                throw new RuntimeException('Invalid username or password.');
            }
            redirect_admin('Welcome back.');
        }

        if ($action === 'logout') {
            require_csrf();
            $_SESSION = [];
            session_destroy();
            header('Location: index.php');
            exit;
        }

        require_admin();
        require_csrf();

        if ($action === 'save_rates') {
            $stmt = $db->prepare('UPDATE `rates` SET 
                `gold22` = ?, `gold24` = ?, `gold18` = ?, `silver` = ?, `platinum` = ?, 
                `fix_gold22` = ?, `fix_gold24` = ?, `fix_gold18` = ?, `fix_silver` = ?, `fix_platinum` = ? 
                WHERE `id` = 1');
            $stmt->execute([
                clean_number($_POST['gold22'] ?? 0, 1),
                clean_number($_POST['gold24'] ?? 0, 1),
                clean_number($_POST['gold18'] ?? 0, 1),
                clean_number($_POST['silver'] ?? 0, 1),
                clean_number($_POST['platinum'] ?? 0, 1),
                isset($_POST['fixGold22']) ? 1 : 0,
                isset($_POST['fixGold24']) ? 1 : 0,
                isset($_POST['fixGold18']) ? 1 : 0,
                isset($_POST['fixSilver']) ? 1 : 0,
                isset($_POST['fixPlatinum']) ? 1 : 0,
            ]);
            redirect_admin('Rates updated on website.');
        }

        if ($action === 'save_settings') {
            $stmt = $db->prepare('UPDATE `settings` SET 
                `site_name` = ?, `rate_label` = ?, `phone` = ?, `whatsapp` = ?, `email` = ?, `showroom` = ?, `address` = ?
                WHERE `id` = 1');
            $stmt->execute([
                clean_text((string) ($_POST['siteName'] ?? ''), 160),
                clean_text((string) ($_POST['rateLabel'] ?? ''), 160),
                clean_text((string) ($_POST['phone'] ?? ''), 40),
                clean_text((string) ($_POST['whatsapp'] ?? ''), 40),
                filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                clean_text((string) ($_POST['showroom'] ?? ''), 160),
                clean_text((string) ($_POST['address'] ?? ''), 500),
            ]);
            redirect_admin('Website settings updated.');
        }

        if ($action === 'change_password') {
            $stmt = $db->query('SELECT * FROM `admin` LIMIT 1');
            $adminData = $stmt->fetch();
            if (!$adminData) {
                throw new RuntimeException('Admin account does not exist.');
            }

            $currentPassword = (string) ($_POST['currentPassword'] ?? '');
            $newPassword = (string) ($_POST['newPassword'] ?? '');
            $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

            if (!verify_admin_password($currentPassword, $adminData)) {
                throw new RuntimeException('Current password is incorrect.');
            }
            if (strlen($newPassword) < 10) {
                throw new RuntimeException('New password must be at least 10 characters.');
            }
            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('New password and confirmation do not match.');
            }

            save_admin_password($adminData['username'] ?? 'admin', $newPassword);
            redirect_admin('Admin password changed.');
        }

        if ($action === 'save_product') {
            $logData = date('Y-m-d H:i:s') . " - [INFO] save_product triggered.\nPOST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true) . "\n";
            file_put_contents(project_root() . '/admin_post.log', $logData, FILE_APPEND);
            $editingId = clean_text((string) ($_POST['editingId'] ?? ''), 120);
            if ($editingId !== '') {
                $editingId = slugify($editingId);
            }

            $refCode = clean_text((string) ($_POST['id'] ?? ''), 120);
            if ($refCode === '') {
                $refCode = slugify((string) ($_POST['name'] ?? ''));
            } else {
                $refCode = slugify($refCode);
            }

            $name = clean_text((string) ($_POST['name'] ?? ''), 160);
            if ($name === '') {
                throw new RuntimeException('Product name is required.');
            }

            $existingImage = 'assets/gold_necklace.png';
            if ($editingId !== '') {
                $stmt = $db->prepare('SELECT `image` FROM `products` WHERE `ref_code` = ?');
                $stmt->execute([$editingId]);
                $existingImage = $stmt->fetchColumn() ?: 'assets/gold_necklace.png';
            }

            $image = clean_text((string) ($_POST['image'] ?? $existingImage), 255);
            $image = handle_upload('productImage', $image);

            $category = clean_text((string) ($_POST['category'] ?? 'gold'), 80);
            $subcategory = clean_text((string) ($_POST['subcategory'] ?? ''), 80);
            $subSubcategory = clean_text((string) ($_POST['subSubcategory'] ?? ''), 80);
            $metalType = clean_text((string) ($_POST['metalType'] ?? 'gold22'), 40);
            $baseWeight = clean_number($_POST['baseWeight'] ?? 0, 0, 100000);
            $wastagePercent = clean_number($_POST['wastagePercent'] ?? 0, 0, 100);
            $purity = clean_text((string) ($_POST['purity'] ?? ''), 120);
            $style = clean_text((string) ($_POST['style'] ?? ''), 120);
            $active = isset($_POST['active']) ? 1 : 0;
            $description = clean_text((string) ($_POST['description'] ?? ''), 1000);

            if ($editingId !== '') {
                $stmt = $db->prepare('UPDATE `products` SET 
                    `ref_code` = ?, `name` = ?, `category` = ?, `subcategory` = ?, `sub_subcategory` = ?, `metal_type` = ?,
                    `base_weight` = ?, `wastage_percent` = ?, `purity` = ?, `style` = ?, `image` = ?, 
                    `active` = ?, `description` = ? WHERE `ref_code` = ?');
                $stmt->execute([
                    $refCode, $name, $category, $subcategory, $subSubcategory, $metalType,
                    $baseWeight, $wastagePercent, $purity, $style, $image,
                    $active, $description, $editingId
                ]);
            } else {
                $stmt = $db->prepare('SELECT COUNT(*) FROM `products` WHERE `ref_code` = ?');
                $stmt->execute([$refCode]);
                $count = (int) $stmt->fetchColumn();
                $baseRef = $refCode;
                $counter = 2;
                while ($count > 0) {
                    $refCode = $baseRef . '-' . $counter;
                    $stmt->execute([$refCode]);
                    $count = (int) $stmt->fetchColumn();
                    $counter++;
                }

                $stmt = $db->prepare('INSERT INTO `products` 
                    (`ref_code`, `name`, `category`, `subcategory`, `sub_subcategory`, `metal_type`, `base_weight`, `wastage_percent`, `purity`, `style`, `image`, `active`, `description`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $refCode, $name, $category, $subcategory, $subSubcategory, $metalType,
                    $baseWeight, $wastagePercent, $purity, $style, $image,
                    $active, $description
                ]);
            }
            file_put_contents(project_root() . '/admin_post.log', date('Y-m-d H:i:s') . " - [SUCCESS] Product saved with ref_code: " . $refCode . "\n\n", FILE_APPEND);
            redirect_admin('Product saved and published.');
        }

        if ($action === 'delete_product') {
            $id = isset($_POST['id']) ? slugify((string) $_POST['id']) : '';
            $stmt = $db->prepare('DELETE FROM `products` WHERE `ref_code` = ?');
            $stmt->execute([$id]);
            redirect_admin('Product deleted.');
        }

        if ($action === 'save_blog') {
            $editingBlogId = (int) ($_POST['editingBlogId'] ?? 0);

            $title = clean_text((string) ($_POST['title'] ?? ''), 160);
            if ($title === '') {
                throw new RuntimeException('Blog title is required.');
            }

            $slug = clean_text((string) ($_POST['slug'] ?? ''), 120);
            if ($slug === '') {
                $slug = slugify($title);
            } else {
                $slug = slugify($slug);
            }

            $author = clean_text((string) ($_POST['author'] ?? 'Admin'), 80);
            $content = trim((string) ($_POST['content'] ?? ''));
            if ($content === '') {
                throw new RuntimeException('Blog content is required.');
            }

            $existingImage = 'assets/gold_necklace.png';
            if ($editingBlogId > 0) {
                $stmt = $db->prepare('SELECT `image` FROM `blogs` WHERE `id` = ?');
                $stmt->execute([$editingBlogId]);
                $existingImage = $stmt->fetchColumn() ?: 'assets/gold_necklace.png';
            }

            $image = clean_text((string) ($_POST['image'] ?? $existingImage), 255);
            $image = handle_upload_blog('blogImage', $image);

            $active = isset($_POST['active']) ? 1 : 0;

            if ($editingBlogId > 0) {
                $stmt = $db->prepare('UPDATE `blogs` SET 
                    `slug` = ?, `title` = ?, `author` = ?, `content` = ?, `image` = ?, `active` = ? 
                    WHERE `id` = ?');
                $stmt->execute([$slug, $title, $author, $content, $image, $active, $editingBlogId]);
            } else {
                $stmt = $db->prepare('SELECT COUNT(*) FROM `blogs` WHERE `slug` = ?');
                $stmt->execute([$slug]);
                $count = (int) $stmt->fetchColumn();
                $baseSlug = $slug;
                $counter = 2;
                while ($count > 0) {
                    $slug = $baseSlug . '-' . $counter;
                    $stmt->execute([$slug]);
                    $count = (int) $stmt->fetchColumn();
                    $counter++;
                }

                $stmt = $db->prepare('INSERT INTO `blogs` 
                    (`slug`, `title`, `author`, `content`, `image`, `active`) 
                    VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$slug, $title, $author, $content, $image, $active]);
            }
            redirect_admin('Blog post saved successfully.');
        }

        if ($action === 'delete_blog') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM `blogs` WHERE `id` = ?');
            $stmt->execute([$id]);
            redirect_admin('Blog post deleted.');
        }
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
    file_put_contents(dirname(__DIR__, 2) . '/admin_post.log', date('Y-m-d H:i:s') . " - [ERROR] Exception caught: " . $error . "\n\n", FILE_APPEND);
}

// Fetch rates, settings, and products from DB for rendering
try {
    $db = get_db_connection();
    $isSetup = !admin_exists();
    $admin = current_admin();

    $ratesRaw = $db->query('SELECT * FROM `rates` WHERE `id` = 1')->fetch() ?: [];
    $rates = [
        'gold22' => $ratesRaw['gold22'] ?? 0,
        'gold24' => $ratesRaw['gold24'] ?? 0,
        'gold18' => $ratesRaw['gold18'] ?? 0,
        'silver' => $ratesRaw['silver'] ?? 0,
        'platinum' => $ratesRaw['platinum'] ?? 0,
        'fixGold22' => $ratesRaw['fix_gold22'] ?? 0,
        'fixGold24' => $ratesRaw['fix_gold24'] ?? 0,
        'fixGold18' => $ratesRaw['fix_gold18'] ?? 0,
        'fixSilver' => $ratesRaw['fix_silver'] ?? 0,
        'fixPlatinum' => $ratesRaw['fix_platinum'] ?? 0,
        'updatedAt' => $ratesRaw['updated_at'] ?? 'Not yet updated',
    ];

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

    $productsRaw = $db->query('SELECT * FROM `products` ORDER BY `id` DESC')->fetchAll();
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

    $editId = isset($_GET['edit']) ? slugify((string) $_GET['edit']) : '';
    $editProduct = null;
    foreach ($products as $prod) {
        if (($prod['id'] ?? '') === $editId) {
            $editProduct = $prod;
            break;
        }
    }

    $blogsRaw = $db->query('SELECT * FROM `blogs` ORDER BY `id` DESC')->fetchAll();
    $blogs = [];
    foreach ($blogsRaw as $row) {
        $blogs[] = [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'author' => $row['author'],
            'content' => $row['content'],
            'image' => $row['image'] ?: 'assets/gold_necklace.png',
            'active' => (bool) $row['active'],
            'createdAt' => $row['created_at'],
        ];
    }

    $editBlogId = (int) ($_GET['edit_blog'] ?? 0);
    $editBlog = null;
    foreach ($blogs as $b) {
        if ($b['id'] === $editBlogId) {
            $editBlog = $b;
            break;
        }
    }
} catch (Throwable $e) {
    $isSetup = false;
    $admin = null;
    $rates = [];
    $settings = [];
    $products = [];
    $editProduct = null;
    $blogs = [];
    $editBlog = null;
    $error = 'Database initialization error: ' . $e->getMessage() . '. Please verify you have run database.sql setup.';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sethu Jewellery | Admin Portal</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php if ($isSetup): ?>
    <div class="auth-wrapper">
        <section class="auth-card">
            <div class="auth-logo">
                <div class="emblem">S</div>
                <h1>System Installation</h1>
                <p>Configure your secure admin credentials.</p>
            </div>
            
            <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
            
            <form method="post" class="grid-form">
                <input type="hidden" name="action" value="setup">
                <div class="form-group">
                    <label>Admin ID / Username</label>
                    <input name="username" placeholder="e.g. manager" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Password (Min 10 characters)</label>
                    <input name="password" type="password" minlength="10" placeholder="••••••••••••" required autocomplete="new-password">
                </div>
                <button type="submit"><i class="fa-solid fa-user-shield"></i> Install Administrator</button>
            </form>
        </section>
    </div>
    
<?php elseif (!$admin): ?>
    <div class="auth-wrapper">
        <section class="auth-card">
            <div class="auth-logo">
                <div class="emblem">S</div>
                <h1>Secure Login</h1>
                <p>Sethu Thanga Nagai Maaligai Dashboard</p>
            </div>
            
            <?php if ($message): ?><div class="notice success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
            
            <form method="post" class="grid-form">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Username</label>
                    <input name="username" placeholder="Enter username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input name="password" type="password" placeholder="Enter password" required autocomplete="current-password">
                </div>
                <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Authenticate</button>
            </form>
        </section>
    </div>
    
<?php else: ?>
    <main class="admin-shell">
        <header class="admin-header">
            <div>
                <span class="eyebrow">Interactive Console</span>
                <h1>Sethu Website Control Panel</h1>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="ghost-btn"><i class="fa-solid fa-power-off"></i> Logout</button>
            </form>
        </header>

        <?php if ($message): ?><div class="notice success"><i class="fa-solid fa-circle-check"></i> &nbsp; <?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><i class="fa-solid fa-circle-xmark"></i> &nbsp; <?= e($error) ?></div><?php endif; ?>

        <div class="dashboard-grid">
            <!-- Rate Board panel -->
            <form method="post" class="panel">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_rates">
                <h2><i class="fa-solid fa-chart-line"></i> Gold & Silver Rates</h2>
                <div class="grid-form two">
                    <div class="form-group">
                        <label>22K Gold (per gram)</label>
                        <input type="number" step="0.01" name="gold22" value="<?= e($rates['gold22'] ?? '') ?>" required>
                        <label class="checkbox-group" style="margin-top: 4px;">
                            <input type="checkbox" name="fixGold22" <?= !empty($rates['fixGold22']) ? 'checked' : '' ?>> Fix Rate
                        </label>
                    </div>
                    <div class="form-group">
                        <label>24K Gold (per gram)</label>
                        <input type="number" step="0.01" name="gold24" value="<?= e($rates['gold24'] ?? '') ?>" required>
                        <label class="checkbox-group" style="margin-top: 4px;">
                            <input type="checkbox" name="fixGold24" <?= !empty($rates['fixGold24']) ? 'checked' : '' ?>> Fix Rate
                        </label>
                    </div>
                    <div class="form-group">
                        <label>18K Gold (per gram)</label>
                        <input type="number" step="0.01" name="gold18" value="<?= e($rates['gold18'] ?? '') ?>" required>
                        <label class="checkbox-group" style="margin-top: 4px;">
                            <input type="checkbox" name="fixGold18" <?= !empty($rates['fixGold18']) ? 'checked' : '' ?>> Fix Rate
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Silver (per gram)</label>
                        <input type="number" step="0.01" name="silver" value="<?= e($rates['silver'] ?? '') ?>" required>
                        <label class="checkbox-group" style="margin-top: 4px;">
                            <input type="checkbox" name="fixSilver" <?= !empty($rates['fixSilver']) ? 'checked' : '' ?>> Fix Rate
                        </label>
                    </div>
                    <div class="form-group full">
                        <label>Platinum (per gram)</label>
                        <input type="number" step="0.01" name="platinum" value="<?= e($rates['platinum'] ?? '') ?>" required>
                        <label class="checkbox-group" style="margin-top: 4px;">
                            <input type="checkbox" name="fixPlatinum" <?= !empty($rates['fixPlatinum']) ? 'checked' : '' ?>> Fix Rate
                        </label>
                    </div>
                </div>
                <button type="submit" style="margin-top: 20px;"><i class="fa-solid fa-arrows-rotate"></i> Update Live Rates</button>
                <p class="hint">Last Board Sync: <?= e($rates['updatedAt'] ?? 'Never updated') ?></p>
            </form>

            <!-- Store settings panel -->
            <form method="post" class="panel">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_settings">
                <h2><i class="fa-solid fa-sliders"></i> Store Settings</h2>
                <div class="grid-form two">
                    <div class="form-group">
                        <label>Boutique Name</label>
                        <input name="siteName" value="<?= e($settings['siteName'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Rate Board Title</label>
                        <input name="rateLabel" value="<?= e($settings['rateLabel'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input name="phone" value="<?= e($settings['phone'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp Number</label>
                        <input name="whatsapp" value="<?= e($settings['whatsapp'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Official Email</label>
                        <input name="email" type="email" value="<?= e($settings['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Showroom Label</label>
                        <input name="showroom" value="<?= e($settings['showroom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group full">
                        <label>Showroom Physical Address</label>
                        <textarea name="address" required><?= e($settings['address'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" style="margin-top: 20px;"><i class="fa-solid fa-floppy-disk"></i> Save Web Settings</button>
            </form>
        </div>

        <!-- Add/Edit Product Panel -->
        <section class="panel">
            <h2><i class="fa-solid fa-gem"></i> <?= $editProduct ? 'Edit Catalog Product' : 'Add New Product' ?></h2>
            <form method="post" enctype="multipart/form-data" class="grid-form three">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="editingId" value="<?= e($editProduct['id'] ?? '') ?>">
                
                <div class="form-group">
                    <label>Product Reference Code</label>
                    <input name="id" value="<?= e($editProduct['id'] ?? '') ?>" placeholder="e.g. gold-ring-50 (or auto)">
                </div>
                <div class="form-group wide">
                    <label>Jewellery Name</label>
                    <input name="name" value="<?= e($editProduct['name'] ?? '') ?>" placeholder="e.g. Antique Lakshmi Kasu Haram" required>
                </div>
                
                <div class="form-group">
                    <label>Metal Class</label>
                    <select name="category">
                        <?php foreach (['gold', 'diamond', 'silver-jewellery', 'silver-articles', 'coins'] as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= (($editProduct['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e(ucwords(str_replace('-', ' ', $cat))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory / Product Type</label>
                    <input name="subcategory" value="<?= e($editProduct['subcategory'] ?? '') ?>" placeholder="e.g. Necklace, Bangle" required>
                </div>
                <div class="form-group">
                    <label>Sub-subcategory</label>
                    <input name="subSubcategory" value="<?= e($editProduct['subSubcategory'] ?? '') ?>" placeholder="e.g. Antique Kasu Mala, Filigree Kada">
                </div>
                <div class="form-group">
                    <label>Pricing Metal Grade</label>
                    <select name="metalType">
                        <?php foreach (['gold22', 'gold24', 'gold18', 'silver', 'platinum'] as $m): ?>
                            <option value="<?= e($m) ?>" <?= (($editProduct['metalType'] ?? '') === $m) ? 'selected' : '' ?>><?= e(strtoupper($m)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Metal Net Weight (in Grams)</label>
                    <input type="number" step="0.001" name="baseWeight" value="<?= e($editProduct['baseWeight'] ?? '') ?>" placeholder="e.g. 8.00" required>
                </div>
                <div class="form-group">
                    <label>Making & Wastage Charges (%)</label>
                    <input type="number" step="0.01" name="wastagePercent" value="<?= e($editProduct['wastagePercent'] ?? '') ?>" placeholder="e.g. 10.00">
                </div>
                <div class="form-group">
                    <label>Purity Standard</label>
                    <input name="purity" value="<?= e($editProduct['purity'] ?? '') ?>" placeholder="e.g. 22K (916 Hallmarked)" required>
                </div>
                
                <div class="form-group">
                    <label>Design Style Name</label>
                    <input name="style" value="<?= e($editProduct['style'] ?? '') ?>" placeholder="e.g. Heritage Antique, Modern Filigree" required>
                </div>
                <div class="form-group">
                    <label>Template/Fallback Image Path</label>
                    <input name="image" value="<?= e($editProduct['image'] ?? 'assets/gold_necklace.png') ?>" required>
                </div>
                <div class="form-group">
                    <label>Upload High-Res Product Image</label>
                    <input type="file" name="productImage" accept="image/png,image/jpeg,image/webp">
                </div>
                
                <div class="form-group full">
                    <label class="checkbox-group">
                        <input type="checkbox" name="active" <?= (($editProduct['active'] ?? true) !== false) ? 'checked' : '' ?>>
                        Publish immediately (Visible in frontend catalog)
                    </label>
                </div>
                
                <div class="form-group full">
                    <label>Detailed Product Description</label>
                    <textarea name="description" required placeholder="Add detailed product description for customers..."><?= e($editProduct['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group full" style="flex-direction: row; gap: 15px; margin-top: 10px;">
                    <button type="submit"><i class="fa-solid fa-circle-check"></i> Save and Publish Product</button>
                    <?php if ($editProduct): ?>
                        <a class="btn secondary" href="index.php"><i class="fa-solid fa-ban"></i> Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Published catalog list -->
        <section class="panel">
            <h2><i class="fa-solid fa-list-check"></i> Published Jewellery Catalog</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Category</th>
                            <th>Metal Code</th>
                            <th>Weight (g)</th>
                            <th>Status</th>
                            <th>Management Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <i class="fa-solid fa-box-open" style="font-size: 32px; margin-bottom: 12px;"></i>
                                    <p>No products in the catalog yet. Use the form above to add your first item.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($prod['name'] ?? '') ?></strong><br>
                                        <span>ID: <?= e($prod['id'] ?? '') ?> &bull; Style: <?= e($prod['style'] ?? '') ?> &bull; Type: <?= e($prod['subcategory'] ?? '') ?><?= !empty($prod['subSubcategory']) ? ' (' . e($prod['subSubcategory']) . ')' : '' ?></span>
                                    </td>
                                    <td><?= e(ucwords(str_replace('-', ' ', $prod['category'] ?? ''))) ?></td>
                                    <td><?= strtoupper(e($prod['metalType'] ?? '')) ?></td>
                                    <td><?= number_format((float)($prod['baseWeight'] ?? 0), 2) ?>g</td>
                                    <td>
                                        <span class="badge <?= (($prod['active'] ?? true) !== false) ? 'active' : 'hidden' ?>">
                                            <?= (($prod['active'] ?? true) !== false) ? 'Active' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a class="action-link" href="?edit=<?= e($prod['id'] ?? '') ?>"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                            <form method="post" class="action-form" onsubmit="return confirm('Confirm deletion of this jewellery item?');">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?= e($prod['id'] ?? '') ?>">
                                                <button type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Blog Management Section -->
        <section class="panel">
            <h2><i class="fa-solid fa-pen-nib"></i> <?= $editBlog ? 'Edit Blog Article' : 'Write Blog Article' ?></h2>
            <form method="post" enctype="multipart/form-data" class="grid-form three">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_blog">
                <input type="hidden" name="editingBlogId" value="<?= e($editBlog['id'] ?? 0) ?>">
                
                <div class="form-group wide">
                    <label>Blog Title</label>
                    <input name="title" value="<?= e($editBlog['title'] ?? '') ?>" placeholder="e.g. 5 Tips to Keep Your Gold Jewellery Sparkling" required>
                </div>
                <div class="form-group">
                    <label>URL Slug (or auto)</label>
                    <input name="slug" value="<?= e($editBlog['slug'] ?? '') ?>" placeholder="e.g. tips-sparkling-gold">
                </div>
                
                <div class="form-group">
                    <label>Author / Publisher</label>
                    <input name="author" value="<?= e($editBlog['author'] ?? 'Sethu Team') ?>" required>
                </div>
                <div class="form-group">
                    <label>Default/Fallback Cover Image Path</label>
                    <input name="image" value="<?= e($editBlog['image'] ?? 'assets/gold_necklace.png') ?>" required>
                </div>
                <div class="form-group">
                    <label>Upload Article Cover Photo</label>
                    <input type="file" name="blogImage" accept="image/png,image/jpeg,image/webp">
                </div>
                
                <div class="form-group full">
                    <label class="checkbox-group">
                        <input type="checkbox" name="active" <?= (($editBlog['active'] ?? true) !== false) ? 'checked' : '' ?>>
                        Publish immediately (Visible on website Blog page)
                    </label>
                </div>
                
                <div class="form-group full">
                    <label>Article Body / Content</label>
                    <textarea name="content" required placeholder="Write your blog article body here... (Line breaks will be converted automatically)" style="min-height: 250px;"><?= e($editBlog['content'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group full" style="flex-direction: row; gap: 15px; margin-top: 10px;">
                    <button type="submit"><i class="fa-solid fa-circle-check"></i> Save and Publish Blog</button>
                    <?php if ($editBlog): ?>
                        <a class="btn secondary" href="index.php"><i class="fa-solid fa-ban"></i> Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Published Blog Articles list -->
        <section class="panel">
            <h2><i class="fa-solid fa-rss"></i> Published Blog Articles</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Article Details</th>
                            <th>Author</th>
                            <th>Date Published</th>
                            <th>Status</th>
                            <th>Management Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blogs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <i class="fa-solid fa-newspaper" style="font-size: 32px; margin-bottom: 12px;"></i>
                                    <p>No blog posts published yet. Write your first article above.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blogs as $b): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($b['title'] ?? '') ?></strong><br>
                                        <span>Slug: <?= e($b['slug'] ?? '') ?></span>
                                    </td>
                                    <td><?= e($b['author'] ?? '') ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($b['createdAt'])) ?></td>
                                    <td>
                                        <span class="badge <?= (($b['active'] ?? true) !== false) ? 'active' : 'hidden' ?>">
                                            <?= (($b['active'] ?? true) !== false) ? 'Active' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a class="action-link" href="?edit_blog=<?= e($b['id'] ?? '') ?>"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                            <form method="post" class="action-form" onsubmit="return confirm('Confirm deletion of this blog post?');">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete_blog">
                                                <input type="hidden" name="id" value="<?= e($b['id'] ?? '') ?>">
                                                <button type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Change Password Section -->
        <section class="panel" style="max-width: 600px; margin-bottom: 0;">
            <h2><i class="fa-solid fa-key"></i> Change Admin Password</h2>
            <form method="post" class="grid-form">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input name="currentPassword" type="password" required autocomplete="current-password" placeholder="••••••••••••">
                </div>
                <div class="form-group">
                    <label>New Password (Min 10 characters)</label>
                    <input name="newPassword" type="password" minlength="10" required autocomplete="new-password" placeholder="••••••••••••">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input name="confirmPassword" type="password" minlength="10" required autocomplete="new-password" placeholder="••••••••••••">
                </div>
                <button type="submit" style="justify-self: start;"><i class="fa-solid fa-shield"></i> Update Password</button>
            </form>
        </section>
    </main>
<?php endif; ?>
</body>
</html>
