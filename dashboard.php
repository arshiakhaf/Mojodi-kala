<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

function dash_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dash_digits($value): string
{
    return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

function dash_price($value): string
{
    return dash_digits(number_format((int) $value, 0, '.', '٬')) . ' تومان';
}

$user = app_current_user();
if (!$user) {
    header('Location: login.php?redirect=dashboard.php');
    exit;
}

$editId = is_numeric($_GET['edit'] ?? null) ? (int) $_GET['edit'] : 0;
$editingOffer = $editId > 0 ? app_get_user_offer($editId, (int) ($user['id'] ?? 0)) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!app_verify_csrf($_POST['csrf_token'] ?? null)) {
        app_flash('error', 'نشست فرم منقضی شده است؛ دوباره تلاش کنید.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $offerId = is_numeric($_POST['offer_id'] ?? null) ? (int) $_POST['offer_id'] : 0;
        $deleted = app_delete_offer($offerId, (int) ($user['id'] ?? 0));
        app_flash($deleted ? 'success' : 'error', $deleted ? 'پیشنهاد حذف شد.' : 'حذف پیشنهاد انجام نشد.');
    } elseif (($_POST['action'] ?? '') === 'add_offer') {
        $result = app_add_offer($_POST, $user);
        app_flash(!empty($result['ok']) ? 'success' : 'error', (string) $result['message']);
    } elseif (($_POST['action'] ?? '') === 'update_offer') {
        $offerId = is_numeric($_POST['offer_id'] ?? null) ? (int) $_POST['offer_id'] : 0;
        $result = app_update_offer($_POST, $user, $offerId);
        app_flash(!empty($result['ok']) ? 'success' : 'error', (string) $result['message']);
    }
    header('Location: dashboard.php');
    exit;
}

