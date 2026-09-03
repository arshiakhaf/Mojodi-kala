<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function persian_digits($value): string
{
    return strtr((string) $value, [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ]);
}

function format_price($value): string
{
    return persian_digits(number_format((int) $value, 0, '.', '٬')) . ' تومان';
}

function lower_text(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function contains_text(string $haystack, string $needle): bool
{
    return $needle === '' || strpos(lower_text($haystack), lower_text($needle)) !== false;
}

function request_string($value): string
{
    return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
}

function valid_filter($value, array $options): string
{
    $value = request_string($value);
    return in_array($value, $options, true) ? $value : '';
}

function render_options(array $options, string $selected): string
{
    $html = '';
    foreach ($options as $index => $option) {
        $value = $index === 0 ? '' : $option;
        $isSelected = $selected === $value || ($index === 0 && $selected === '');
        $html .= '<option value="' . e($value) . '"' . ($isSelected ? ' selected' : '') . '>' . e($option) . '</option>';
    }
    return $html;
}

$q = request_string($_GET['q'] ?? '');
$city = valid_filter($_GET['city'] ?? '', array_slice($filterOptions['cities'], 1));
$category = valid_filter($_GET['category'] ?? '', array_slice($filterOptions['categories'], 1));
$brand = valid_filter($_GET['brand'] ?? '', array_slice($filterOptions['brands'], 1));
$condition = valid_filter($_GET['condition'] ?? '', array_slice($filterOptions['conditions'], 1));
$testDays = valid_filter($_GET['test_days'] ?? '', array_slice($filterOptions['test_days'], 1));
$warranty = valid_filter($_GET['warranty'] ?? '', array_slice($filterOptions['warranty'], 1));
$payment = valid_filter($_GET['payment'] ?? '', array_slice($filterOptions['payments'], 1));

$filteredProducts = array_values(array_filter($products, function (array $product) use ($q, $city, $category, $brand, $condition, $testDays, $warranty, $payment): bool {
    $searchable = implode(' ', [
        $product['title'], $product['code'], $product['category'], $product['brand'],
        $product['vehicle'], implode(' ', $product['tags']),
    ]);

    if ($q !== '' && !contains_text($searchable, $q)) {
        return false;
    }
    if ($category !== '' && $product['category'] !== $category) {
        return false;
    }
    if ($brand !== '' && $product['brand'] !== $brand) {
        return false;
    }

    foreach ($product['offers'] as $offer) {
        if ($city !== '' && $offer['city'] !== $city) {
            continue;
        }
        if ($condition !== '' && $offer['condition'] !== $condition) {
            continue;
        }
        if ($testDays !== '' && $offer['test'] !== $testDays) {
            continue;
        }
        if ($warranty !== '' && $offer['warranty'] !== $warranty) {
            continue;
        }
        if ($payment !== '' && $offer['payment'] !== $payment) {
            continue;
        }
        return true;
    }

    return $city === '' && $condition === '' && $testDays === '' && $warranty === '' && $payment === '';
}));

$totalOffers = 0;
foreach ($products as $product) {
    $totalOffers += count($product['offers']);
}

$filteredOfferCount = 0;
foreach ($filteredProducts as $product) {
    foreach ($product['offers'] as $offer) {
        if ($city !== '' && $offer['city'] !== $city) {
            continue;
        }
        if ($condition !== '' && $offer['condition'] !== $condition) {
            continue;
        }
        if ($testDays !== '' && $offer['test'] !== $testDays) {
            continue;
        }
        if ($warranty !== '' && $offer['warranty'] !== $warranty) {
            continue;
        }
        if ($payment !== '' && $offer['payment'] !== $payment) {
            continue;
        }
        $filteredOfferCount++;
    }
}

$activeFilterCount = count(array_filter([$city, $category, $brand, $condition, $testDays, $warranty, $payment]));
$hasSearch = $q !== '' || $activeFilterCount > 0;
$firstPreviewProduct = $products[0];
$secondPreviewProduct = $products[3];
$thirdPreviewProduct = $products[5];

// داده‌ای که به پنجره جزئیات می‌رود با فیلترهای انتخاب‌شده همگام می‌ماند.
$clientProducts = [];
foreach ($filteredProducts as $product) {
    $clientProduct = $product;
    $clientProduct['offers'] = array_values(array_filter($product['offers'], function (array $offer) use ($city, $condition, $testDays, $warranty, $payment): bool {
        return ($city === '' || $offer['city'] === $city)
            && ($condition === '' || $offer['condition'] === $condition)
            && ($testDays === '' || $offer['test'] === $testDays)
            && ($warranty === '' || $offer['warranty'] === $warranty)
            && ($payment === '' || $offer['payment'] === $payment);
    }));
    $clientProducts[] = $clientProduct;
}
$productJson = json_encode($clientProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($productJson === false) {
    $productJson = '[]';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2467e8">
    <meta name="description" content="موجودی‌کالا؛ جست‌وجوی سریع و شفاف قطعات و کالا از میان فروشندگان مختلف.">
    <title>موجودی‌کالا | جست‌وجوی هوشمند کالا</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-shell">
            <a class="brand" href="index.php" aria-label="صفحه اصلی موجودی‌کالا">
                <span class="brand-mark">
                    <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="m5.5 10.5 10.5-5 10.5 5v11L16 27 5.5 21.5v-11Z" fill="white" opacity=".97"/>
                        <path d="m5.5 10.5 10.5 6 10.5-6M16 16.5V27" stroke="#2467e8" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <span class="brand-name">موجودی‌کالا</span>
                    <span class="brand-tagline">پیدا کن، مقایسه کن، مطمئن شو</span>
                </span>
            </a>

            <nav class="main-nav" id="mainNav" aria-label="ناوبری اصلی">
                <a href="#home">خانه</a>
                <a href="#search">جست‌وجوی کالا</a>
                <a href="#how-it-works">راهنمای سامانه</a>
                <a href="#about">درباره ما</a>
            </nav>

            <div class="header-actions">
                <a class="header-login" href="#seller-cta">ورود فروشندگان</a>
                <a class="btn btn-primary" href="#seller-cta">ثبت فروشگاه</a>
                <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="باز کردن منو" aria-controls="mainNav" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow">بازار شفاف قطعات و کالا</span>
                    <h1>کالای موردنظرتان را<br><em>سریع‌تر</em> پیدا کنید.</h1>
                    <p class="hero-description">موجودی‌کالا کمک می‌کند نام، کد یا مدل کالای خود را جست‌وجو کنید و قیمت‌های به‌روز فروشندگان مختلف را یک‌جا ببینید.</p>
                    <div class="hero-actions">
                        <a class="btn btn-primary" href="#search">
                            شروع جست‌وجو
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a class="btn btn-light" href="#how-it-works">چطور کار می‌کند؟</a>
                    </div>
                    <div class="hero-note">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        اطلاعات این صفحه نمونه و مناسب ارائه اولیه است
                    </div>
                </div>

                <div class="hero-visual" aria-label="پیش‌نمایش نتایج موجودی کالا">
                    <div class="visual-glow"></div>
                    <div class="visual-ring"></div>
                    <div class="floating-stat top">
                        <span class="floating-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12a8 8 0 1 0 16 0 8 8 0 0 0-16 0Z"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="floating-stat-copy"><strong>قیمت‌های قابل مقایسه</strong><span>به‌روزرسانی روزانه</span></span>
                    </div>
                    <div class="floating-stat bottom">
                        <span class="floating-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20V10l8-6 8 6v10"/><path d="M9 20v-6h6v6M3 20h18" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="floating-stat-copy"><strong>فروشگاه‌های منتخب</strong><span>از ۵ شهر ایران</span></span>
                    </div>
                    <div class="dashboard-preview">
                        <div class="preview-top">
                            <div class="preview-title"><span class="preview-title-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 7 8-4 8 4v10l-8 4-8-4V7Z"/><path d="m4 7 8 4 8-4M12 11v10"/></svg></span>نمایش زنده موجودی</div>
                            <span class="live-dot">آنلاین</span>
                        </div>
                        <div class="preview-search">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5" stroke-linecap="round"/></svg>
                            <span>سرسیلندر MVM 110</span>
                        </div>
                        <div class="preview-list">
                            <div class="preview-item">
                                <div class="preview-thumb"><img src="<?= e($firstPreviewProduct['image']) ?>" alt=""></div>
                                <div class="preview-item-copy"><div class="preview-item-title"><?= e($firstPreviewProduct['title']) ?></div><div class="preview-item-meta">۳ فروشنده · تهران، کرج</div></div>
                                <strong class="preview-price">۱٬۱۲۰٬۰۰۰</strong>
                            </div>
                            <div class="preview-item">
                                <div class="preview-thumb"><img src="<?= e($secondPreviewProduct['image']) ?>" alt=""></div>
                                <div class="preview-item-copy"><div class="preview-item-title"><?= e($secondPreviewProduct['title']) ?></div><div class="preview-item-meta">۲ فروشنده · تهران، شیراز</div></div>
                                <strong class="preview-price">۲٬۱۰۰٬۰۰۰</strong>
                            </div>
                            <div class="preview-item">
                                <div class="preview-thumb"><img src="<?= e($thirdPreviewProduct['image']) ?>" alt=""></div>
                                <div class="preview-item-copy"><div class="preview-item-title"><?= e($thirdPreviewProduct['title']) ?></div><div class="preview-item-meta">۲ فروشنده · تهران، مشهد</div></div>
                                <strong class="preview-price">۲٬۱۹۰٬۰۰۰</strong>
                            </div>
                        </div>
                        <div class="preview-footer"><span>نتیجه‌های پیدا شده</span><strong><?= persian_digits($totalOffers) ?> پیشنهاد</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-strip" aria-label="آمار نمونه سامانه">
            <div class="container">
                <div class="stats-card">
                    <div class="stat"><span class="stat-number"><?= persian_digits(count($products)) ?></span><span class="stat-label">کالای نمونه<br>برای جست‌وجو</span></div>
                    <div class="stat"><span class="stat-number"><?= persian_digits($totalOffers) ?></span><span class="stat-label">پیشنهاد فعال<br>در این دموی نمونه</span></div>
                    <div class="stat"><span class="stat-number"><?= persian_digits(count($filterOptions['cities']) - 1) ?></span><span class="stat-label">شهر تحت پوشش<br>برای شروع کار</span></div>
                </div>
            </div>
        </section>

        <section class="search-section" id="search">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">جست‌وجوی ساده و حرفه‌ای</span>
                        <h2>کالا را با نام، کد یا خودرو پیدا کنید</h2>
                    </div>
                    <p>برای دیدن نمونه نتیجه‌ها، یکی از عبارت‌های پیشنهادی را انتخاب کنید یا فیلترهای حرفه‌ای را باز کنید.</p>
                </div>

                <form class="search-box" method="get" action="index.php#results" id="searchForm">
                    <div class="search-row">
                        <label class="search-control search-main" for="searchInput">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.7"/><path d="m16.2 16.2 4.3 4.3" stroke-linecap="round"/></svg>
                            <input id="searchInput" name="q" type="search" value="<?= e($q) ?>" placeholder="مثلاً: سرسیلندر MVM 110 یا کد 100071" autocomplete="off">
                        </label>
                        <label class="search-control select-wrap" for="citySelect">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <select id="citySelect" name="city" aria-label="انتخاب شهر">
                                <?= render_options($filterOptions['cities'], $city) ?>
                            </select>
                        </label>
                        <button class="search-submit" type="submit">
                            جست‌وجوی کالا
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="search-help">
                        <div class="quick-searches">
                            <span>جست‌وجوی سریع:</span>
                            <a href="index.php?q=سرسیلندر#results">سرسیلندر</a>
                            <a href="index.php?q=دیسک%20و%20صفحه#results">دیسک و صفحه</a>
                            <a href="index.php?q=چراغ%20جلو#results">چراغ جلو</a>
                            <a href="index.php?q=باتری#results">باتری</a>
                        </div>
                        <button class="advanced-toggle" id="advancedToggle" type="button" aria-expanded="<?= $activeFilterCount > 0 ? 'true' : 'false' ?>" aria-controls="advancedFields">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M7 12h10M10 17h4" stroke-linecap="round"/></svg>
                            جست‌وجوی حرفه‌ای
                            <svg class="toggle-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="advanced-fields<?= $activeFilterCount > 0 ? ' is-open' : '' ?>" id="advancedFields">
                        <label class="field-label">دسته‌بندی
                            <span class="select-wrap"><select name="category" aria-label="فیلتر دسته‌بندی"><?= render_options($filterOptions['categories'], $category) ?></select></span>
                        </label>
                        <label class="field-label">برند / سازنده
                            <span class="select-wrap"><select name="brand" aria-label="فیلتر برند"><?= render_options($filterOptions['brands'], $brand) ?></select></span>
                        </label>
                        <label class="field-label">وضعیت کالا
                            <span class="select-wrap"><select name="condition" aria-label="فیلتر وضعیت کالا"><?= render_options($filterOptions['conditions'], $condition) ?></select></span>
                        </label>
                        <label class="field-label">مهلت تست
                            <span class="select-wrap"><select name="test_days" aria-label="فیلتر مهلت تست"><?= render_options($filterOptions['test_days'], $testDays) ?></select></span>
                        </label>
                        <label class="field-label">گارانتی
                            <span class="select-wrap"><select name="warranty" aria-label="فیلتر گارانتی"><?= render_options($filterOptions['warranty'], $warranty) ?></select></span>
                        </label>
                        <label class="field-label">نوع پرداخت
                            <span class="select-wrap"><select name="payment" aria-label="فیلتر نوع پرداخت"><?= render_options($filterOptions['payments'], $payment) ?></select></span>
                        </label>
                        <?php if ($activeFilterCount > 0): ?>
                            <div class="active-filters" style="align-self:end; padding-bottom:12px;">فیلتـر فعال: <?= persian_digits($activeFilterCount) ?></div>
                            <a class="clear-link" style="align-self:end; padding-bottom:12px;" href="index.php#search">پاک کردن فیلترها</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="results-section" id="results">
            <div class="container">
                <div class="results-top">
                    <div>
                        <span class="section-kicker">فهرست موجودی</span>
                        <h2><?= $hasSearch ? 'نتایج جست‌وجوی شما' : 'آخرین کالاهای ثبت‌شده' ?></h2>
                        <p class="results-caption">
                            <?php if ($hasSearch): ?>
                                برای <strong><?= e($q !== '' ? $q : 'فیلترهای انتخاب‌شده') ?></strong>، <?= persian_digits(count($filteredProducts)) ?> کالا و <?= persian_digits($filteredOfferCount) ?> پیشنهاد پیدا شد.
                            <?php else: ?>
                                قیمت و شرایط نمونه از فروشگاه‌های منتخب؛ جزئیات هر کالا را باز کنید.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="results-tools">
                        <span class="fresh-label">به‌روزرسانی امروز</span>
                        <?php if ($hasSearch): ?><a class="clear-link" href="index.php#results">نمایش همه</a><?php endif; ?>
                    </div>
                </div>

                <?php if (count($filteredProducts) > 0): ?>
                    <div class="results-grid">
                        <?php foreach ($filteredProducts as $product):
                            $offers = $product['offers'];
                            $prices = array_column($offers, 'price');
                            $minPrice = min($prices);
                            $cities = array_values(array_unique(array_column($offers, 'city')));
                            $visibleOffers = 0;
                            foreach ($offers as $offer) {
                                if ($city !== '' && $offer['city'] !== $city) continue;
                                if ($condition !== '' && $offer['condition'] !== $condition) continue;
                                if ($testDays !== '' && $offer['test'] !== $testDays) continue;
                                if ($warranty !== '' && $offer['warranty'] !== $warranty) continue;
                                if ($payment !== '' && $offer['payment'] !== $payment) continue;
                                $visibleOffers++;
                            }
                        ?>
                            <article class="product-card">
                                <div class="product-media">
                                    <img src="<?= e($product['image']) ?>" alt="تصویر آزمایشی <?= e($product['title']) ?>" loading="lazy">
                                    <span class="stock-badge"><?= persian_digits($visibleOffers) ?> پیشنهاد</span>
                                </div>
                                <div class="product-content">
                                    <div class="product-content-top">
                                        <div class="product-title-wrap">
                                            <span class="product-category"><?= e($product['category']) ?></span>
                                            <h3 class="product-title" title="<?= e($product['title']) ?>"><?= e($product['title']) ?></h3>
                                        </div>
                                        <span class="product-code">کد <strong><?= persian_digits($product['code']) ?></strong></span>
                                    </div>
                                    <p class="product-vehicle"><?= e($product['vehicle']) ?></p>
                                    <div class="product-info">
                                        <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><?= e(implode('، ', $cities)) ?></span>
                                        <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12a8 8 0 1 0 16 0 8 8 0 0 0-16 0Z"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/></svg>تأیید اولیه فروشنده</span>
                                    </div>
                                    <div class="product-bottom">
                                        <div><span class="price-label">شروع قیمت از</span><strong class="price-value"><?= e(format_price($minPrice)) ?></strong></div>
                                        <button class="product-detail-btn js-product-detail" type="button" data-product-id="<?= e($product['id']) ?>">مشاهده قیمت‌ها</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5"/><path d="m16 16 4.5 4.5M8.5 10.8h4.6" stroke-linecap="round"/></svg></div>
                        <h3>کالایی با این مشخصات پیدا نشد</h3>
                        <p>عبارت جست‌وجو یا فیلترها را کمی تغییر دهید تا پیشنهادهای بیشتری ببینید.</p>
                        <a class="btn btn-light" href="index.php#search">شروع جست‌وجوی تازه</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="info-section" id="about">
            <div class="container">
                <div class="section-heading center">
                    <span class="section-kicker">چرا موجودی‌کالا؟</span>
                    <h2>تصمیم خرید، با اطلاعات روشن‌تر</h2>
                    <p>به جای تماس‌های متعدد، نتیجه‌های مرتب و قابل مقایسه را یک‌جا ببینید و انتخاب مناسب‌تری داشته باشید.</p>
                </div>
                <div class="benefits-grid">
                    <article class="benefit-card">
                        <div class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5M8 11h6" stroke-linecap="round"/></svg></div>
                        <h3>جست‌وجوی هدفمند</h3>
                        <p>با نام، کد کالا، برند، شهر و وضعیت، سریع‌تر به نتیجه‌ای که لازم دارید برسید.</p>
                    </article>
                    <article class="benefit-card">
                        <div class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5" stroke-linecap="round"/><path d="m16 17 2 2 3-4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        <h3>مقایسه در یک نگاه</h3>
                        <p>قیمت، شهر، مهلت تست و گارانتی پیشنهادهای مختلف را کنار هم ببینید.</p>
                    </article>
                    <article class="benefit-card">
                        <div class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        <h3>تجربه شفاف‌تر</h3>
                        <p>اطلاعات فروشنده و هشدارهای لازم را قبل از تماس و تصمیم‌گیری مشاهده کنید.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="process-section" id="how-it-works">
            <div class="container process-layout">
                <div class="process-copy">
                    <span class="section-kicker">روند استفاده</span>
                    <h2>از جست‌وجو تا انتخاب، سه قدم ساده</h2>
                    <p>این جریان برای نسخه نمایشی آماده شده و در نسخه نهایی می‌تواند به حساب کاربری، پنل فروشنده و دیتابیس متصل شود.</p>
                    <a class="process-link" href="#search">امتحانش کنید <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                </div>
                <div class="steps">
                    <article class="step"><span class="step-number">۱</span><h3>کالا را جست‌وجو کنید</h3><p>نام، کد یا مدل خودرو را وارد کنید و در صورت نیاز فیلترها را فعال کنید.</p></article>
                    <article class="step"><span class="step-number">۲</span><h3>پیشنهادها را مقایسه کنید</h3><p>قیمت و شرایط فروشندگان مختلف را مرتب و یک‌جا ببینید.</p></article>
                    <article class="step"><span class="step-number">۳</span><h3>با فروشنده تماس بگیرید</h3><p>جزئیات پیشنهاد را باز کنید و برای هماهنگی مستقیم تماس بگیرید.</p></article>
                </div>
            </div>
        </section>

        <section class="seller-cta" id="seller-cta">
            <div class="container">
                <div class="seller-card">
                    <div class="seller-copy">
                        <span class="section-kicker">برای فروشندگان و تأمین‌کنندگان</span>
                        <h2>موجودی فروشگاهت را بیشتر دیده‌شده کن</h2>
                        <p>در نسخه اصلی، فروشنده می‌تواند کالا، قیمت، موجودی و شرایط فروش خود را از پنل اختصاصی مدیریت کند.</p>
                    </div>
                    <a class="btn btn-white" href="#search">مشاهده دموی سامانه <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-about">
                    <a class="brand" href="#home" aria-label="بازگشت به ابتدای صفحه">
                        <span class="brand-mark"><svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m5.5 10.5 10.5-5 10.5 5v11L16 27 5.5 21.5v-11Z" fill="white"/><path d="m5.5 10.5 10.5 6 10.5-6M16 16.5V27" stroke="#2467e8" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                        <span class="brand-copy"><span class="brand-name">موجودی‌کالا</span><span class="brand-tagline">پیدا کن، مقایسه کن، مطمئن شو</span></span>
                    </a>
                    <p>یک دموی فارسی و واکنش‌گرا برای جست‌وجو و مقایسه موجودی کالا میان فروشندگان.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-links-group"><strong>دسترسی سریع</strong><a href="#search">جست‌وجوی کالا</a><a href="#how-it-works">روند استفاده</a><a href="#about">مزیت‌های سامانه</a></div>
                    <div class="footer-links-group"><strong>راهنما</strong><a href="#seller-cta">پنل فروشنده</a><a href="#home">درباره دموی نمونه</a><a href="#search">سؤالات متداول</a></div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© <?= date('Y') ?> موجودی‌کالا — نسخه نمایشی</span>
                <span class="footer-warning"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5M12 17h.01" stroke-linecap="round"/></svg>قبل از هر معامله، اطلاعات و کالا را بررسی کنید.</span>
            </div>
        </div>
    </footer>

    <div class="modal-backdrop" id="productModal" role="dialog" aria-modal="true" aria-labelledby="modalProductTitle" aria-hidden="true">
        <div class="product-modal">
            <div class="modal-header">
                <div class="modal-product-intro">
                    <div class="modal-image"><img id="modalProductImage" src="assets/images/cylinder-head.svg" alt=""></div>
                    <div><h2 id="modalProductTitle">جزئیات کالا</h2><p id="modalProductMeta">پیشنهادهای فروشندگان</p></div>
                </div>
                <button class="modal-close" id="modalClose" type="button" aria-label="بستن پنجره"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg></button>
            </div>
            <div class="modal-body">
                <p class="modal-description" id="modalProductDescription"></p>
                <div class="offer-list" id="offerList"></div>
                <div class="modal-disclaimer"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5M12 17h.01" stroke-linecap="round"/></svg><span>این اطلاعات آزمایشی است. سامانه در معامله بین خریدار و فروشنده مسئولیتی ندارد؛ قبل از پرداخت، کالا، قیمت و شرایط را بررسی کنید.</span></div>
            </div>
        </div>
    </div>

    <script>window.MOJODI_PRODUCTS = <?= $productJson ?>;</script>
    <script src="assets/js/app.js" defer></script>
</body>
</html>
