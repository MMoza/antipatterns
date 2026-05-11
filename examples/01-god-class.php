#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use AntiPatterns\Common\Database;
use AntiPatterns\GodClass\antipattern\OrderManager;

echo "=== 01 - God Class / Clase Dios ===\n\n";

// Inicializa BD en memoria para el demo
$db = Database::getInstance();

$db->exec("
    CREATE TABLE IF NOT EXISTS stores (
        id INTEGER PRIMARY KEY, name TEXT, timezone TEXT DEFAULT 'Europe/Madrid'
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY, store_id INTEGER, name TEXT, price REAL, stock INTEGER DEFAULT 0, reserved_stock INTEGER DEFAULT 0, category_id INTEGER
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY, name TEXT, parent_id INTEGER
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, active INTEGER DEFAULT 1, merged_to INTEGER
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS carts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, store_id INTEGER, created_at TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS cart_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT, cart_id INTEGER, product_id INTEGER, quantity INTEGER, price REAL
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, product_id INTEGER, customer_id INTEGER,
        customer_name TEXT, customer_email TEXT, customer_phone TEXT, quantity INTEGER DEFAULT 1,
        unit_price REAL, subtotal REAL, discount REAL DEFAULT 0, tax REAL, total REAL,
        status INTEGER DEFAULT 1, is_vip INTEGER DEFAULT 0, cancellation_reason TEXT,
        refund_amount REAL DEFAULT 0, cancelled_at TEXT, coupon_code TEXT,
        discount_amount REAL DEFAULT 0, total_amount REAL, shipped_at TEXT, created_at TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, quantity INTEGER, price REAL
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT, type TEXT, value REAL, valid_from TEXT, valid_until TEXT, active INTEGER DEFAULT 1
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS shipments (
        id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, order_id INTEGER, shipping_type INTEGER DEFAULT 1,
        estimated_days INTEGER DEFAULT 5, shipping_cost REAL DEFAULT 0, status INTEGER DEFAULT 1,
        carrier TEXT, tracking_number TEXT, created_at TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS returns (
        id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, order_id INTEGER, product_id INTEGER,
        reason TEXT, status INTEGER DEFAULT 1, refund_amount REAL DEFAULT 0, created_at TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, customer_id INTEGER,
        rating INTEGER, comment TEXT, created_at TEXT
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS restock_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, store_id INTEGER, status INTEGER DEFAULT 1
    )
");
$db->exec("
    CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, invoice_number TEXT,
        subtotal REAL, tax_amount REAL, total REAL, tax_rate REAL, issued_at TEXT, status INTEGER DEFAULT 1
    )
");

$db->exec("INSERT INTO stores (id, name) VALUES (1, 'Tienda Demo')");
$db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (1, 1, 'Camiseta Basica', 29.99, 100)");
$db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (2, 1, 'Zapatillas Running', 89.99, 50)");

$manager = new OrderManager(1);

echo "1. ANTE: God Class - Una clase hace TODO\n";
echo "-----------------------------------------\n\n";

echo "Creando pedido (mezcla validacion, persistencia, precios, envio, email, HTML)...\n";
$result = $manager->createOrder([
    'customer_name' => 'Miguel Garcia',
    'customer_email' => 'miguel@example.com',
    'customer_phone' => '+34 600 123 456',
    'product_id' => 1,
    'quantity' => 4,
]);

echo "  - Order ID: {$result['order_id']}\n";
echo "  - Total: {$result['total']}\n";
echo "  - Devuelve HTML mezclado: " . (isset($result['html']) ? 'SI (problema)' : 'NO') . "\n";
echo "\n";

echo "Ejecutando accion por numero (que hace la accion 1?)...\n";
$catalog = $manager->executeAction(1, [
    'start_date' => '2025-06-01',
    'end_date' => '2025-06-10',
]);
echo "  - Devuelve HTML: " . (is_string($catalog) ? 'SI' : 'NO') . "\n";
echo "  - Longitud: " . strlen($catalog) . " caracteres\n";
echo "\n";

echo "Estado mutable compartido - currentOrder despues de crear:\n";
$current = $manager->getCurrentOrder();
echo "  - ID: {$current->id}\n";
echo "  - Total: {$current->total}\n";
echo "  - Customer: {$current->customer}\n";
echo "\n";

echo "Consultas con efectos secundarios ocultos:\n";
echo "  - showCatalog() deberia ser solo lectura...\n";
echo "  - Pero crea tareas de restock como side effect\n";
echo "\n";

echo "2. PROBLEMAS DEMOSTRADOS\n";
echo "------------------------\n";
echo "  - Una clase con 15+ responsabilidades\n";
echo "  - Metodos que devuelven HTML + datos mezclados\n";
echo "  - Estado mutable compartido entre todos los metodos\n";
echo "  - Acciones identificadas por numeros (1, 2, 3... 9000)\n";
echo "  - Queries SQL con interpolacion directa (SQL injection)\n";
echo "  - Try/catch vacios que ocultan errores\n";
echo "  - Logica duplicada entre metodos similares\n";
echo "  - Imposible testear un metodo en aislamiento\n";
echo "\n";

echo "3. SIGUIENTE PASO: Refactorizar a servicios separados\n";
echo "   - OrderService (pedidos)\n";
echo "   - CartService (carrito)\n";
echo "   - PricingService (precios, cupones, impuestos)\n";
echo "   - ShippingService (envios)\n";
echo "   - NotificationService (emails)\n";
echo "\n";
