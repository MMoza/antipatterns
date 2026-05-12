<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\antipattern;

use AntiPatterns\Common\Database;
use Exception;
use PDO;

/**
 * ReservationContext - State & Coupling Antipatterns
 *
 * Esta clase demuestra los antipatrones de estado y acoplamiento:
 *
 * #9  Mutable Shared State    - $this->context se modifica desde todos los metodos
 * #10 Action at Distance      - cambiar un flag afecta metodos no relacionados
 * #11 Temporal Coupling       - los metodos deben llamarse en orden especifico
 * #12 Implicit Workflow       - el flujo esta en comentarios, no en codigo
 * #13 Service Locator         - dependencias se resuelven via estado del objeto
 * #14 Hidden Side Effects     - metodos de consulta que modifican datos
 * #15 Recursive Instantiation - servicios que instancian otros servicios
 * #16 Environment Scattered   - logica de prod/dev/test esparcida por todo
 */
class ReservationContext
{
    // #9 Mutable Shared State - todo se lee y escribe desde cualquier metodo
    private array $context;
    private ?object $currentReservation;
    private array $prices;
    private ?object $customer;
    private array $flags;
    private string $environment;
    private PDO $db;
    private ?object $shippingConfig;
    private array $serviceCache;
    private string $lastError;

    public function __construct(array $config = [])
    {
        $this->db = Database::getInstance();
        $this->environment = $this->detectEnvironment();

        // #9 Estado mutable compartido inicializado aqui
        $this->context = [
            'store_id' => $config['store_id'] ?? 1,
            'currency' => 'EUR',
            'language' => 'es',
        ];

        $this->currentReservation = null;
        $this->prices = [];
        $this->customer = null;

        // #16 Environment logic scattered - flags dependen del entorno
        $this->flags = [
            'enable_debug' => $this->environment === 'development',
            'use_mock_payments' => $this->environment !== 'production',
            'skip_email' => $this->environment === 'testing',
            'force_https' => $this->environment === 'production',
            'log_queries' => $this->environment !== 'production',
        ];

        $this->serviceCache = [];
        $this->lastError = '';
    }

    // #13 Service Locator by Object State
    // Las dependencias se crean a partir del estado interno, no se inyectan
    private function getPricingService(): object
    {
        $cacheKey = 'pricing_' . $this->context['store_id'];
        if (!isset($this->serviceCache[$cacheKey])) {
            // #15 Recursive Service Instantiation
            // Cada servicio instancia sus propias dependencias
            $this->serviceCache[$cacheKey] = new PricingService([
                'store_id' => $this->context['store_id'],
                'currency' => $this->context['currency'],
                'environment' => $this->environment,
            ]);
        }
        return $this->serviceCache[$cacheKey];
    }

    private function getShippingService(): object
    {
        $cacheKey = 'shipping_' . $this->context['store_id'];
        if (!isset($this->serviceCache[$cacheKey])) {
            // #15 Cada servicio crea su propia cadena de dependencias
            $this->serviceCache[$cacheKey] = new ShippingService([
                'store_id' => $this->context['store_id'],
                'environment' => $this->environment,
            ]);
        }
        return $this->serviceCache[$cacheKey];
    }

    private function getPaymentService(): object
    {
        $cacheKey = 'payment_' . $this->context['store_id'];
        if (!isset($this->serviceCache[$cacheKey])) {
            $this->serviceCache[$cacheKey] = new PaymentService([
                'store_id' => $this->context['store_id'],
                'use_mock' => $this->flags['use_mock_payments'],
                'environment' => $this->environment,
            ]);
        }
        return $this->serviceCache[$cacheKey];
    }

    private function getEmailService(): object
    {
        $cacheKey = 'email_' . $this->context['store_id'];
        if (!isset($this->serviceCache[$cacheKey])) {
            $this->serviceCache[$cacheKey] = new EmailService([
                'store_id' => $this->context['store_id'],
                'skip' => $this->flags['skip_email'],
                'environment' => $this->environment,
            ]);
        }
        return $this->serviceCache[$cacheKey];
    }

    // #11 Temporal Coupling - este metodo DEBE llamarse primero
    // pero no hay forma de saberlo excepto leyendo comentarios
    public function loadReservation(int $reservationId): void
    {
        $query = "SELECT * FROM reservations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $reservationId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("Reservation $reservationId not found");
        }

        // #9 Modifica estado compartido - todos los metodos siguientes dependen de esto
        $this->currentReservation = (object) $row;
        $this->context['reservation_id'] = $reservationId;
        $this->context['status'] = $row['status'];
        $this->context['total'] = $row['total'];

        // #10 Action at Distance - este cambio afecta a calculateShipping()
        // que se llamara despues y usara $this->context['status']
        if ($row['status'] >= 3) {
            $this->context['shipping_locked'] = true;
        }

