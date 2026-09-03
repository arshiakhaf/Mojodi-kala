<?php
/**
 * هسته نسخه کامل موجودی‌کالا
 *
 * SQLite برای نسخه دموی قابل حمل استفاده می‌شود. اگر هاست SQLite نداشته باشد،
 * سایت عمومی همچنان با داده‌های نمونه‌ی data.php بالا می‌آید و پنل فروشنده
 * پیام مناسب نمایش می‌دهد.
 */

declare(strict_types=1);

require_once __DIR__ . '/data.php';

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $sessionDirectory = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionDirectory)) {
        @mkdir($sessionDirectory, 0775, true);
    }
    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) {
        session_save_path($sessionDirectory);
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_database_path(): string
{
    return dirname(__DIR__) . '/storage/mojodi.sqlite';
}

function app_render_options(array $options, string $selected): string
{
    $html = '';
    foreach ($options as $index => $option) {
        $value = $index === 0 ? '' : $option;
        $isSelected = $selected === $value || ($index === 0 && $selected === '');
        $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . ($isSelected ? ' selected' : '') . '>' . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
}

function app_db(): ?PDO
{
    static $db;
    static $initialized = false;

    if ($initialized) {
        return $db instanceof PDO ? $db : null;
    }
    $initialized = true;

    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        return null;
    }

    try {
        $storage = dirname(__DIR__) . '/storage';
        if (!is_dir($storage)) {
            @mkdir($storage, 0775, true);
        }
        $db = new PDO('sqlite:' . app_database_path());
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA busy_timeout = 3000');
        app_install_schema($db);
        app_seed_database($db);
        return $db;
    } catch (Throwable $exception) {
        error_log('Mojodi-kala database unavailable: ' . $exception->getMessage());
        $db = null;
        return null;
    }
}

function app_install_schema(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    city TEXT NOT NULL DEFAULT 'تهران',
    role TEXT NOT NULL DEFAULT 'seller',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS products (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    code TEXT NOT NULL UNIQUE,
    category TEXT NOT NULL,
    brand TEXT NOT NULL,
    vehicle TEXT NOT NULL,
    image TEXT NOT NULL,
    description TEXT NOT NULL,
    tags TEXT NOT NULL DEFAULT '[]',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS offers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id TEXT NOT NULL,
    seller_id INTEGER,
    seller TEXT NOT NULL,
    phone TEXT NOT NULL,
    city TEXT NOT NULL,
    price INTEGER NOT NULL CHECK (price > 0),
    condition TEXT NOT NULL,
    warranty TEXT NOT NULL,
    test_days TEXT NOT NULL,
    payment TEXT NOT NULL,
    offer_date TEXT NOT NULL,
    verified INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (product_id, seller, phone, price, offer_date)
);
CREATE INDEX IF NOT EXISTS idx_products_code ON products(code);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_offers_product ON offers(product_id);
CREATE INDEX IF NOT EXISTS idx_offers_city ON offers(city);
CREATE INDEX IF NOT EXISTS idx_offers_seller ON offers(seller_id);
SQL);
}

