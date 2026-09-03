# اجرای پیش‌نمایش بدون نصب PHP

اگر روی سیستم PHP نصب نیست، این روش اختیاری همان `index.php` را با PHP-Wasm اجرا می‌کند:

```bash
npm install
npm run preview
```

سپس آدرس `http://localhost:8000` را باز کنید. پورت را می‌توان با `PORT=8080 npm run preview` تغییر داد.

برای محیط واقعی، پیشنهاد اصلی همان اجرای مستقیم PHP است:

```bash
php -S 0.0.0.0:8000
```
