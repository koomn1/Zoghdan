<?php
declare(strict_types=1);

/**
 * Zoghdan one-time installer for PHP/MySQL hosting.
 * No passwords or database credentials are shipped in this file.
 */
$configPath = __DIR__ . '/config.local.php';
if (is_file($configPath)) {
    http_response_code(404);
    exit('Installer disabled.');
}

session_name('zoghdan_install');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();
$_SESSION['install_csrf'] ??= bin2hex(random_bytes(32));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['install_csrf'], $_POST['csrf'] ?? '')) {
        $error = 'طلب إعداد غير صالح. حاول مرة أخرى.';
    } else {
        $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');
        $email = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
        $adminPass = (string) ($_POST['admin_password'] ?? '');
        $phone = trim((string) ($_POST['support_phone'] ?? ''));

        if ($name === '' || $user === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 12) {
            $error = 'أكمل جميع الحقول، واجعل كلمة مرور المدير 12 حرفاً على الأقل.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host=$host;dbname=$name;charset=utf8mb4",
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
                );

                $schema = [
                    'CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                    'CREATE TABLE IF NOT EXISTS admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                    'CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE, image VARCHAR(255) NOT NULL, sort_order INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                    'CREATE TABLE IF NOT EXISTS products (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NOT NULL, name VARCHAR(180) NOT NULL, slug VARCHAR(200) NOT NULL UNIQUE, price DECIMAL(12,2) NOT NULL, sale_price DECIMAL(12,2) NULL, stock INT NOT NULL DEFAULT 0, image VARCHAR(255) NOT NULL, description TEXT NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, featured TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(category_id), CONSTRAINT products_category_fk FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                    "CREATE TABLE IF NOT EXISTS orders (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_code VARCHAR(40) NOT NULL UNIQUE, customer_name VARCHAR(150) NOT NULL, phone VARCHAR(32) NOT NULL, address TEXT NOT NULL, note TEXT NULL, subtotal DECIMAL(12,2) NOT NULL, delivery_charge DECIMAL(12,2) NOT NULL, total DECIMAL(12,2) NOT NULL, payment_method VARCHAR(50) NOT NULL, status ENUM('new','confirmed','packed','shipped','delivered','cancelled') NOT NULL DEFAULT 'new', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(status), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    'CREATE TABLE IF NOT EXISTS order_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, product_name VARCHAR(180) NOT NULL, unit_price DECIMAL(12,2) NOT NULL, quantity INT NOT NULL, line_total DECIMAL(12,2) NOT NULL, CONSTRAINT order_items_order_fk FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                ];
                foreach ($schema as $statement) {
                    $pdo->exec($statement);
                }

                $saveSetting = $pdo->prepare('INSERT IGNORE INTO settings(setting_key, setting_value) VALUES (?, ?)');
                foreach (['store_name' => 'زغدان | Zoghdan', 'support_phone' => $phone, 'delivery_charge' => '0', 'outside_city_delivery' => '0', 'currency_symbol' => 'ج.م'] as $key => $value) {
                    $saveSetting->execute([$key, $value]);
                }
                $pdo->prepare('INSERT INTO admins(name, email, password_hash) VALUES (?, ?, ?)')->execute([
                    'مدير زغدان',
                    $email,
                    password_hash($adminPass, PASSWORD_DEFAULT),
                ]);

                $categories = [
                    ['العطور', 'perfumes', 'assets/images/zoghdan-perfumes.jpg', 1],
                    ['البخور والعود', 'bakhoor', 'assets/images/zoghdan-incense.jpg', 2],
                    ['الزجاجيات', 'glass', 'assets/images/zoghdan-glass.jpg', 3],
                ];
                $insertCategory = $pdo->prepare('INSERT INTO categories(name, slug, image, sort_order) VALUES (?, ?, ?, ?)');
                foreach ($categories as $category) {
                    $insertCategory->execute($category);
                }

                $categoryIds = [];
                foreach ($pdo->query('SELECT id, slug FROM categories') as $category) {
                    $categoryIds[$category['slug']] = (int) $category['id'];
                }
                $products = [
                    ['perfumes', 'عطر عود شرقي', 'attar-oud-classic', 620, 560, 24, 'assets/images/zoghdan-perfumes.jpg', 'عطر بطابع شرقي دافئ يجمع نفحات العود والعنبر. أضف الحجم والتركيز ومكونات العطر الفعلية قبل الإطلاق.', 1],
                    ['perfumes', 'مسك أبيض ناعم', 'white-musk-elegance', 480, null, 20, 'assets/images/zoghdan-perfumes.jpg', 'مسك ناعم للاستخدام اليومي بلمسة نظيفة وأنيقة. حدّث الحجم والتركيبة ومعلومات الاستخدام حسب المنتج الفعلي.', 1],
                    ['bakhoor', 'بخور عود مختار', 'oud-bakhoor-selection', 390, 350, 18, 'assets/images/zoghdan-incense.jpg', 'قطع بخور مختارة لأجواء دافئة ومميزة. أضف الوزن والمنشأ وتعليمات الاستخدام الحقيقية قبل النشر.', 1],
                    ['glass', 'مبخرة زجاجية أنيقة', 'elegant-glass-burner', 750, null, 12, 'assets/images/zoghdan-glass.jpg', 'قطعة زجاجية بلمسة أنيقة تناسب تقديم العطر أو تعطير المكان. راجع الخامة والمقاسات ومقاومة الحرارة قبل البيع.', 1],
                    ['perfumes', 'زيت عطري مركز', 'concentrated-perfume-oil', 320, 290, 22, 'assets/images/zoghdan-perfumes.jpg', 'زيت عطري مركز في عبوة صغيرة عملية. أضف الحجم ومعلومات المكونات والتحذيرات الملائمة للمنتج الفعلي.', 0],
                    ['bakhoor', 'مبثوث برائحة شرقية', 'oriental-bakhoor-blend', 280, null, 16, 'assets/images/zoghdan-incense.jpg', 'خلطة بخور بروائح شرقية غنية. اذكر الوزن والمكونات وطريقة الاستخدام الفعلية قبل نشر المنتج.', 0],
                    ['glass', 'قارورة عطر زجاجية', 'artisan-glass-perfume-bottle', 540, 490, 10, 'assets/images/zoghdan-glass.jpg', 'قارورة زجاجية أنيقة لحفظ العطور أو الزيوت العطرية. راجع السعة والقياسات والتفاصيل الفعلية قبل الإطلاق.', 0],
                    ['bakhoor', 'حامل بخور نحاسي', 'brass-incense-holder', 680, null, 9, 'assets/images/zoghdan-incense.jpg', 'حامل بخور بلمسة نحاسية يضفي حضوراً مميزاً على المكان. أضف المقاسات والخامة الفعلية وتعليمات السلامة.', 0],
                ];
                $insertProduct = $pdo->prepare('INSERT INTO products(category_id, name, slug, price, sale_price, stock, image, description, active, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
                foreach ($products as [$category, $productName, $slug, $price, $salePrice, $stock, $image, $description, $featured]) {
                    $insertProduct->execute([$categoryIds[$category], $productName, $slug, $price, $salePrice, $stock, $image, $description, $featured]);
                }

                $local = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                    'installed' => true,
                    'app_key' => bin2hex(random_bytes(32)),
                    'db' => ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass],
                ], true) . ";\n";
                if (file_put_contents($configPath, $local, LOCK_EX) === false) {
                    throw new RuntimeException('تعذر إنشاء ملف الإعدادات. تحقق من صلاحيات مجلد الموقع.');
                }
                @chmod($configPath, 0640);
                header('Location: /admin');
                exit;
            } catch (Throwable $exception) {
                error_log($exception->getMessage());
                $error = 'تعذر الاتصال بقاعدة البيانات أو إتمام الإعداد. راجع البيانات وحاول مجدداً.';
            }
        }
    }
}
?><!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>إعداد زغدان | Zoghdan</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/zoghdan-brand.css?v=1"></head>
<body class="setup"><section class="setup-card"><img src="/assets/images/zoghdan-mark.svg" alt="زغدان"><p class="eyebrow"><i></i> إعداد المتجر مرة واحدة</p><h1>إعداد متجر زغدان</h1><p>أدخل بيانات قاعدة بيانات MySQL لإنشاء جداول المتجر وحساب المدير ومنتجات تجريبية للعطور والبخور والزجاجيات. سيتوقف ملف التثبيت تلقائياً بعد نجاح الإعداد.</p><?php if ($error): ?><div class="notice notice-error"><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?><form method="post" class="setup-form"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['install_csrf'], ENT_QUOTES, 'UTF-8') ?>"><div class="field-row"><label>مضيف قاعدة البيانات<input name="db_host" value="localhost" required></label><label>اسم قاعدة البيانات<input name="db_name" required></label></div><div class="field-row"><label>مستخدم قاعدة البيانات<input name="db_user" required></label><label>كلمة مرور قاعدة البيانات<input name="db_pass" type="password" required></label></div><label>البريد الإلكتروني للمدير<input name="admin_email" type="email" required></label><label>كلمة مرور قوية للمدير <small>(12 حرفاً على الأقل)</small><input name="admin_password" type="password" minlength="12" required></label><label>رقم التواصل مع العملاء<input name="support_phone" placeholder="أدخل رقم الهاتف" required></label><button class="button button-vermilion">إتمام الإعداد الآمن <span>→</span></button></form></section></body></html>