function app_seed_database(PDO $db): void
{
    $count = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $insertProduct = $db->prepare('INSERT OR IGNORE INTO products (id, title, code, category, brand, vehicle, image, description, tags) VALUES (:id, :title, :code, :category, :brand, :vehicle, :image, :description, :tags)');
    $insertUser = $db->prepare('INSERT OR IGNORE INTO users (name, phone, password_hash, city, role) VALUES (:name, :phone, :password_hash, :city, :role)');
    $findUser = $db->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
    $insertOffer = $db->prepare('INSERT OR IGNORE INTO offers (product_id, seller_id, seller, phone, city, price, condition, warranty, test_days, payment, offer_date, verified) VALUES (:product_id, :seller_id, :seller, :phone, :city, :price, :condition, :warranty, :test_days, :payment, :offer_date, :verified)');

    $db->beginTransaction();
    try {
        foreach ($GLOBALS['products'] as $product) {
            $insertProduct->execute([
                ':id' => $product['id'],
                ':title' => $product['title'],
                ':code' => $product['code'],
                ':category' => $product['category'],
                ':brand' => $product['brand'],
                ':vehicle' => $product['vehicle'],
                ':image' => $product['image'],
                ':description' => $product['description'],
                ':tags' => json_encode($product['tags'], JSON_UNESCAPED_UNICODE),
            ]);

            foreach ($product['offers'] as $offer) {
                $insertUser->execute([
                    ':name' => $offer['seller'],
                    ':phone' => $offer['phone'],
                    ':password_hash' => password_hash('demo1234', PASSWORD_DEFAULT),
                    ':city' => $offer['city'],
                    ':role' => 'seller',
                ]);
                $findUser->execute([':phone' => $offer['phone']]);
                $sellerId = (int) $findUser->fetchColumn();
                $insertOffer->execute([
                    ':product_id' => $product['id'],
                    ':seller_id' => $sellerId > 0 ? $sellerId : null,
                    ':seller' => $offer['seller'],
                    ':phone' => $offer['phone'],
                    ':city' => $offer['city'],
                    ':price' => (int) $offer['price'],
                    ':condition' => $offer['condition'],
                    ':warranty' => $offer['warranty'],
                    ':test_days' => $offer['test'],
                    ':payment' => $offer['payment'],
                    ':offer_date' => $offer['date'],
                    ':verified' => !empty($offer['verified']) ? 1 : 0,
                ]);
            }
        }
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}

function app_products(): array
{
    global $products;
    $db = app_db();
    if (!$db) {
        return $products;
    }

    try {
        $productRows = $db->query('SELECT id, title, code, category, brand, vehicle, image, description, tags FROM products ORDER BY created_at DESC, rowid DESC')->fetchAll();
        $offerQuery = $db->prepare('SELECT seller, phone, city, price, condition, warranty, test_days, payment, offer_date, verified FROM offers WHERE product_id = :product_id ORDER BY price ASC, id ASC');
        $result = [];
        foreach ($productRows as $row) {
            $offerQuery->execute([':product_id' => $row['id']]);
            $offers = [];
            foreach ($offerQuery->fetchAll() as $offer) {
                $offers[] = [
                    'seller' => $offer['seller'],
                    'phone' => $offer['phone'],
                    'city' => $offer['city'],
                    'price' => (int) $offer['price'],
                    'condition' => $offer['condition'],
                    'warranty' => $offer['warranty'],
                    'test' => $offer['test_days'],
                    'payment' => $offer['payment'],
                    'date' => $offer['offer_date'],
                    'verified' => (bool) $offer['verified'],
                ];
            }
            $tags = json_decode((string) $row['tags'], true);
            $result[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'code' => $row['code'],
                'category' => $row['category'],
                'brand' => $row['brand'],
                'vehicle' => $row['vehicle'],
                'image' => $row['image'],
                'description' => $row['description'],
                'tags' => is_array($tags) ? $tags : [],
                'offers' => $offers,
            ];
        }
        return $result ?: $products;
    } catch (Throwable $exception) {
        error_log('Mojodi-kala product query failed: ' . $exception->getMessage());
        return $products;
    }
}

function app_product(string $id): ?array
{
    foreach (app_products() as $product) {
        if ($product['id'] === $id) {
            return $product;
        }
    }
    return null;
}

function app_input_string($value): string
{
    return is_scalar($value) ? trim((string) $value) : '';
}

function app_current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function app_login(string $phone, string $password): bool
{
    $phone = trim($phone);
    $db = app_db();
    if ($db) {
        $statement = $db->prepare('SELECT id, name, phone, city, role, password_hash FROM users WHERE phone = :phone LIMIT 1');
        $statement->execute([':phone' => $phone]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    // حساب ورود دموی fallback، زمانی که PDO SQLite روی هاست فعال نیست.
    if ($phone === '09120000001' && $password === 'demo1234') {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'یدک‌پارت مرکزی',
            'phone' => $phone,
            'city' => 'تهران',
            'role' => 'seller',
        ];
        return true;
    }
    return false;
}

function app_register_user(string $name, string $phone, string $password, string $city): array
{
    $db = app_db();
    if (!$db) {
        return ['ok' => false, 'message' => 'برای ثبت‌نام، فعال بودن SQLite روی هاست لازم است.'];
    }
    try {
        $statement = $db->prepare('INSERT INTO users (name, phone, password_hash, city, role) VALUES (:name, :phone, :password_hash, :city, :role)');
        $statement->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':city' => $city,
            ':role' => 'seller',
        ]);
        return ['ok' => true, 'message' => 'حساب فروشنده با موفقیت ساخته شد.'];
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() === '23000') {
            return ['ok' => false, 'message' => 'این شماره قبلاً ثبت شده است.'];
        }
        return ['ok' => false, 'message' => 'ثبت‌نام انجام نشد؛ اطلاعات را دوباره بررسی کنید.'];
    }
}

