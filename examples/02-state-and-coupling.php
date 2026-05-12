#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use AntiPatterns\Common\Database;
use AntiPatterns\StateAndCoupling\antipattern\ReservationContext;
use AntiPatterns\StateAndCoupling\solution\BookingWorkflow;
use AntiPatterns\StateAndCoupling\solution\PricingCalculator;
use AntiPatterns\StateAndCoupling\solution\ReservationFactory;
use AntiPatterns\StateAndCoupling\solution\ShippingCalculator;
use AntiPatterns\StateAndCoupling\solution\ShippingConfig;
use AntiPatterns\StateAndCoupling\solution\StatusTransition;

echo "=== 02 - State & Coupling: Antes y Despues ===\n\n";

// Setup database
Database::reset();
$db = Database::getInstance();
$db->exec("CREATE TABLE stores (id INTEGER PRIMARY KEY, name TEXT)");
$db->exec("CREATE TABLE customers (id INTEGER PRIMARY KEY, name TEXT, email TEXT, last_activity TEXT)");
$db->exec("CREATE TABLE reservations (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, customer_id INTEGER, base_price REAL, nights INTEGER, weight REAL DEFAULT 0, destination TEXT DEFAULT '', status INTEGER DEFAULT 1, payment_status INTEGER DEFAULT 0, transaction_id TEXT, coupon_code TEXT, discount REAL DEFAULT 0, total REAL DEFAULT 0, view_count INTEGER DEFAULT 0, notified INTEGER DEFAULT 0, created_at TEXT)");
$db->exec("CREATE TABLE availability (date TEXT PRIMARY KEY, available INTEGER, view_count INTEGER DEFAULT 0)");
$db->exec("CREATE TABLE availability_alerts (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER, alert_type TEXT, created_at TEXT)");
$db->exec("CREATE TABLE tax_rates (store_id INTEGER PRIMARY KEY, rate REAL)");
$db->exec("CREATE TABLE discount_rules (store_id INTEGER PRIMARY KEY, rule TEXT)");
$db->exec("CREATE TABLE carriers (store_id INTEGER PRIMARY KEY, name TEXT)");
$db->exec("CREATE TABLE shipping_zones (store_id INTEGER PRIMARY KEY, zone TEXT)");
$db->exec("CREATE TABLE email_templates (store_id INTEGER PRIMARY KEY, template TEXT)");
$db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
$db->exec("INSERT INTO customers (id, name, email) VALUES (1, 'Test Customer', 'test@example.com')");
$db->exec("INSERT INTO reservations (id, store_id, customer_id, base_price, nights, weight, destination, status, payment_status, total, created_at) VALUES (1, 1, 1, 100.0, 3, 1.5, 'Madrid', 1, 0, 0, '2025-01-01')");
$db->exec("INSERT INTO availability (date, available) VALUES ('2025-06-01', 2)");
$db->exec("INSERT INTO availability (date, available) VALUES ('2025-06-02', 1)");

echo "1. ANTE: State & Coupling Problems\n";
echo "   --------------------------------\n";
echo "   ReservationContext: estado mutable compartido entre todos los metodos\n";
echo "   Temporal coupling: los metodos deben llamarse en orden especifico\n";
echo "   Hidden side effects: getters que modifican la BD\n";
echo "   Service locator: dependencias resueltas via estado interno\n\n";

$context = new ReservationContext(['store_id' => 1]);

echo "   Cargando reserva #1...\n";
$context->loadReservation(1);
echo "   - Context expone estado interno: reservation_id = {$context->getContext()['reservation_id']}\n";

echo "   Calculando precios (requiere loadReservation primero)...\n";
$prices = $context->calculatePrices();
echo "   - Base: {$prices['base']}, Taxes: {$prices['taxes']}, Total: {$prices['total']}\n";

echo "   Calculando envio (depende de context['total'] set por calculatePrices)...\n";
$shipping = $context->calculateShipping();
echo "   - Cost: {$shipping['cost']}, Method: {$shipping['method']}\n";

echo "   Aplicando descuento (requiere calculatePrices primero)...\n";
$discount = $context->applyDiscounts('SUMMER20');
echo "   - Discount: {$discount['discount']}, New Total: {$discount['new_total']}\n";

echo "   Side effects ocultos en getAvailableDates():\n";
$alertsBefore = $db->query("SELECT COUNT(*) FROM availability_alerts")->fetchColumn();
$context->getAvailableDates('2025-06-01', '2025-06-02');
$alertsAfter = $db->query("SELECT COUNT(*) FROM availability_alerts")->fetchColumn();
echo "   - Alerts creadas: " . ($alertsAfter - $alertsBefore) . " (nadie esperaba esto)\n\n";

echo "2. DESPUES: Explicit State & Pure Functions\n";
echo "   ----------------------------------------\n";
echo "   Reservation: Value object inmutable\n";
echo "   BookingWorkflow: Workflow explicito paso a paso\n";
echo "   StatusTransition: Maquina de estados validada\n";
echo "   ShippingCalculator: Funcion pura sin side effects\n";
echo "   PricingCalculator: Funcion pura sin side effects\n\n";

$factory = new ReservationFactory($db);
$shippingCalc = new ShippingCalculator(new ShippingConfig());
$pricingCalc = new PricingCalculator(0.21);
$transitions = new StatusTransition();
$workflow = new BookingWorkflow($factory, $shippingCalc, $pricingCalc, $transitions, $db);

echo "   Confirmando reserva #1 (operacion independiente)...\n";
$result = $workflow->confirmReservation(1);
echo "   - Success: " . ($result->success ? 'YES' : 'NO') . "\n";
echo "   - Total: {$result->data->total}\n";
echo "   - Shipping: {$result->data->shipping->cost} ({$result->data->shipping->method->value})\n";

echo "   Aplicando cupon (operacion independiente)...\n";
$couponResult = $workflow->applyCoupon(1, 'SUMMER20');
echo "   - Success: " . ($couponResult->success ? 'YES' : 'NO') . "\n";
echo "   - Discount: {$couponResult->data->discount}\n";

echo "   Transicion invalida (validada explicitamente)...\n";
$invalidResult = $workflow->confirmReservation(1); // Ya confirmado
echo "   - Success: " . ($invalidResult->success ? 'YES' : 'NO') . "\n";
echo "   - Error: {$invalidResult->error}\n\n";

echo "3. Comparativa de antipatrones resueltos\n";
echo "   -------------------------------------\n";
echo "   | Antipatron              | Antes              | Despues            |\n";
echo "   |-------------------------|--------------------|--------------------|\n";
echo "   | Mutable Shared State    | \$this->context     | Inmutable VO       |\n";
echo "   | Action at Distance      | Flags ocultos      | Parametros explicit|\n";
echo "   | Temporal Coupling       | Orden implicito    | Workflow explicito |\n";
echo "   | Implicit Workflow       | Comentarios        | Metodo confirm()   |\n";
echo "   | Service Locator         | Estado interno     | DI por constructor |\n";
echo "   | Hidden Side Effects     | Getters modifican  | Funciones puras    |\n";
echo "   | Recursive Instantiation | Cadenas ocultas    | DI explicita       |\n";
echo "   | Environment Scattered   | Logica esparcida   | Config inyectada   |\n\n";

echo "=== Fin del demo ===\n";
