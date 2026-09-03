<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

function product_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function product_digits($value): string
{
    return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

function product_price($value): string
{
    return product_digits(number_format((int) $value, 0, '.', '٬')) . ' تومان';
}

function product_initial(string $name): string
{
    if (function_exists('mb_substr')) return mb_substr($name, 0, 1, 'UTF-8');
    return substr($name, 0, 1);
}

$productId = is_scalar($_GET['id'] ?? null) ? trim((string) $_GET['id']) : '';
$product = app_product($productId);
if (!$product) {
    http_response_code(404);
}
$offers = $product ? $product['offers'] : [];
if ($offers) {
    usort($offers, function (array $left, array $right): int {
        return ((int) $left['price']) <=> ((int) $right['price']);
    });
}
$minPrice = $offers ? min(array_column($offers, 'price')) : 0;
$cities = $offers ? array_values(array_unique(array_column($offers, 'city'))) : [];
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2467e8">
    <meta name="description" content="جزئیات و پیشنهادهای <?= product_e($product['title'] ?? 'کالا') ?> در موجودی‌کالا.">
    <title><?= product_e($product['title'] ?? 'کالا پیدا نشد') ?> | موجودی‌کالا</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-page">
    <header class="portal-header">
        <div class="portal-container portal-header-inner">
            <a class="brand" href="index.php" aria-label="موجودی‌کالا"><span class="brand-mark"><svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m5.5 10.5 10.5-5 10.5 5v11L16 27 5.5 21.5v-11Z" fill="white"/><path d="m5.5 10.5 10.5 6 10.5-6M16 16.5V27" stroke="#2467e8" stroke-width="1.8" stroke-linejoin="round"/></svg></span><span class="brand-copy"><span class="brand-name">موجودی‌کالا</span><span class="brand-tagline">پیدا کن، مقایسه کن، مطمئن شو</span></span></a>
            <nav class="portal-nav" aria-label="منوی سایت"><a href="index.php#search">جست‌وجوی کالا</a><a href="index.php#how-it-works">راهنما</a><a href="login.php">پنل فروشنده</a></nav>
            <a class="btn btn-primary" href="index.php#search">جست‌وجوی تازه</a>
        </div>
    </header>

    <main class="detail-main">
        <div class="portal-container">
            <div class="detail-topbar"><a class="detail-back" href="index.php#results"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>بازگشت به نتایج</a><span class="portal-status">اطلاعات نمونه</span></div>
            <?php if (!$product): ?>
                <section class="portal-card" style="text-align:center; padding:70px 25px;"><h1 class="portal-title">کالا پیدا نشد</h1><p class="portal-lead">شناسه کالا معتبر نیست یا این کالا از فهرست نمونه حذف شده است.</p><a class="btn btn-primary" href="index.php#search" style="margin-top:20px;">بازگشت به جست‌وجو</a></section>
            <?php else: ?>
                <div class="detail-layout">
                    <div>
                        <section class="detail-hero">
                            <div class="detail-image"><img src="<?= product_e($product['image']) ?>" alt="تصویر آزمایشی <?= product_e($product['title']) ?>"></div>
                            <div><span class="detail-category"><?= product_e($product['category']) ?></span><h1><?= product_e($product['title']) ?></h1><span class="detail-code">کد کالا: <?= product_digits($product['code']) ?> · مناسب <?= product_e($product['vehicle']) ?></span><p class="detail-description"><?= product_e($product['description']) ?></p><div class="detail-tags"><?php foreach ($product['tags'] as $tag): ?><span><?= product_e($tag) ?></span><?php endforeach; ?></div></div>
                        </section>
                        <section class="detail-offers">
                            <h2>پیشنهادهای فروشندگان</h2><p class="detail-offers-lead">قیمت‌ها و شرایط ثبت‌شده برای این کالای نمونه را مقایسه کنید.</p>
                            <?php foreach ($offers as $offer): ?>
                                <?php $phone = preg_replace('/\D+/', '', (string) $offer['phone']); $callLink = 'tel:+98' . ltrim($phone, '0'); ?>
                                <div class="detail-offer"><div class="detail-seller"><span class="detail-seller-avatar"><?= product_e(product_initial((string) $offer['seller'])) ?></span><span class="detail-seller-copy"><strong><?= product_e($offer['seller']) ?></strong><span><?= product_e($offer['city']) ?> · <?= !empty($offer['verified']) ? 'تأیید اولیه' : 'در حال بررسی' ?></span></span></div><div class="detail-cell"><small>وضعیت</small><span><?= product_e($offer['condition']) ?></span></div><div class="detail-cell"><small>گارانتی / تست</small><span><?= product_e($offer['warranty']) ?> · <?= product_e($offer['test']) ?></span></div><strong class="detail-price"><?= product_e(product_price($offer['price'])) ?></strong><a class="detail-call" href="<?= product_e($callLink) ?>" aria-label="تماس با <?= product_e($offer['seller']) ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M7 4h3l1.5 4-2 1.5a13 13 0 0 0 5 5l1.5-2 4 1.5v3c0 1.1-.9 2-2 2C11.4 19.8 4.2 12.6 4 5.9 4 4.9 5 4 6 4h1Z" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
                            <?php endforeach; ?>
                            <div class="detail-notice"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5M12 17h.01" stroke-linecap="round"/></svg><span>این داده‌ها آزمایشی هستند. قبل از پرداخت، اصالت کالا، قیمت، موجودی و شرایط معامله را مستقیماً با فروشنده بررسی کنید.</span></div>
                        </section>
                    </div>
                    <aside class="detail-side-card"><h2>خلاصه کالا</h2><div class="detail-side-list"><div class="detail-side-row"><span>تعداد پیشنهادها</span><strong><?= product_digits(count($offers)) ?></strong></div><div class="detail-side-row"><span>شروع قیمت از</span><strong><?= product_e(product_price($minPrice)) ?></strong></div><div class="detail-side-row"><span>شهرهای موجود</span><strong><?= product_e(implode('، ', $cities)) ?></strong></div><div class="detail-side-row"><span>وضعیت ثبت</span><strong style="color:#15a99a;">به‌روز</strong></div></div><a class="btn btn-primary" href="index.php#search" style="width:100%; margin-top:22px;">جست‌وجوی کالای دیگر</a></aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
