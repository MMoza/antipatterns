#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use AntiPatterns\Common\Database;
use AntiPatterns\GodClass\antipattern\OrderManager;
use AntiPatterns\GodClass\solution\Config;
use AntiPatterns\GodClass\solution\CreateOrderRequest;
use AntiPatterns\GodClass\solution\OrderRepository;
use AntiPatterns\GodClass\solution\OrderService;
use AntiPatterns\GodClass\solution\PricingService;
use AntiPatterns\GodClass\solution\ShippingService;
use AntiPatterns\GodClass\solution\ValueObjects\ProductId;

echo "=== 01 - God Class: Antes y Despues ===\n\n";

// Setup database
Database::reset();
$db = Database::getInstance();
$db->exec("CREATE TABLE stores (id INTEGER PRIMARY KEY, name TEXT)");
$db->exec("CREATE TABLE products (id INTEGER PRIMARY KEY, store_id INTEGER, name TEXT, price REAL, stock INTEGER DEFAULT 0, reserved_stock INTEGER DEFAULT 0)");
$db->exec("CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, product_id INTEGER, customer_name TEXT, customer_email TEXT, customer_phone TEXT, quantity INTEGER DEFAULT 1, unit_price REAL, subtotal REAL, discount REAL DEFAULT 0, tax REAL, total REAL, status INTEGER DEFAULT 1, is_vip INTEGER DEFAULT 0, cancellation_reason TEXT, refund_amount REAL DEFAULT 0, cancelled_at TEXT, created_at TEXT)");
$db->exec("CREATE TABLE shipments (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, order_id INTEGER, shipping_type INTEGER DEFAULT 1, estimated_days INTEGER DEFAULT 5, shipping_cost REAL DEFAULT 0, status INTEGER DEFAULT 1, carrier TEXT, created_at TEXT)");
$db->exec("CREATE TABLE customers (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, active INTEGER DEFAULT 1, merged_to INTEGER)");
$db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
$db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (1, 1, 'Producto A', 100.0, 50)");
$db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (2, 1, 'Producto B', 150.0, 30)");

echo "1. ANTE: God Class\n";
echo "   ----------------\n";
echo "   OrderManager: 1100+ lineas, 15+ responsabilidades\n";
echo "   Hereda de BaseManager (auth, templates, logging, config)\n";
echo "   Constructor hace 13 inicializaciones\n";
echo "   executeAction() con switch numerico\n\n";

$manager = new OrderManager(1);

echo "   Creando pedido via executeAction(4, ...):\n";
$result = $manager->executeAction(4, [
    'customer_name' => 'Cliente Prueba',
    'customer_email' => 'cliente@test.com',
    'customer_phone' => '+34 600 123 456',
    'product_id' => 1,
    'quantity' => 4,
]);

echo "   - Order ID: {$result['order_id']}\n";
echo "   - Total: {$result['total']}\n";
echo "   - Incluye HTML: " . (isset($result['html']) ? 'SI (no puedo evitarlo)' : 'NO') . "\n";
echo "   - Estado mutable: currentOrder->id = {$manager->getCurrentOrder()->id}\n";
echo "   - Problema: No puedo testear createOrder() sin toda la infraestructura\n\n";

echo "2. DESPUES: Servicios Separados\n";
echo "   ----------------------------\n";
echo "   OrderService: Solo ciclo de vida de pedidos\n";
echo "   PricingService: Calculo de precios, impuestos, descuentos\n";
echo "   ShippingService: Logica de envios\n";
echo "   OrderRepository: Acceso a datos con prepared statements\n";
echo "   Config: Configuracion externalizada\n\n";

$config = Config::default(storeId: 1);
$repository = new OrderRepository($db, 1);
$pricing = new PricingService($config);
$shipping = new ShippingService($db, $config, 1);
$service = new OrderService($repository, $pricing, $shipping);

echo "   Creando pedido via OrderService::createOrder():\n";
$result = $service->createOrder(new CreateOrderRequest(
    productId: new ProductId(1),
    customerName: 'Cliente Prueba',
    customerEmail: 'cliente@test.com',
    customerPhone: '+34 600 123 456',
    quantity: 4,
));

echo "   - Order ID: {$result->data->orderId->value}\n";
echo "   - Subtotal: {$result->data->pricing->subtotal->format()}\n";
echo "   - Descuento: {$result->data->pricing->discount->format()}\n";
echo "   - Impuestos: {$result->data->pricing->tax->format()}\n";
echo "   - Total: {$result->data->pricing->total->format()}\n";
echo "   - Es VIP: " . ($result->data->isVip ? 'SI' : 'NO') . "\n";
echo "   - Incluye HTML: NO (solo datos tipados)\n";
echo "   - Estado mutable: NO (todo inmutable)\n";
echo "   - Testeable: SI (cada servicio se testea aisladamente)\n\n";

echo "3. Comparativa de antipatrones resueltos\n";
echo "   -------------------------------------\n";
echo "   | Antipatron              | Antes         | Despues           |\n";
echo "   |-------------------------|---------------|-------------------|\n";
echo "   | God Class               | 1100+ lineas  | 4 servicios       |\n";
echo "   | God Method              | switch(15+)   | Metodos explicitos|\n";
echo "   | Inheritance Abuse       | BaseManager   | Composicion       |\n";
echo "   | Constructor Heavy       | 13 init       | 3-4 deps          |\n";
echo "   | High Cognitive Load     | 7+ a rastrear | SRP claro         |\n";
echo "   | Leaky Abstractions      | stdClass      | Typed objects     |\n";
echo "   | Hardcoded Infra         | Magic numbers | Config object     |\n";
echo "   | Infrastructure Leakage  | SQL inline    | Repository        |\n\n";

echo "=== Fin del demo ===\n";
