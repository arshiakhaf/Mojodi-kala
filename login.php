<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

function auth_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function auth_input($value): string
{
    return is_scalar($value) ? trim((string) $value) : '';
}

function normalize_phone(string $phone): string
{
    return strtr(preg_replace('/[^0-9۰-۹٠-٩]+/u', '', $phone) ?? '', [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function redirect_after_auth(): void
{
    $redirect = auth_input($_GET['redirect'] ?? $_POST['redirect'] ?? '');
    $allowed = ['dashboard.php', 'index.php'];
    $target = in_array($redirect, $allowed, true) ? $redirect : 'dashboard.php';
    header('Location: ' . $target);
    exit;
}

$mode = auth_input($_GET['mode'] ?? $_POST['mode'] ?? 'login');
$mode = $mode === 'register' ? 'register' : 'login';
$errors = [];
$success = '';
$name = auth_input($_POST['name'] ?? '');
$phone = normalize_phone(auth_input($_POST['phone'] ?? ''));
$city = auth_input($_POST['city'] ?? 'تهران');
$redirect = auth_input($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if (app_current_user()) {
    redirect_after_auth();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!app_verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'نشست فرم منقضی شده است. صفحه را تازه کنید و دوباره تلاش کنید.';
    } elseif ($mode === 'login') {
        $password = auth_input($_POST['password'] ?? '');
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            $errors[] = 'شماره موبایل معتبر وارد کنید.';
        }
        if ($password === '') {
            $errors[] = 'رمز عبور را وارد کنید.';
        }
        if (!$errors && !app_login($phone, $password)) {
            $errors[] = 'شماره موبایل یا رمز عبور صحیح نیست.';
        }
        if (!$errors) {
            redirect_after_auth();
        }
    } else {
        $password = auth_input($_POST['password'] ?? '');
        $passwordConfirm = auth_input($_POST['password_confirm'] ?? '');
        $allowedCities = array_slice($filterOptions['cities'], 1);
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
        if ($nameLength < 3 || $nameLength > 80) {
            $errors[] = 'نام فروشگاه یا نام شما باید بین ۳ تا ۸۰ حرف باشد.';
        }
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            $errors[] = 'شماره موبایل معتبر وارد کنید.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'تکرار رمز عبور با رمز اصلی یکسان نیست.';
        }
        if (!in_array($city, $allowedCities, true)) {
            $errors[] = 'شهر انتخاب‌شده معتبر نیست.';
        }
        if (!$errors) {
            $result = app_register_user($name, $phone, $password, $city);
            if (!empty($result['ok'])) {
                app_login($phone, $password);
                redirect_after_auth();
            }
            $errors[] = (string) $result['message'];
        }
    }
}

$pageTitle = $mode === 'register' ? 'ثبت فروشگاه' : 'ورود فروشندگان';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2467e8">
    <title><?= auth_e($pageTitle) ?> | موجودی‌کالا</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-layout">
            <aside class="auth-aside">
                <a class="brand" href="index.php" aria-label="بازگشت به موجودی‌کالا">
                    <span class="brand-mark"><svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m5.5 10.5 10.5-5 10.5 5v11L16 27 5.5 21.5v-11Z" fill="white"/><path d="m5.5 10.5 10.5 6 10.5-6M16 16.5V27" stroke="#2467e8" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                    <span class="brand-copy"><span class="brand-name">موجودی‌کالا</span><span class="brand-tagline">پیدا کن، مقایسه کن، مطمئن شو</span></span>
                </a>
                <div class="auth-aside-copy">
                    <h1>موجودی فروشگاهت را ساده‌تر مدیریت کن.</h1>
                    <p>پیشنهادهای خود را ثبت کن تا خریداران بیشتری کالا، قیمت و شرایط فروش تو را ببینند.</p>
                </div>
                <div class="auth-feature-list">
                    <div class="auth-feature"><span class="auth-feature-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12a8 8 0 1 0 16 0 8 8 0 0 0-16 0Z"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>نمایش در جست‌وجوی خریداران</div>
                    <div class="auth-feature"><span class="auth-feature-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5M4 19h16" stroke-linecap="round"/><path d="m7 15 3-4 3 2 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>مدیریت قیمت و شرایط فروش</div>
                    <div class="auth-feature"><span class="auth-feature-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 4h10v16H7z"/><path d="M10 7h4M10 17h4" stroke-linecap="round"/></svg></span>دسترسی مناسب در موبایل و وب</div>
                </div>
            </aside>

            <section class="auth-content">
                <div class="auth-top">
                    <div><h2><?= auth_e($pageTitle) ?></h2><p><?= $mode === 'register' ? 'فروشگاه خود را در چند قدم معرفی کنید.' : 'برای مدیریت پیشنهادهای فروشگاه وارد شوید.' ?></p></div>
                    <a class="auth-switch" href="<?= $mode === 'register' ? 'login.php' : 'login.php?mode=register' ?>"><?= $mode === 'register' ? 'ورود به حساب' : 'ثبت فروشگاه' ?></a>
                </div>

                <?php if ($errors): ?>
                    <div class="portal-alert error"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16h.01" stroke-linecap="round"/></svg><span><?= auth_e(implode(' ', $errors)) ?></span></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?><div class="portal-alert success"><?= auth_e($success) ?></div><?php endif; ?>

                <form class="auth-form" method="post" action="login.php<?= $mode === 'register' ? '?mode=register' : '' ?>">
                    <input type="hidden" name="csrf_token" value="<?= auth_e(app_csrf_token()) ?>">
                    <input type="hidden" name="mode" value="<?= auth_e($mode) ?>">
                    <input type="hidden" name="redirect" value="<?= auth_e($redirect) ?>">
                    <?php if ($mode === 'register'): ?>
                        <label class="auth-field">نام فروشگاه یا نام شما
                            <input type="text" name="name" value="<?= auth_e($name) ?>" required minlength="3" maxlength="80" placeholder="مثلاً یدک‌پارت مرکزی" autocomplete="organization">
                        </label>
                    <?php endif; ?>
                    <label class="auth-field">شماره موبایل
                        <input type="tel" name="phone" value="<?= auth_e($phone) ?>" required inputmode="numeric" pattern="09[0-9]{9}" placeholder="09120000001" autocomplete="tel">
                    </label>
                    <?php if ($mode === 'register'): ?>
                        <label class="auth-field">شهر فعالیت
                            <select name="city" required><?= app_render_options($filterOptions['cities'], $city) ?></select>
                        </label>
                    <?php endif; ?>
                    <label class="auth-field">رمز عبور
                        <input type="password" name="password" required minlength="6" placeholder="حداقل ۶ کاراکتر" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>">
                    </label>
                    <?php if ($mode === 'register'): ?>
                        <label class="auth-field">تکرار رمز عبور
                            <input type="password" name="password_confirm" required minlength="6" placeholder="رمز عبور را دوباره وارد کنید" autocomplete="new-password">
                        </label>
                    <?php endif; ?>
                    <button class="auth-submit" type="submit"><?= $mode === 'register' ? 'ساخت حساب فروشنده' : 'ورود به پنل فروشنده' ?></button>
                </form>

                <?php if ($mode === 'login'): ?>
                    <div class="auth-demo"><strong>ورود آزمایشی برای ارائه</strong>شماره: <code>09120000001</code> &nbsp; رمز: <code>demo1234</code></div>
                <?php else: ?>
                    <div class="auth-demo"><strong>نسخه نمایشی</strong>ثبت‌نام به دیتابیس SQLite وصل می‌شود و پس از ورود می‌توانید پیشنهاد کالا ثبت کنید.</div>
                <?php endif; ?>
                <div class="auth-footer">با ورود یا ثبت‌نام، قوانین استفاده از سامانه را می‌پذیرید. اطلاعات این محیط برای نمایش اولیه و آزمایشی است.</div>
            </section>
        </div>
    </main>
</body>
</html>