        // Carga tambien el cliente como side effect
        $this->loadCustomer((int) $row['customer_id']);
    }

    // #11 Temporal Coupling - requiere que loadReservation() se haya llamado antes
    public function loadCustomer(int $customerId): void
    {
        $query = "SELECT * FROM customers WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $customerId]);
        $row = $stmt->fetch();

        // #9 Modifica estado compartido
        $this->customer = $row ? (object) $row : null;
    }

    // #14 Hidden Side Effects - parece una consulta pero modifica datos
    public function getAvailableDates(string $startDate, string $endDate): array
    {
        $query = "SELECT date, available FROM availability 
                  WHERE date BETWEEN :start AND :end 
                  ORDER BY date";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        $dates = $stmt->fetchAll();

        // #14 Side effect oculto: marca las fechas como "vistas" en la BD
        // para tracking de analytics - nadie espera esto de un getter
        foreach ($dates as $date) {
            if ($date['available'] > 0) {
                $this->db->prepare(
                    "UPDATE availability SET view_count = view_count + 1 WHERE date = :date"
                )->execute(['date' => $date['date']]);
            }
        }

        // #14 Otro side effect: si hay pocas fechas disponibles,
        // crea automaticamente una alerta de disponibilidad
        $availableCount = array_sum(array_column($dates, 'available'));
        if ($availableCount < 5) {
            $this->db->prepare(
                "INSERT INTO availability_alerts (store_id, alert_type, created_at) 
                 VALUES (:store, 'low_availability', datetime('now'))"
            )->execute(['store' => $this->context['store_id']]);
        }

        return $dates;
    }

    // #12 Implicit Workflow - el flujo esta en los comentarios, no en el codigo
    // PASO 1: loadReservation()
    // PASO 2: calculatePrices()
    // PASO 3: calculateShipping()
    // PASO 4: applyDiscounts()
    // PASO 5: processPayment()
    // PASO 6: sendConfirmation()
    // Si te saltas un paso o cambias el orden, las cosas se rompen
    public function calculatePrices(): array
    {
        // #11 Requiere que currentReservation este cargado
        if ($this->currentReservation === null) {
            throw new Exception("Must call loadReservation() first");
        }

        $pricing = $this->getPricingService();

        // #9 Lee y modifica estado compartido
        $basePrice = $this->currentReservation->base_price;
        $nights = $this->currentReservation->nights;
        $this->prices['base'] = $basePrice * $nights;

        // #10 Action at Distance: el flag 'seasonal_pricing' se setea
        // en otro metodo pero afecta aqui
        if (!empty($this->context['seasonal_pricing'])) {
            $this->prices['seasonal_adjustment'] = $this->prices['base'] * 0.15;
            $this->prices['base'] += $this->prices['seasonal_adjustment'];
        }

        $this->prices['taxes'] = $this->prices['base'] * 0.21;
        $this->prices['total'] = $this->prices['base'] + $this->prices['taxes'];

        // #9 Actualiza el contexto compartido
        $this->context['total'] = $this->prices['total'];
        $this->context['prices_calculated'] = true;

        return $this->prices;
    }

    // #10 Action at Distance - depende de flags setados en otros metodos
    public function calculateShipping(): array
    {
        // #11 Requiere que currentReservation este cargado
        if ($this->currentReservation === null) {
            throw new Exception("Must call loadReservation() first");
        }

        // #10 Action at Distance: si shipping_locked se seteo en loadReservation()
        // el comportamiento cambia silenciosamente
        if (!empty($this->context['shipping_locked'])) {
            return [
                'cost' => 0,
                'method' => 'already_shipped',
                'note' => 'Shipping already processed',
            ];
        }

        $shipping = $this->getShippingService();

        // #10 Action at Distance: el metodo usa $this->context['total']
        // que fue setado en calculatePrices(). Si no llamaste a calculatePrices()
        // primero, el calculo de shipping sera incorrecto
        $orderTotal = $this->context['total'] ?? 0;

        // #16 Environment logic scattered
        if ($this->environment === 'production') {
            $shippingCost = $shipping->calculateRealCost(
                $this->currentReservation->weight,
                $this->currentReservation->destination
            );
        } else {
            // En dev/test usa costos fijos
            $shippingCost = $orderTotal > 100 ? 0 : 9.99;
        }

        // #9 Modifica estado compartido
        $this->context['shipping_cost'] = $shippingCost;
        $this->context['total'] = ($this->context['total'] ?? 0) + $shippingCost;

        return [
            'cost' => $shippingCost,
            'method' => $this->determineShippingMethod(),
            'estimated_days' => $this->determineEstimatedDays(),
        ];
    }

    public function applyDiscounts(string $couponCode): array
    {
        // #11 Requiere que calculatePrices() se haya llamado
        if (empty($this->context['prices_calculated'])) {
            throw new Exception("Must call calculatePrices() first");
        }

        $validCoupons = [
            'SUMMER20' => ['type' => 'percent', 'value' => 20],
            'WINTER10' => ['type' => 'percent', 'value' => 10],
            'FLAT50' => ['type' => 'fixed', 'value' => 50],
        ];

        if (!isset($validCoupons[$couponCode])) {
            return ['error' => 'Invalid coupon'];
        }

        $coupon = $validCoupons[$couponCode];
        $total = $this->context['total'];

        if ($coupon['type'] === 'percent') {
            $discount = $total * ($coupon['value'] / 100);
        } else {
            $discount = min($coupon['value'], $total);
        }

        // #9 Modifica estado compartido - afecta a processPayment()
        $this->context['discount'] = $discount;
        $this->context['total'] = $total - $discount;
        $this->context['coupon_applied'] = $couponCode;

        return [
            'discount' => $discount,
            'new_total' => $this->context['total'],
            'coupon' => $couponCode,
        ];
    }

    public function processPayment(string $paymentMethod): array
    {
        // #11 Requiere que todo el workflow anterior se haya ejecutado
        if (empty($this->context['prices_calculated'])) {
            throw new Exception("Must call calculatePrices() first");
        }

        $payment = $this->getPaymentService();

        // #10 Action at Distance: usa $this->context['total'] que fue
        // modificado por calculatePrices(), calculateShipping(), applyDiscounts()
        $amount = $this->context['total'];

        // #16 Environment logic scattered
        if ($this->flags['use_mock_payments']) {
            $result = ['success' => true, 'transaction_id' => 'mock-' . uniqid()];
        } else {
            $result = $payment->charge($amount, $paymentMethod, $this->customer);
        }

        if (!$result['success']) {
            $this->lastError = $result['error'] ?? 'Payment failed';
            return ['error' => $this->lastError];
        }

        // #9 Modifica estado compartido
        $this->context['payment_status'] = 'paid';
        $this->context['transaction_id'] = $result['transaction_id'];

        // #14 Hidden Side Effect: actualiza el estado de la reserva en BD
        // como efecto colateral de procesar el pago
        $this->db->prepare(
            "UPDATE reservations SET status = 2, payment_status = 'paid', 
             transaction_id = :tx WHERE id = :id"
        )->execute([
            'tx' => $result['transaction_id'],
            'id' => $this->context['reservation_id'],
        ]);

        return $result;
    }

    public function sendConfirmation(): array
    {
        // #11 Requiere que processPayment() se haya llamado
        if ($this->context['payment_status'] !== 'paid') {
            throw new Exception("Payment must be processed first");
        }

        $email = $this->getEmailService();

        // #16 Environment logic scattered
        if ($this->flags['skip_email']) {
            return ['skipped' => true, 'reason' => 'Email disabled in this environment'];
        }

        // #10 Action at Distance: depende de que customer se cargo en loadReservation()
        if ($this->customer === null) {
            return ['error' => 'Customer data not loaded'];
        }

        $result = $email->send(
            $this->customer->email,
            'reservation_confirmation',
            [
                'reservation_id' => $this->context['reservation_id'],
                'total' => $this->context['total'],
                'transaction_id' => $this->context['transaction_id'],
            ]
        );

        // #14 Hidden Side Effect: marca la reserva como "notificada"
        $this->db->prepare(
            "UPDATE reservations SET notified = 1 WHERE id = :id"
        )->execute(['id' => $this->context['reservation_id']]);

        return $result;
    }

    // #14 Hidden Side Effects - metodo que parece solo lectura
    public function getReservationSummary(): array
    {
        // #11 Requiere loadReservation()
        if ($this->currentReservation === null) {
            throw new Exception("Must call loadReservation() first");
        }

        // #14 Side effect: incrementa el contador de vistas
        $this->db->prepare(
            "UPDATE reservations SET view_count = view_count + 1 WHERE id = :id"
        )->execute(['id' => $this->context['reservation_id']]);

        // #14 Side effect: si el cliente tiene reservas antiguas,
        // actualiza su "ultima actividad"
        if ($this->customer !== null) {
            $this->db->prepare(
                "UPDATE customers SET last_activity = datetime('now') WHERE id = :id"
            )->execute(['id' => $this->customer->id]);
        }

        return [
            'id' => $this->context['reservation_id'],
            'status' => $this->context['status'],
            'total' => $this->context['total'],
            'customer' => $this->customer?->name ?? 'Unknown',
        ];
    }

    // #16 Environment detection logic
    private function detectEnvironment(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // #16 Logica de entorno hardcodeada y esparcida
        if (strpos($host, 'prod') !== false || strpos($host, 'live') !== false) {
            return 'production';
        }
        if (strpos($host, 'staging') !== false || strpos($host, 'stage') !== false) {
            return 'staging';
        }
        if (strpos($host, 'test') !== false) {
            return 'testing';
        }
        return 'development';
    }

    private function determineShippingMethod(): string
    {
        // #16 Environment logic scattered
        if ($this->environment === 'production') {
            $weight = $this->currentReservation?->weight ?? 0;
            if ($weight > 10) {
                return 'freight';
            }
            if ($weight > 2) {
                return 'express';
            }
            return 'standard';
        }

        // En dev/test siempre devuelve standard
        return 'standard';
    }

    private function determineEstimatedDays(): int
    {
        // #16 Environment logic scattered
        if ($this->environment === 'production') {
            $method = $this->determineShippingMethod();
            return match ($method) {
                'freight' => 7,
                'express' => 2,
                default => 5,
            };
        }

        return 1; // En dev/test siempre 1 dia
    }

    // Getters que exponen estado interno mutable
    public function getContext(): array
    {
        return $this->context;
    }

    public function getCurrentReservation(): ?object
    {
        return $this->currentReservation;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getCustomer(): ?object
    {
        return $this->customer;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }
}

