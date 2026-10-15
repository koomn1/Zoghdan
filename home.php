<?php
declare(strict_types=1);

$pdo = db();
$categories = $pdo->query('SELECT * FROM categories WHERE active=1 ORDER BY sort_order, name LIMIT 3')->fetchAll();
$featured = $pdo->query('SELECT p.*, c.name category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.featured DESC, p.id DESC LIMIT 8')->fetchAll();
$hot = array_slice($featured, 0, 4);
header_html('عطور وبخور وزجاجيات مختارة');
?>
<section class="rm-hero">
  <div class="rm-hero-copy">
    <p class="rm-kicker"><span>زغدان | Zoghdan</span> تفاصيلٌ تصنع حضورًا</p>
    <h1>عطرٌ يعبّر عنك،<br><b>وتفاصيل تبقى.</b></h1>
    <p>اكتشف عطورًا شرقية، وبخورًا مختارًا، وقطعًا زجاجية أنيقة — مجموعة تجمع بين أصالة الرائحة وجمال التفاصيل.</p>
    <form class="rm-search" action="/shop"><input name="q" aria-label="ابحث عن العطور والبخور والزجاجيات" placeholder="ابحث عن منتجك المفضل..."><button>بحث</button></form>
    <div class="rm-hero-actions"><a href="/shop" class="rm-primary">تسوّق المجموعة <span>←</span></a><a href="#today" class="rm-secondary">مختارات زغدان</a></div>
  </div>
  <div class="rm-hero-visual">
    <img src="/assets/images/zoghdan-hero.jpg" alt="عطر شرقي ومبخرة وقطعة زجاجية من زغدان">
    <div class="rm-hero-chip"><b>✦</b><span>اختيارات زغدان<br><strong>ذوقٌ يدوم</strong></span></div>
    <div class="rm-hero-delivery">تجربة تسوّق سهلة<br><b>الدفع عند الاستلام متاح</b></div>
  </div>
</section>

<section class="rm-confidence"><div><b>✦</b><span><strong>مختارات بعناية</strong><small>عطور وبخور وقطع زجاجية</small></span></div><div><b>◇</b><span><strong>طلب بسيط وواضح</strong><small>تفاصيل المنتج والسعر قبل الطلب</small></span></div><div><b>✓</b><span><strong>خدمة موثوقة</strong><small>تابع طلبك من الموقع</small></span></div></section>

<section class="rm-category-boulevard"><div class="rm-section-heading"><div><span class="rm-overline">عالم زغدان</span><h2>تسوّق حسب ما تحب.</h2></div><a href="/shop">اكتشف المجموعة <span>←</span></a></div><div class="rm-category-grid"><?php foreach($categories as $category): ?><a href="/shop?category=<?=e($category['slug'])?>" class="rm-category"><img src="/<?=e($category['image'])?>" alt="<?=e($category['name'])?>"><span><?=e($category['name'])?></span><b>←</b></a><?php endforeach; ?></div></section>

<section id="today" class="rm-offer-section"><div class="rm-offer-banner"><div><span class="rm-offer-badge">مختارات الموسم</span><h2>لحظاتٌ أجمل<br><b>برائحةٍ وذوق.</b></h2><p>تصفّح تشكيلة زغدان، واختر العطر أو البخور أو القطعة الزجاجية التي تناسبك.</p><a href="/shop" class="rm-dark-button">تصفّح المنتجات <span>←</span></a></div><div class="rm-offer-art"><span>زغدان</span><i>أثرٌ من ذوقك</i></div></div><div class="rm-product-strip"><?php foreach($hot as $product) product_card($product); ?></div></section>

<section class="rm-product-section"><div class="rm-section-heading"><div><span class="rm-overline">اختيار زغدان</span><h2>منتجات تستحق الاكتشاف.</h2></div><a href="/shop">كل المنتجات <span>←</span></a></div><div class="product-grid rm-product-grid"><?php foreach($featured as $product) product_card($product); ?></div></section>

<section class="rm-how"><div class="rm-how-copy"><span class="rm-overline">تجربة سهلة</span><h2>طلبك في ثلاث خطوات.</h2><p>اختر ما يعجبك، أضفه إلى السلة، ثم أكّد بيانات التوصيل. سنراجع طلبك ونتواصل معك.</p><a href="/cart" class="rm-primary">اذهب إلى السلة <span>←</span></a></div><div class="rm-steps"><div><b>١</b><strong>اختر منتجك</strong><span>تصفّح العطور والبخور والزجاجيات.</span></div><div><b>٢</b><strong>راجع سلتك</strong><span>تأكد من الكمية والسعر قبل المتابعة.</span></div><div><b>٣</b><strong>أكّد طلبك</strong><span>أدخل بياناتك واختر الدفع عند الاستلام.</span></div></div></section>

<section class="rm-business"><div><span>ZOGHDAN | زغدان</span><h2>رائحةٌ جميلة،<br>وتفاصيل لها معنى.</h2></div><p>عطور شرقية، وبخور مختار، وزجاجيات أنيقة — في مكان واحد.</p><a href="/about">تعرّف على زغدان <span>←</span></a></section>
<?php footer_html(); ?>
