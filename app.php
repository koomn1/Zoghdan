<?php
declare(strict_types=1);

/**
 * Zoghdan | زغدان application helpers.
 * Arabic-first Zoghdan storefront for selected perfumes, incense and glassware.
 */

$config = require __DIR__ . '/config.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('zoghdan_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = '/'): string { return $path === '' ? '/' : $path; }
function redirect(string $path): never { header('Location: ' . url($path), true, 303); exit; }
function csrf(): string { if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); } return $_SESSION['csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('الطلب غير صالح. حدّث الصفحة وحاول مجدداً.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function flashes(): array { $items = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $items; }
function money(float $amount): string { return number_format($amount, 2, '.', ',') . ' ' . setting('currency_symbol', 'ج.م'); }
function slugify(string $text): string { $text = strtolower(trim($text)); $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? ''; return trim($text, '-') ?: 'item-' . bin2hex(random_bytes(3)); }

function db(): PDO {
    global $config;
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (empty($config['installed'])) { throw new RuntimeException('لم يتم إعداد متجر زغدان بعد.'); }
    $db = $config['db'];
    $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function setting(string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings[$key] ?? $default;
}

function cart_count(): int { return array_sum(array_map('intval', $_SESSION['cart'] ?? [])); }
function cart_rows(): array {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];
    $ids = array_keys($cart);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, slug, price, sale_price, stock, image FROM products WHERE active = 1 AND id IN ($marks)");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $product) {
        $qty = max(1, min((int)$cart[$product['id']], (int)$product['stock']));
        $unit = $product['sale_price'] !== null ? (float)$product['sale_price'] : (float)$product['price'];
        $product['quantity'] = $qty; $product['unit_price'] = $unit; $product['line_total'] = $unit * $qty;
        $items[] = $product;
    }
    return $items;
}
function cart_total(array $items): float { return array_sum(array_column($items, 'line_total')); }
function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if (!is_admin()) { flash('error', 'سجّل الدخول للوصول إلى لوحة الإدارة.'); redirect('/admin'); } }
function order_code(): string { return 'ZG-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))); }
function order_status_label(string $status): string { return ['new'=>'جديد','confirmed'=>'تم التأكيد','packed'=>'قيد التجهيز','shipped'=>'تم الشحن','delivered'=>'تم التسليم','cancelled'=>'ملغي'][$status] ?? $status; }

function header_html(string $title = 'زغدان | Zoghdan', bool $admin = false): void {
    $cart = cart_count();
    $store = !empty($GLOBALS['config']['installed']) ? setting('store_name', 'زغدان | Zoghdan') : 'زغدان | Zoghdan';
    ?>
<!doctype html><html lang="ar" dir="rtl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#26382d"><meta name="description" content="زغدان | Zoghdan — عطور شرقية، بخور فاخر، وزجاجيات مختارة."><meta name="color-scheme" content="light">
<title><?= e($title) ?> · <?= e($store) ?></title><link rel="icon" type="image/svg+xml" href="/assets/images/zoghdan-mark.svg">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/river-market.css"><link rel="stylesheet" href="/assets/css/bd-ecommerce-guide.css"><link rel="stylesheet" href="/assets/css/professional-polish.css"><link rel="stylesheet" href="/assets/css/commerce-extensions.css?v=20260823"><link rel="stylesheet" href="/assets/css/zoghdan-brand.css?v=1"></head><body class="<?= $admin ? 'admin-body' : '' ?>">
<?php if (!$admin): ?><div class="utility"><span>توصيل موثوق</span><span>الدفع عند الاستلام</span><span>اختيارات بعناية</span></div>
<header class="site-header"><a class="brand" href="/" aria-label="زغدان | Zoghdan"><img src="/assets/images/zoghdan-mark.svg" alt=""><span class="brand-lockup"><strong>Zoghdan</strong><small>زغدان</small></span></a><nav aria-label="التنقل الرئيسي"><a href="/shop">كل المنتجات</a><a href="/shop?category=perfumes">العطور</a><a href="/shop?category=bakhoor">البخور</a><a href="/shop?category=glass">الزجاجيات</a></nav><div class="header-actions"><a href="/shop" aria-label="ابحث عن المنتجات">بحث</a><a href="/cart" class="cart-link">السلة <b><?= $cart ?></b></a></div></header><?php endif; ?>
<main><?php foreach (flashes() as $notice): ?><div class="notice notice-<?= e($notice['type']) ?>"><span><?= e($notice['message']) ?></span><button type="button" onclick="this.parentElement.remove()">×</button></div><?php endforeach; ?>
<?php }
function developer_credit_html(): void { /* Brand-neutral footer: no template vendor attribution. */ }
function footer_html(): void { ?>
</main><?php $support = !empty($GLOBALS['config']['installed']) ? setting('support_phone', '') : ''; if ($support === '' || $support === '__UNCONFIGURED__') $support = 'أضف رقم خدمة العملاء من إعدادات المتجر'; $supportTel = preg_replace('/[^0-9+]/', '', $support) ?: ''; ?><footer class="site-footer commerce-footer"><div class="footer-intro"><a class="brand footer-brand" href="/"><img src="/assets/images/zoghdan-mark.svg" alt=""><span class="brand-lockup"><strong>Zoghdan</strong><small>زغدان</small></span></a><p>نفحاتٌ مختارة، وبخورٌ أصيل، وقطع زجاجية تضيف لمسةً من الجمال إلى يومك.</p><span class="cod-badge"><b>COD</b> الدفع عند الاستلام</span></div><div><small>خدمة العملاء</small><a href="/about">من نحن</a><a href="/contact">تواصل معنا</a><a href="/track-order">تتبّع الطلب</a><a href="/faq">الأسئلة الشائعة</a></div><div><small>تسوّق حسب القسم</small><a href="/shop?category=perfumes">العطور</a><a href="/shop?category=bakhoor">البخور</a><a href="/shop?category=glass">الزجاجيات</a></div><div><small>الدعم والطلبات</small><a class="support-phone" href="tel:<?=e($supportTel)?>"><?=e($support)?></a><p>أوقات التواصل من إعدادات المتجر</p><div class="footer-social"><a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><span>f</span></a><a href="https://wa.me/" target="_blank" rel="noopener noreferrer" aria-label="واتساب"><span>WA</span></a></div></div><div class="footer-bottom"><span>© <?= date('Y') ?> زغدان | Zoghdan. جميع الحقوق محفوظة.</span><span><a href="/privacy-policy">سياسة الخصوصية</a><a href="/return-refund-policy">سياسة الاستبدال والاسترجاع</a></span></div></footer><?php developer_credit_html(); ?><script src="/assets/js/app.js"></script></body></html><?php }

function product_card(array $p): void { $price = $p['sale_price'] !== null ? (float)$p['sale_price'] : (float)$p['price']; ?>
<article class="product-card"><a class="product-image" href="/product/<?= e($p['slug']) ?>"><img src="/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy"><span class="product-reveal">عرض التفاصيل</span></a><div class="product-meta"><div><small><?= e($p['category_name'] ?? 'Selected') ?></small><h3><a href="/product/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3></div><div class="price"><?php if ($p['sale_price'] !== null): ?><del><?= money((float)$p['price']) ?></del><?php endif; ?><strong><?= money($price) ?></strong></div></div><form method="post" action="/cart/add" class="card-cart"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><button <?= (int)$p['stock'] < 1 ? 'disabled' : '' ?>><?= (int)$p['stock'] < 1 ? 'نفد المخزون' : 'أضف إلى السلة' ?></button></form></article><?php }
