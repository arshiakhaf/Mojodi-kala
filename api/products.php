<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');

function api_string($value): string
{
    return is_scalar($value) ? trim((string) $value) : '';
}

function api_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function api_contains(string $haystack, string $needle): bool
{
    return $needle === '' || strpos(api_lower($haystack), api_lower($needle)) !== false;
}

$q = api_string($_GET['q'] ?? '');
$city = api_string($_GET['city'] ?? '');
$category = api_string($_GET['category'] ?? '');
$brand = api_string($_GET['brand'] ?? '');
$condition = api_string($_GET['condition'] ?? '');
$testDays = api_string($_GET['test_days'] ?? '');
$warranty = api_string($_GET['warranty'] ?? '');
$payment = api_string($_GET['payment'] ?? '');

$products = array_values(array_filter(app_products(), function (array $product) use ($q, $city, $category, $brand, $condition, $testDays, $warranty, $payment): bool {
    $searchable = implode(' ', [$product['title'], $product['code'], $product['category'], $product['brand'], $product['vehicle'], implode(' ', $product['tags'])]);
    if ($q !== '' && !api_contains($searchable, $q)) return false;
    if ($category !== '' && $product['category'] !== $category) return false;
    if ($brand !== '' && $product['brand'] !== $brand) return false;
    foreach ($product['offers'] as $offer) {
        if ($city !== '' && $offer['city'] !== $city) continue;
        if ($condition !== '' && $offer['condition'] !== $condition) continue;
        if ($testDays !== '' && $offer['test'] !== $testDays) continue;
        if ($warranty !== '' && $offer['warranty'] !== $warranty) continue;
        if ($payment !== '' && $offer['payment'] !== $payment) continue;
        return true;
    }
    return $city === '' && $condition === '' && $testDays === '' && $warranty === '' && $payment === '';
}));

$offers = 0;
foreach ($products as $product) {
    foreach ($product['offers'] as $offer) {
        if ($city !== '' && $offer['city'] !== $city) continue;
        if ($condition !== '' && $offer['condition'] !== $condition) continue;
        if ($testDays !== '' && $offer['test'] !== $testDays) continue;
        if ($warranty !== '' && $offer['warranty'] !== $warranty) continue;
        if ($payment !== '' && $offer['payment'] !== $payment) continue;
        $offers++;
    }
}

echo json_encode([
    'ok' => true,
    'data' => $products,
    'meta' => [
        'products' => count($products),
        'offers' => $offers,
        'message' => 'این API آماده اتصال به فرانت‌اند و اپلیکیشن WebView است.',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