function app_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function app_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf_token'];
}

function app_verify_csrf($token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function app_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function app_take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function app_add_offer(array $input, array $user): array
{
    $db = app_db();
    if (!$db) {
        return ['ok' => false, 'message' => 'برای ثبت و ویرایش موجودی، SQLite روی هاست فعال نیست.'];
    }

    $productId = app_input_string($input['product_id'] ?? '');
    $city = app_input_string($input['city'] ?? '');
    $price = is_numeric($input['price'] ?? null) ? (int) $input['price'] : 0;
    $condition = app_input_string($input['condition'] ?? '');
    $warranty = app_input_string($input['warranty'] ?? '');
    $testDays = app_input_string($input['test_days'] ?? '');
    $payment = app_input_string($input['payment'] ?? '');
    global $filterOptions;
    $validCities = array_slice($filterOptions['cities'], 1);
    $validConditions = ['نو', 'دست دوم'];
    $validWarranty = ['دارد', 'ندارد'];
    $validTest = ['ندارد', 'یک روز', 'دو روز', 'یک هفته'];
    $validPayment = ['نقدی', 'نقد و اقساط', 'آنلاین'];

    if (!app_product($productId) || !in_array($city, $validCities, true) || $price < 1 || !in_array($condition, $validConditions, true) || !in_array($warranty, $validWarranty, true) || !in_array($testDays, $validTest, true) || !in_array($payment, $validPayment, true)) {
        return ['ok' => false, 'message' => 'اطلاعات پیشنهاد کامل یا معتبر نیست.'];
    }

    try {
        $statement = $db->prepare('INSERT INTO offers (product_id, seller_id, seller, phone, city, price, condition, warranty, test_days, payment, offer_date, verified) VALUES (:product_id, :seller_id, :seller, :phone, :city, :price, :condition, :warranty, :test_days, :payment, :offer_date, 0)');
        $statement->execute([
            ':product_id' => $productId,
            ':seller_id' => (int) ($user['id'] ?? 0),
            ':seller' => (string) $user['name'],
            ':phone' => (string) $user['phone'],
            ':city' => $city,
            ':price' => $price,
            ':condition' => $condition,
            ':warranty' => $warranty,
            ':test_days' => $testDays,
            ':payment' => $payment,
            ':offer_date' => date('Y/m/d'),
        ]);
        return ['ok' => true, 'message' => 'پیشنهاد شما با موفقیت ثبت شد و برای بررسی آماده است.'];
    } catch (PDOException $exception) {
        return ['ok' => false, 'message' => 'این پیشنهاد مشابه قبلاً ثبت شده یا اطلاعات قابل ذخیره نیست.'];
    }
}

function app_user_offers(int $userId): array
{
    $db = app_db();
    if (!$db || $userId < 1) {
        return [];
    }
    $statement = $db->prepare('SELECT o.*, p.title AS product_title, p.code AS product_code FROM offers o INNER JOIN products p ON p.id = o.product_id WHERE o.seller_id = :seller_id ORDER BY o.id DESC');
    $statement->execute([':seller_id' => $userId]);
    return $statement->fetchAll();
}

function app_get_user_offer(int $offerId, int $userId): ?array
{
    $db = app_db();
    if (!$db || $offerId < 1 || $userId < 1) {
        return null;
    }
    $statement = $db->prepare('SELECT o.*, p.title AS product_title, p.code AS product_code FROM offers o INNER JOIN products p ON p.id = o.product_id WHERE o.id = :id AND o.seller_id = :seller_id LIMIT 1');
    $statement->execute([':id' => $offerId, ':seller_id' => $userId]);
    $offer = $statement->fetch();
    return $offer ?: null;
}

function app_update_offer(array $input, array $user, int $offerId): array
{
    $db = app_db();
    if (!$db || $offerId < 1) {
        return ['ok' => false, 'message' => 'ویرایش پیشنهاد در این محیط در دسترس نیست.'];
    }

    $productId = app_input_string($input['product_id'] ?? '');
    $city = app_input_string($input['city'] ?? '');
    $price = is_numeric($input['price'] ?? null) ? (int) $input['price'] : 0;
    $condition = app_input_string($input['condition'] ?? '');
    $warranty = app_input_string($input['warranty'] ?? '');
    $testDays = app_input_string($input['test_days'] ?? '');
    $payment = app_input_string($input['payment'] ?? '');
    global $filterOptions;
    $validCities = array_slice($filterOptions['cities'], 1);
    if (!app_product($productId) || !in_array($city, $validCities, true) || $price < 1 || !in_array($condition, ['نو', 'دست دوم'], true) || !in_array($warranty, ['دارد', 'ندارد'], true) || !in_array($testDays, ['ندارد', 'یک روز', 'دو روز', 'یک هفته'], true) || !in_array($payment, ['نقدی', 'نقد و اقساط', 'آنلاین'], true)) {
        return ['ok' => false, 'message' => 'اطلاعات پیشنهاد کامل یا معتبر نیست.'];
    }

    try {
        $statement = $db->prepare('UPDATE offers SET product_id = :product_id, city = :city, price = :price, condition = :condition, warranty = :warranty, test_days = :test_days, payment = :payment, verified = 0 WHERE id = :id AND seller_id = :seller_id');
        $statement->execute([
            ':product_id' => $productId,
            ':city' => $city,
            ':price' => $price,
            ':condition' => $condition,
            ':warranty' => $warranty,
            ':test_days' => $testDays,
            ':payment' => $payment,
            ':id' => $offerId,
            ':seller_id' => (int) ($user['id'] ?? 0),
        ]);
        if ($statement->rowCount() < 1 && !app_get_user_offer($offerId, (int) ($user['id'] ?? 0))) {
            return ['ok' => false, 'message' => 'پیشنهاد موردنظر پیدا نشد.'];
        }
        return ['ok' => true, 'message' => 'پیشنهاد با موفقیت ویرایش شد و دوباره برای بررسی ارسال شد.'];
    } catch (PDOException $exception) {
        return ['ok' => false, 'message' => 'ویرایش پیشنهاد انجام نشد؛ اطلاعات را بررسی کنید.'];
    }
}

function app_delete_offer(int $offerId, int $userId): bool
{
    $db = app_db();
    if (!$db || $offerId < 1 || $userId < 1) {
        return false;
    }
    $statement = $db->prepare('DELETE FROM offers WHERE id = :id AND seller_id = :seller_id');
    $statement->execute([':id' => $offerId, ':seller_id' => $userId]);
    return $statement->rowCount() > 0;
}