// #15 Recursive Service Instantiation - cada servicio crea sus propias dependencias
class PricingService
{
    private array $config;
    private ?object $taxService;
    private ?object $discountService;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 Cada servicio instancia sus dependencias internamente
        $this->taxService = new TaxService(['store_id' => $config['store_id']]);
        $this->discountService = new DiscountService(['store_id' => $config['store_id']]);
    }

    public function calculateBase(float $price, int $nights): float
    {
        return $price * $nights;
    }
}

class TaxService
{
    private array $config;
    private ?object $taxRateLoader;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 Cadena de instanciacion recursiva
        $this->taxRateLoader = new TaxRateLoader(['store_id' => $config['store_id']]);
    }
}

class TaxRateLoader
{
    public function __construct(array $config)
    {
        // #15 Sigue la cadena...
        $db = Database::getInstance();
        $db->prepare("SELECT * FROM tax_rates WHERE store_id = :id")
           ->execute(['id' => $config['store_id']]);
    }
}

class DiscountService
{
    public function __construct(array $config)
    {
        // #15 Otra cadena...
        $db = Database::getInstance();
        $db->prepare("SELECT * FROM discount_rules WHERE store_id = :id")
           ->execute(['id' => $config['store_id']]);
    }
}

class ShippingService
{
    private array $config;
    private ?object $carrierService;
    private ?object $zoneService;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 Cada servicio crea su propia cadena
        $this->carrierService = new CarrierService(['store_id' => $config['store_id']]);
        $this->zoneService = new ZoneService(['store_id' => $config['store_id']]);
    }

    public function calculateRealCost(float $weight, string $destination): float
    {
        return $weight * 2.5 + 5.0;
    }
}

