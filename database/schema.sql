-- Schema مرجع نسخه کامل موجودی‌کالا
-- bootstrap.php همین ساختار را در اولین اجرا به‌صورت خودکار می‌سازد.

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
