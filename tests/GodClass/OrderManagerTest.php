<?php

declare(strict_types=1);

namespace Tests\GodClass;

use AntiPatterns\Common\Database;
use AntiPatterns\GodClass\antipattern\OrderManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests que demuestran lo dificil que es testear una God Class.
 *
 * Problemas evidentes:
 * 1. No puedes testear un metodo sin inicializar toda la clase
 * 2. Los efectos secundarios hacen que los tests no sean aislables
 * 3. El estado mutable compartido causa tests que dependen del orden
 * 4. Las queries SQL requieren una BD configurada
 * 5. No hay forma de mockear dependencias internas
 */
class OrderManagerTest extends TestCase
{
    private OrderManager $manager;

    protected function setUp(): void
    {
        Database::reset();
        $this->setUpDatabase();
        $this->manager = new OrderManager(1);
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function setUpDatabase(): void
    {
        $db = Database::getInstance();

        $db->exec("
            CREATE TABLE IF NOT EXISTS stores (
                id INTEGER PRIMARY KEY,
                name TEXT,
                timezone TEXT DEFAULT 'Europe/Madrid'
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY,
                store_id INTEGER,
                name TEXT,
                price REAL,
                stock INTEGER DEFAULT 0,
                reserved_stock INTEGER DEFAULT 0,
                category_id INTEGER
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY,
                name TEXT,
                parent_id INTEGER
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT,
                phone TEXT,
                active INTEGER DEFAULT 1,
                merged_to INTEGER
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS carts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER,
                store_id INTEGER,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cart_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cart_id INTEGER,
                product_id INTEGER,
                quantity INTEGER,
                price REAL
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                product_id INTEGER,
                customer_id INTEGER,
                customer_name TEXT,
                customer_email TEXT,
                customer_phone TEXT,
                quantity INTEGER DEFAULT 1,
                unit_price REAL,
                subtotal REAL,
                discount REAL DEFAULT 0,
                tax REAL,
                total REAL,
                status INTEGER DEFAULT 1,
                is_vip INTEGER DEFAULT 0,
                cancellation_reason TEXT,
                refund_amount REAL DEFAULT 0,
                cancelled_at TEXT,
                coupon_code TEXT,
                discount_amount REAL DEFAULT 0,
                total_amount REAL,
                shipped_at TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER,
                product_id INTEGER,
                quantity INTEGER,
                price REAL
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT,
                type TEXT,
                value REAL,
                valid_from TEXT,
                valid_until TEXT,
                active INTEGER DEFAULT 1
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS shipments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                order_id INTEGER,
                shipping_type INTEGER DEFAULT 1,
                estimated_days INTEGER DEFAULT 5,
                shipping_cost REAL DEFAULT 0,
                status INTEGER DEFAULT 1,
                carrier TEXT,
                tracking_number TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS returns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                order_id INTEGER,
                product_id INTEGER,
                reason TEXT,
                status INTEGER DEFAULT 1,
                refund_amount REAL DEFAULT 0,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                customer_id INTEGER,
                rating INTEGER,
                comment TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS restock_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                store_id INTEGER,
                status INTEGER DEFAULT 1
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER,
                invoice_number TEXT,
                subtotal REAL,
                tax_amount REAL,
                total REAL,
                tax_rate REAL,
                issued_at TEXT,
                status INTEGER DEFAULT 1
            )
        ");

        // Datos de prueba
        $db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
        $db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (1, 1, 'Test Product A', 100.0, 50)");
        $db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (2, 1, 'Test Product B', 150.0, 30)");
    }

    /**
     * Test basico de creacion - pero requiere toda la infraestructura inicializada.
     * No puedes testear solo la logica de negocio aisladamente.
     */
    public function testCreateOrderRequiresFullInfrastructure(): void
    {
        $result = $this->manager->createOrder([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+34 600 123 456',
            'product_id' => 1,
            'quantity' => 4,
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['order_id']);
        $this->assertArrayHasKey('html', $result); // Side effect: devuelve HTML mezclado
    }

    /**
     * Demuestra que el estado mutable compartido causa problemas.
     * El resultado de cancelOrder depende de si se llamo a otro metodo antes.
     */
    public function testCancelOrderDependsOnMutableState(): void
    {
        // Creamos un pedido
        $createResult = $this->manager->createOrder([
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+34 600 654 321',
            'product_id' => 1,
            'quantity' => 2,
        ]);

        $orderId = $createResult['order_id'];

        // El estado interno currentOrder se actualizo como side effect
        $currentOrder = $this->manager->getCurrentOrder();
        $this->assertEquals($orderId, $currentOrder->id);

        // Cancelamos - funciona porque el estado interno esta actualizado
        $cancelResult = $this->manager->cancelOrder([
            'order_id' => $orderId,
            'reason' => 'Test cancellation',
        ]);

        $this->assertTrue($cancelResult['success']);
        $this->assertGreaterThan(0, $cancelResult['refund_amount']);
    }

    /**
     * Demuestra que no puedes testear getOrders sin tener datos en BD.
     * El metodo tiene acoplamiento fuerte con la base de datos.
     */
    public function testGetOrdersRequiresDatabaseData(): void
    {
        // Insertamos datos directamente en BD - no hay forma de inyectar datos
        $db = Database::getInstance();
        $db->exec("
            INSERT INTO orders (store_id, product_id, customer_name, quantity, unit_price, subtotal, tax, total, status, total_amount, created_at)
            VALUES (1, 1, 'Test Customer', 4, 100, 400, 84, 484, 1, 484, datetime('now'))
        ");

        $result = $this->manager->getOrders([]);

        $this->assertGreaterThan(0, $result['total']);
        $this->assertArrayHasKey('orders', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * Demuestra temporal coupling: el estado interno se comparte entre llamadas.
     * currentOrder se modifica como side effect y afecta a metodos posteriores.
     */
    public function testTemporalCouplingBetweenMethods(): void
    {
        // Primera pedido
        $first = $this->manager->createOrder([
            'customer_name' => 'First Customer',
            'customer_email' => 'first@example.com',
            'customer_phone' => '+34 600 111 111',
            'product_id' => 1,
            'quantity' => 3,
        ]);

        // El estado interno currentOrder se modifico como side effect
        $currentOrder = $this->manager->getCurrentOrder();
        $this->assertEquals($first['order_id'], $currentOrder->id);
        $this->assertEquals('First Customer', $currentOrder->customer);

        // Segunda pedido en otro producto - el estado interno cambia
        $second = $this->manager->createOrder([
            'customer_name' => 'Second Customer',
            'customer_email' => 'second@example.com',
            'customer_phone' => '+34 600 222 222',
            'product_id' => 2,
            'quantity' => 1,
        ]);

        // Ahora currentOrder apunta al segundo pedido
        // Si alguien guardo una referencia al primero, los datos son inconsistentes
        $this->assertEquals($second['order_id'], $this->manager->getCurrentOrder()->id);
        $this->assertEquals('Second Customer', $this->manager->getCurrentOrder()->customer);

        // La primera referencia sigue apuntando al mismo objeto pero con datos sobrescritos
        $this->assertEquals('Second Customer', $currentOrder->customer); // Bug: se sobrescribio!
    }

    /**
     * Demuestra que los metodos de consulta tienen efectos secundarios ocultos.
     * showCatalog modifica el estado de restock mientras "solo consulta".
     */
    public function testQueryMethodsHaveHiddenSideEffects(): void
    {
        // Creamos un pedido con status 3 (shipped)
        $db = Database::getInstance();
        $db->exec("
            INSERT INTO orders (store_id, product_id, customer_name, quantity, unit_price, subtotal, tax, total, status, total_amount, created_at)
            VALUES (1, 1, 'Shipped Customer', 4, 100, 400, 84, 484, 3, 484, '2025-10-01')
        ");

        // Antes de llamar a showCatalog, verificamos que no hay tareas de restock
        $beforeCount = $db->query("SELECT COUNT(*) FROM restock_queue")->fetchColumn();

        // showCatalog deberia ser solo consulta, pero tiene side effects
        $this->manager->showCatalog([
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-10',
        ]);

        // Ahora hay una tarea de restock creada como side effect
        $afterCount = $db->query("SELECT COUNT(*) FROM restock_queue")->fetchColumn();
        $this->assertGreaterThan($beforeCount, $afterCount);
    }

    /**
     * Demuestra que no puedes testear la logica de impuestos aisladamente.
     * Tienes que instanciar toda la God Class.
     */
    public function testCannotTestTaxLogicInIsolation(): void
    {
        // Para testear calculateTaxes necesitas toda la infraestructura
        $result = $this->manager->calculateTaxes([
            'amount' => 1000,
            'tax_type' => 'general',
            'location' => 'peninsula',
        ]);

        $this->assertEquals(1000, $result['base']);
        $this->assertEquals(210, $result['tax_amount']);
        $this->assertEquals(1210, $result['total']);
    }

    /**
     * Demuestra que el cache mutable compartido puede causar inconsistencias.
     */
    public function testMutableCacheCausesInconsistencies(): void
    {
        // El cache esta vacio inicialmente
        $this->assertEmpty($this->manager->getOrderCache());

        // Llamamos a getOrders - llena el cache como side effect
        $db = Database::getInstance();
        $db->exec("
            INSERT INTO orders (store_id, product_id, customer_name, quantity, unit_price, subtotal, tax, total, status, total_amount, created_at)
            VALUES (1, 1, 'Cache Test', 2, 100, 200, 42, 242, 1, 242, datetime('now'))
        ");

        $this->manager->getOrders([]);

        // El cache ahora tiene datos - pero si alguien modifica la BD directamente,
        // el cache queda desactualizado
        $cache = $this->manager->getOrderCache();
        $this->assertGreaterThan(0, $cache['total']);
    }

    /**
     * Demuestra que los flags booleanos controlan comportamiento de forma implicita.
     */
    public function testBooleanFlagsControlBehaviorImplicitly(): void
    {
        // Con showShipping = 0, no incluye datos de envio
        $managerNoShipping = new OrderManager(1, ['show_shipping' => 0]);

        $db = Database::getInstance();
        $db->exec("
            INSERT INTO orders (store_id, product_id, customer_name, quantity, unit_price, subtotal, tax, total, status, total_amount, created_at)
            VALUES (1, 1, 'Flag Test', 2, 100, 200, 42, 242, 1, 242, datetime('now'))
        ");

        $result = $managerNoShipping->showCatalog([
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-10',
        ]);

        // El HTML no deberia incluir seccion de envio
        $this->assertStringNotContainsString('shipping', strtolower($result));
    }

    /**
     * Demuestra que executeAction con numeros es confuso y propenso a errores.
     */
    public function testActionNumbersAreConfusing(): void
    {
        // Que hace la accion 1? Tienes que mirar el codigo
        $result1 = $this->manager->executeAction(1, [
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-10',
        ]);
        $this->assertIsString($result1); // Devuelve HTML

        // Que hace la accion 9000? Magic number
        $result9000 = $this->manager->executeAction(9000, []);
        $this->assertIsString($result9000); // Devuelve CSV

        // Accion inexistente
        $resultInvalid = $this->manager->executeAction(999, []);
        $this->assertArrayHasKey('error', $resultInvalid);
    }

    /**
     * Demuestra que no puedes reutilizar la logica de negocio sin la presentacion.
     */
    public function testBusinessLogicCannotBeSeparatedFromPresentation(): void
    {
        $result = $this->manager->createOrder([
            'customer_name' => 'API User',
            'customer_email' => 'api@example.com',
            'customer_phone' => '+34 600 999 999',
            'product_id' => 2,
            'quantity' => 2,
        ]);

        // Quiero solo los datos, pero tambien me devuelve HTML renderizado
        $this->assertArrayHasKey('order_id', $result);
        $this->assertArrayHasKey('html', $result); // No puedo evitar recibir HTML

        // Si quiero usar esto en una API JSON, tengo que ignorar el HTML
        $this->assertStringContainsString('<div', $result['html']);
    }
}