class CarrierService
{
    public function __construct(array $config)
    {
        $db = Database::getInstance();
        $db->prepare("SELECT * FROM carriers WHERE store_id = :id")
           ->execute(['id' => $config['store_id']]);
    }
}

class ZoneService
{
    public function __construct(array $config)
    {
        $db = Database::getInstance();
        $db->prepare("SELECT * FROM shipping_zones WHERE store_id = :id")
           ->execute(['id' => $config['store_id']]);
    }
}

class PaymentService
{
    private array $config;
    private ?object $gateway;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 Otra cadena de dependencias
        $this->gateway = new PaymentGateway([
            'use_mock' => $config['use_mock'],
            'environment' => $config['environment'],
        ]);
    }

    public function charge(float $amount, string $method, ?object $customer): array
    {
        return $this->gateway->charge($amount, $method, $customer);
    }
}

class PaymentGateway
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 SSL Verification Disabled en produccion real
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    public function charge(float $amount, string $method, ?object $customer): array
    {
        if ($this->config['use_mock']) {
            return ['success' => true, 'transaction_id' => 'mock-' . uniqid()];
        }
        return ['success' => true, 'transaction_id' => 'real-' . uniqid()];
    }
}

class EmailService
{
    private array $config;
    private ?object $templateEngine;

    public function __construct(array $config)
    {
        $this->config = $config;
        // #15 Otra cadena...
        $this->templateEngine = new TemplateEngine(['store_id' => $config['store_id']]);
    }

    public function send(string $to, string $template, array $data): array
    {
        if ($this->config['skip']) {
            return ['skipped' => true];
        }
        return ['sent' => true, 'to' => $to];
    }
}

class TemplateEngine
{
    public function __construct(array $config)
    {
        $db = Database::getInstance();
        $db->prepare("SELECT * FROM email_templates WHERE store_id = :id")
           ->execute(['id' => $config['store_id']]);
    }
}