$flash = app_take_flash();
$allProducts = app_products();
$userOffers = app_user_offers((int) ($user['id'] ?? 0));
$activeOffers = 0;
foreach ($userOffers as $offer) {
    if ((int) $offer['verified'] === 1) $activeOffers++;
}
$cityOptions = array_slice($filterOptions['cities'], 1);
$productsWithOffers = count($allProducts);
$formProductId = $editingOffer['product_id'] ?? '';
$formCity = $editingOffer['city'] ?? (string) $user['city'];
$formPrice = $editingOffer['price'] ?? '';
$formCondition = $editingOffer['condition'] ?? '';
$formWarranty = $editingOffer['warranty'] ?? '';
$formTestDays = $editingOffer['test_days'] ?? '';
$formPayment = $editingOffer['payment'] ?? '';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2467e8">
    <title>پنل فروشنده | موجودی‌کالا</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-page">
    <header class="portal-header">
        <div class="portal-container portal-header-inner">
            <a class="brand" href="index.php" aria-label="موجودی‌کالا">
                <span class="brand-mark"><svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m5.5 10.5 10.5-5 10.5 5v11L16 27 5.5 21.5v-11Z" fill="white"/><path d="m5.5 10.5 10.5 6 10.5-6M16 16.5V27" stroke="#2467e8" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                <span class="brand-copy"><span class="brand-name">موجودی‌کالا</span><span class="brand-tagline">پنل فروشندگان</span></span>
            </a>
            <nav class="portal-nav" aria-label="منوی پنل"><a href="index.php">مشاهده سایت</a><a href="#add-offer">ثبت پیشنهاد</a><a href="#offers">پیشنهادهای من</a></nav>
            <div class="portal-user"><span class="portal-user-avatar"><?= dash_e(function_exists('mb_substr') ? mb_substr((string) $user['name'], 0, 1, 'UTF-8') : substr((string) $user['name'], 0, 1)) ?></span><span><?= dash_e($user['name']) ?></span><a class="portal-link" href="logout.php" aria-label="خروج">خروج</a></div>
        </div>
    </header>

    <main class="portal-main">
        <div class="portal-container">
            <span class="portal-kicker">پنل مدیریت فروشگاه</span>
            <h1 class="portal-title">سلام، <?= dash_e($user['name']) ?> 👋</h1>
            <p class="portal-lead">موجودی و پیشنهادهای فروشگاهت را از یک‌جا مدیریت کن. هر پیشنهاد قبل از نمایش عمومی قابل بررسی است.</p>

            <?php if ($flash): ?><div class="portal-alert <?= dash_e($flash['type']) ?>" style="margin-top:20px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/></svg><span><?= dash_e($flash['message']) ?></span></div><?php endif; ?>

            <div class="portal-stat-grid">
                <div class="portal-stat"><strong><?= dash_digits(count($userOffers)) ?></strong><span>پیشنهاد ثبت‌شده من</span></div>
                <div class="portal-stat"><strong><?= dash_digits($activeOffers) ?></strong><span>پیشنهاد تأییدشده</span></div>
                <div class="portal-stat"><strong><?= dash_digits($productsWithOffers) ?></strong><span>کالای قابل انتخاب</span></div>
            </div>

            <div class="portal-grid">
                <section class="portal-card" id="add-offer">
                    <div class="portal-card-title"><div><h2><?= $editingOffer ? 'ویرایش پیشنهاد' : 'ثبت پیشنهاد جدید' ?></h2><p>قیمت و شرایط موجودی خود را وارد کنید.</p></div><span class="portal-status">پنل فعال</span></div>
                    <form class="portal-form" method="post" action="dashboard.php">
                        <input type="hidden" name="csrf_token" value="<?= dash_e(app_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= $editingOffer ? 'update_offer' : 'add_offer' ?>">
                        <?php if ($editingOffer): ?><input type="hidden" name="offer_id" value="<?= (int) $editingOffer['id'] ?>"><?php endif; ?>
                        <div class="portal-form-grid">
                            <label class="portal-field full">کالا
                                <select name="product_id" required><option value="">انتخاب کالا</option><?php foreach ($allProducts as $product): ?><option value="<?= dash_e($product['id']) ?>"<?= $formProductId === $product['id'] ? ' selected' : '' ?>><?= dash_e($product['title']) ?> — کد <?= dash_digits($product['code']) ?></option><?php endforeach; ?></select>
                            </label>
                            <label class="portal-field">شهر موجودی
                                <select name="city" required><?= app_render_options($filterOptions['cities'], (string) $formCity) ?></select>
                            </label>
                            <label class="portal-field">قیمت به تومان
                                <input type="number" name="price" min="1" step="1000" required placeholder="مثلاً ۱۳۵۰۰۰۰" inputmode="numeric" value="<?= dash_e($formPrice) ?>">
                            </label>
                            <label class="portal-field">وضعیت کالا
                                <select name="condition" required><?= app_render_options(['وضعیت کالا', 'نو', 'دست دوم'], (string) $formCondition) ?></select>
                            </label>
                            <label class="portal-field">گارانتی
                                <select name="warranty" required><?= app_render_options(['وضعیت گارانتی', 'دارد', 'ندارد'], (string) $formWarranty) ?></select>
                            </label>
                            <label class="portal-field">مهلت تست
                                <select name="test_days" required><?= app_render_options(['مهلت تست', 'ندارد', 'یک روز', 'دو روز', 'یک هفته'], (string) $formTestDays) ?></select>
                            </label>
                            <label class="portal-field">نوع پرداخت
                                <select name="payment" required><?= app_render_options(['نوع پرداخت', 'نقدی', 'نقد و اقساط', 'آنلاین'], (string) $formPayment) ?></select>
                            </label>
                        </div>
                        <div class="portal-actions"><button class="btn btn-primary" type="submit"><?= $editingOffer ? 'ذخیره تغییرات' : 'ثبت پیشنهاد' ?></button><?php if ($editingOffer): ?><a class="btn btn-ghost" href="dashboard.php#offers">انصراف</a><?php endif; ?><span class="portal-field hint">پیشنهاد جدید ابتدا با وضعیت «در حال بررسی» ذخیره می‌شود.</span></div>
                    </form>
                </section>

                <aside class="portal-card">
                    <div class="portal-card-title"><div><h2>راهنمای کوتاه</h2><p>برای ارائه بهتر اطلاعات</p></div></div>
                    <div class="detail-side-list">
                        <div class="detail-side-row"><span>نام فروشگاه</span><strong><?= dash_e($user['name']) ?></strong></div>
                        <div class="detail-side-row"><span>شماره تماس</span><strong dir="ltr"><?= dash_e($user['phone']) ?></strong></div>
                        <div class="detail-side-row"><span>شهر اصلی</span><strong><?= dash_e($user['city']) ?></strong></div>
                    </div>
                    <div class="portal-alert info" style="margin:18px 0 0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg><span>قیمت و اطلاعات تماس پس از تأیید مدیریت منتشر می‌شوند.</span></div>
                    <a class="portal-link" href="index.php#search">دیدن نتیجه‌های سایت ←</a>
                </aside>
            </div>

            <section class="portal-card" id="offers" style="margin-top:18px;">
                <div class="portal-card-title"><div><h2>پیشنهادهای من</h2><p>مدیریت موجودی‌های ثبت‌شده در سامانه</p></div><span class="portal-status">به‌روزرسانی خودکار</span></div>
                <?php if ($userOffers): ?>
                    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>کالا</th><th>قیمت</th><th>شهر</th><th>شرایط</th><th>تاریخ</th><th>وضعیت</th><th></th></tr></thead><tbody>
                        <?php foreach ($userOffers as $offer): ?>
                            <tr><td><?= dash_e($offer['product_title']) ?><span class="muted"> · <?= dash_digits($offer['product_code']) ?></span></td><td class="price"><?= dash_price($offer['price']) ?></td><td><?= dash_e($offer['city']) ?></td><td><?= dash_e($offer['condition']) ?> · <?= dash_e($offer['test_days']) ?></td><td><?= dash_digits($offer['offer_date']) ?></td><td><span class="portal-badge<?= (int) $offer['verified'] === 1 ? '' : ' pending' ?>"><?= (int) $offer['verified'] === 1 ? 'فعال' : 'در حال بررسی' ?></span></td><td><a class="portal-link" href="dashboard.php?edit=<?= (int) $offer['id'] ?>#add-offer">ویرایش</a><form style="display:inline; margin-inline-start:8px;" method="post" action="dashboard.php" onsubmit="return confirm('این پیشنهاد حذف شود؟');"><input type="hidden" name="csrf_token" value="<?= dash_e(app_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>"><button class="portal-danger" type="submit">حذف</button></form></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?>
                    <div class="portal-empty">هنوز پیشنهادی ثبت نکرده‌ای. از فرم بالا اولین موجودی خود را اضافه کن.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
