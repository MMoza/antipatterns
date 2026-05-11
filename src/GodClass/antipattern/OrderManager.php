<?php

declare(strict_types=1);

namespace AntiPatterns\GodClass\antipattern;

use AntiPatterns\Common\Database;
use Exception;
use PDO;

/**
 * OrderManager - God Class Antipattern
 *
 * Esta clase hace DEMASIADAS cosas:
 * - Gestion de productos (catalogo, stock, categorias)
 * - Carrito de compra (añadir, quitar, total)
 * - Gestion de pedidos (crear, actualizar estado, cancelar)
 * - Calculo de precios (envio, cupones, impuestos)
 * - Gestion de clientes (info, direcciones, fusionar cuentas)
 * - Inventario (comprobar stock, reservar, alertas)
 * - Envios (calcular, asignar transportista, rastrear)
 * - Devoluciones (crear, reembolsar, politica)
 * - Notificaciones (confirmacion, envio)
 * - Analytics (ventas, top productos, estadisticas)
 * - Renderizado HTML (resumen pedido, carrito, lista productos)
 * - Acceso directo a base de datos con queries raw
 */
class OrderManager
{
    // Mutable shared state - todo se lee y escribe desde cualquier metodo
    private $storeId;
    private $currentOrder;
    private $cart;
    private $customerData;
    private $settings;
    private $debugMode;
    private $isInvoiceMode;
    private $showShipping;
    private $showAnalytics;
    private $db;
    private $templateData;
    private $orderCache;
    private $taxRates;
    private $currencySymbol;
    private $maxItems;
    private $startDate;
    private $endDate;
    private $actionNumber;
    private $lastError;
    private $performanceLog;

    // Constructor Heavy - hace 10 cosas a la vez
    public function __construct(int $storeId = 579314, array $config = [])
    {
        $this->storeId = $storeId; // hardcoded ID
        $this->db = Database::getInstance();
        $this->debugMode = $config['debug'] ?? false;
        $this->isInvoiceMode = $config['invoice_mode'] ?? 0;
        $this->showShipping = $config['show_shipping'] ?? 0;
        $this->showAnalytics = $config['show_analytics'] ?? 0;
        $this->maxItems = 36; // magic number
        $this->currencySymbol = '€';
        $this->performanceLog = [];

        // Hardcoded tax rates - deberia ser configuracion externa
        $this->taxRates = [
            'general' => 0.21,
            'reduced' => 0.10,
            'super_reduced' => 0.04,
            'canarias_igic' => 0.07,
        ];

        // Carga inicial de la tienda - efecto secundario en constructor
        $this->loadStore();

        // Inicializa estado vacio
        $this->currentOrder = new \stdClass();
        $this->cart = new \stdClass();
        $this->customerData = new \stdClass();
        $this->templateData = new \stdClass();
        $this->orderCache = [];
        $this->lastError = null;
    }

    // God Method - switch con 15+ acciones numericas
    public function executeAction(int $action, array $requestData): mixed
    {
        $this->actionNumber = $action;
        $this->logPerformance("action_{$action}_start");

        switch ($action) {
            case 1:
                return $this->showCatalog($requestData);
            case 2:
                return $this->getOrders($requestData);
            case 3:
                return $this->getOrderDetail($requestData);
            case 4:
                return $this->createOrder($requestData);
            case 5:
                return $this->modifyOrder($requestData);
            case 6:
                return $this->cancelOrder($requestData);
            case 7:
                return $this->checkStock($requestData);
            case 8:
                return $this->generateInvoice($requestData);
            case 9:
                return $this->getCustomerInfo($requestData);
            case 10:
                return $this->mergeCustomers($requestData);
            case 11:
                return $this->assignShipping($requestData);
            case 12:
                return $this->getShippingStatus($requestData);
            case 13:
                return $this->calculateTaxes($requestData);
            case 14:
                return $this->applyCoupon($requestData);
            case 15:
                return $this->renderOrderSummary($requestData);
            case 9000: // Magic number para accion especial
                return $this->exportOrders($requestData);
            default:
                return ['error' => "Accion {$action} no reconocida"];
        }
    }

    // Mezcla presentacion con logica de dominio
    public function showCatalog(array $request): string
    {
        $this->startDate = $request['start_date'] ?? date('Y-m-d');
        $this->endDate = $request['end_date'] ?? date('Y-m-d', strtotime('+30 days'));

        // Query SQL inline con interpolacion directa - SQL injection risk
        $query = "SELECT * FROM orders 
                  WHERE store_id = $this->storeId 
                  AND created_at >= '$this->startDate' 
                  AND created_at <= '$this->endDate'
                  ORDER BY created_at";

        $stmt = $this->db->query($query);
        $orders = $stmt->fetchAll();

        // Array-based domain modeling - estructura compleja sin validacion
        $catalogData = [];
        foreach ($orders as $order) {
            $catalogData[$order['product_id']]['orders'][] = [
                'id' => $order['id'],
                'customer' => $order['customer_name'],
                'status' => $order['status'],
                'color' => $this->getStatusColor($order['status']),
            ];

            // Hidden side effect - modifica estado mientras "consulta"
            if ($order['status'] == 3) {
                $this->markProductForRestock($order['product_id']);
            }
        }

        // Mas logica de envios mezclada
        if ($this->showShipping == 1) {
            $shippingTasks = $this->getShippingTasksForPeriod($this->startDate, $this->endDate);
            $catalogData['shipping'] = $shippingTasks;
        }

        // Presentacion mezclada con dominio
        $html = $this->renderCatalogHtml($catalogData);

        return $html;
    }

    // Metodo que deberia ser consulta pero tiene efectos secundarios
    public function getOrders(array $request): array
    {
        $filters = '';
        if (!empty($request['status'])) {
            $filters .= " AND status = {$request['status']}";
        }
        if (!empty($request['product_id'])) {
            $filters .= " AND product_id = {$request['product_id']}";
        }
        if (!empty($request['customer_name'])) {
            // LIKE con wildcard inicial - full table scan
            $filters .= " AND customer_name LIKE '%{$request['customer_name']}%'";
        }

        // SELECT * anti-pattern
        $query = "SELECT * FROM orders 
                  WHERE store_id = $this->storeId
                  {$filters}
                  ORDER BY created_at DESC
                  LIMIT 10000";

        try {
            $stmt = $this->db->query($query);
            $orders = $stmt->fetchAll();
        } catch (Exception $e) {
            // Silent catch - error invisible
        }

        // Transformacion compleja con arrays asociativos
        $result = [];
        $result['orders'] = [];
        $result['total'] = 0;
        $result['summary'] = [];

        foreach ($orders as $order) {
            $result['orders'][$order['id']]['data'] = new \stdClass();
            $result['orders'][$order['id']]['data']->id = $order['id'];
            $result['orders'][$order['id']]['data']->customer = $order['customer_name'];
            $result['orders'][$order['id']]['data']->product = $order['product_id'];
            $result['orders'][$order['id']]['data']->date = $order['created_at'];
            $result['orders'][$order['id']]['data']->total = $order['total_amount'];
            $result['orders'][$order['id']]['data']->status = $order['status'];

            // Data clumps - siempre viajan juntos pero no encapsulados
            $result['orders'][$order['id']]['dates']['created'] = $order['created_at'];
            $result['orders'][$order['id']]['dates']['shipped'] = $order['shipped_at'] ?? null;
            $result['orders'][$order['id']]['dates']['items'] = $this->countOrderItems(
                $order['id']
            );

            // Mas efectos secundarios ocultos
            $result['orders'][$order['id']]['shipping'] = $this->getShippingStatus($order['id']);
            $result['orders'][$order['id']]['returns'] = $this->getReturnStatus($order['id']);

            // Acumuladores con flags
            $statusKey = $this->getStatusName($order['status']);
            if (!isset($result['summary'][$statusKey])) {
                $result['summary'][$statusKey] = 0;
            }
            $result['summary'][$statusKey]++;
            $result['total']++;
        }

        // Cache mutable compartido
        $this->orderCache = $result;

        return $result;
    }

    // DRY violation - logica duplicada con getOrders
    public function getOrdersMonthlyView(array $request): array
    {
        $filters = '';
        if (!empty($request['status'])) {
            $filters .= " AND status = {$request['status']}";
        }
        if (!empty($request['product_id'])) {
            $filters .= " AND product_id = {$request['product_id']}";
        }

        // Query casi identica a getOrders
        $query = "SELECT * FROM orders 
                  WHERE store_id = $this->storeId
                  {$filters}
                  ORDER BY created_at DESC
                  LIMIT 10000";

        try {
            $stmt = $this->db->query($query);
            $orders = $stmt->fetchAll();
        } catch (Exception $e) {
            // Otro silent catch
        }

        // Estructura diferente pero logica similar
        $result = [];
        $result['months'] = [];

        foreach ($orders as $order) {
            $monthNumber = date('m', strtotime($order['created_at']));
            $result['months'][$monthNumber]['orders'][] = [
                'id' => $order['id'],
                'customer' => $order['customer_name'],
                'product' => $order['product_id'],
            ];
        }

        return $result;
    }

    // Mezcla validacion, persistencia, calculo de precios y notificaciones
    public function createOrder(array $request): array
    {
        // Validacion inline mezclada con logica
        if (empty($request['customer_name'])) {
            return ['error' => 'Nombre de cliente requerido'];
        }
        if (empty($request['product_id'])) {
            return ['error' => 'Producto requerido'];
        }
        if (empty($request['quantity'])) {
            return ['error' => 'Cantidad requerida'];
        }

        // Temporal coupling - debes llamar en orden correcto
        $stock = $this->checkStock([
            'product_id' => $request['product_id'],
            'quantity' => $request['quantity'],
        ]);

        if (!$stock['available']) {
            return ['error' => 'Producto no disponible'];
        }

        // Calculo de precio con logica hardcoded
        $quantity = (int) $request['quantity'];
        $pricePerUnit = $this->getProductPrice($request['product_id']);
        $subtotal = $quantity * $pricePerUnit;

        // Descuentos con magic numbers
        if ($quantity >= 7) {
            $discount = $subtotal * 0.10; // 10% para compras grandes
        } elseif ($quantity >= 3) {
            $discount = $subtotal * 0.05; // 5% para compras medias
        } else {
            $discount = 0;
        }

        $subtotal -= $discount;
        $tax = $subtotal * $this->taxRates['general'];
        $total = $subtotal + $tax;

        // Mas validacion dispersa
        if ($total > 5000) {
            // Flag de pedido VIP hardcoded
            $request['is_vip'] = 1;
        }

        // SQL injection vulnerability
        $isVip = $request['is_vip'] ?? 0;
        $query = "INSERT INTO orders 
                  (store_id, product_id, customer_name, customer_email, customer_phone, 
                   quantity, unit_price, subtotal, discount, tax, total, 
                   status, is_vip, created_at)
                  VALUES 
                  ($this->storeId, {$request['product_id']}, '{$request['customer_name']}', 
                   '{$request['customer_email']}', '{$request['customer_phone']}',
                   $quantity, $pricePerUnit, 
                   $subtotal, $discount, $tax, $total, 
                   1, $isVip, datetime('now'))";

        $this->db->exec($query);
        $orderId = (int) $this->db->lastInsertId();

        // Efecto secundario: actualiza estado mutable
        $this->currentOrder->id = $orderId;
        $this->currentOrder->total = $total;
        $this->currentOrder->customer = $request['customer_name'];
        $this->currentOrder->date = date('Y-m-d');

        // Mas efectos secundarios: crea envio
        $this->createShipment($orderId, date('Y-m-d'));

        // Y mas: envia email (simulado)
        $this->sendConfirmationEmail($request['customer_email'], $orderId, $total);

        // Devuelve estructura inconsistente - a veces array, a veces stdClass
        return [
            'success' => true,
            'order_id' => $orderId,
            'total' => $total,
            'html' => $this->renderOrderConfirmationHtml($orderId, $request, $total),
        ];
    }

    // Action at distance - modifica estado que afecta a otros metodos
    public function modifyOrder(array $request): array
    {
        $orderId = $request['order_id'];

        // Carga el pedido y modifica estado compartido
        $query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
        $stmt = $this->db->query($query);
        $order = $stmt->fetch();

        if (!$order) {
            return ['error' => 'Pedido no encontrado'];
        }

        // Modifica estado mutable compartido - action at distance
        $this->currentOrder->id = $order['id'];
        $this->currentOrder->originalTotal = $order['total'];

        $updates = [];
        if (!empty($request['quantity'])) {
            $updates[] = "quantity = {$request['quantity']}";
            $this->currentOrder->newQuantity = $request['quantity'];
        }
        if (!empty($request['customer_name'])) {
            $updates[] = "customer_name = '{$request['customer_name']}'";
        }
        if (!empty($request['status'])) {
            $updates[] = "status = {$request['status']}";
        }

        if (empty($updates)) {
            return ['error' => 'No hay cambios'];
        }

        // Recalcula precio si cambia cantidad - logica duplicada de createOrder
        $quantity = (int) ($request['quantity'] ?? $order['quantity']);
        $pricePerUnit = $this->getProductPrice($order['product_id']);
        $subtotal = $quantity * $pricePerUnit;

        if ($quantity >= 7) {
            $discount = $subtotal * 0.10;
        } elseif ($quantity >= 3) {
            $discount = $subtotal * 0.05;
        } else {
            $discount = 0;
        }

        $subtotal -= $discount;
        $tax = $subtotal * $this->taxRates['general'];
        $total = $subtotal + $tax;

        $updates[] = "subtotal = $subtotal";
        $updates[] = "discount = $discount";
        $updates[] = "tax = $tax";
        $updates[] = "total = $total";

        $updateQuery = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = $orderId";
        $this->db->exec($updateQuery);

        // Efecto secundario: si cambio cantidad, reprograma envio
        if (!empty($request['quantity'])) {
            $this->rescheduleShipment($orderId, date('Y-m-d'));
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'new_total' => $total,
            'price_difference' => $total - $order['total'],
        ];
    }

    // Temporal coupling - requiere que se llame en orden especifico
    public function cancelOrder(array $request): array
    {
        $orderId = $request['order_id'];
        $reason = $request['reason'] ?? 'Sin motivo';

        // Necesitas haber cargado el pedido antes (temporal coupling implicito)
        if (!isset($this->currentOrder->id) || $this->currentOrder->id != $orderId) {
            // Intenta cargarlo si no esta cargado
            $query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
            $stmt = $this->db->query($query);
            $order = $stmt->fetch();

            if (!$order) {
                return ['error' => 'Pedido no encontrado'];
            }

            // Efecto secundario: modifica estado
            $this->currentOrder->id = $order['id'];
            $this->currentOrder->total = $order['total'];
        }

        // Politica de cancelacion con magic numbers
        $orderDate = new \DateTime($this->currentOrder->date ?? $order['created_at']);
        $today = new \DateTime();
        $daysSinceOrder = (int) $today->diff($orderDate)->format('%r%a');

        if ($daysSinceOrder < 2) {
            $refundPercentage = 100; // Cancelacion gratuita
        } elseif ($daysSinceOrder < 7) {
            $refundPercentage = 50; // 50% de reembolso
        } elseif ($daysSinceOrder < 14) {
            $refundPercentage = 25; // 25% de reembolso
        } else {
            $refundPercentage = 0; // Sin reembolso
        }

        $refundAmount = $this->currentOrder->total * ($refundPercentage / 100);

        // Actualiza estado
        $query = "UPDATE orders SET status = 4, cancellation_reason = '$reason', 
                  refund_amount = $refundAmount, cancelled_at = datetime('now') 
                  WHERE id = $orderId";
        $this->db->exec($query);

        // Efectos secundarios: cancela envio
        $this->cancelShipments($orderId);

        return [
            'success' => true,
            'refund_amount' => $refundAmount,
            'refund_percentage' => $refundPercentage,
        ];
    }

    // Mezcla logica de stock con queries complejas
    public function checkStock(array $request): array
    {
        $productId = $request['product_id'];
        $quantity = $request['quantity'] ?? 1;

        // Query compleja inline
        $query = "SELECT stock, reserved_stock 
                  FROM products 
                  WHERE store_id = $this->storeId 
                  AND id = $productId";

        $stmt = $this->db->query($query);
        $result = $stmt->fetch();

        // Tambien verifica devoluciones pendientes
        $returnsQuery = "SELECT COUNT(*) as pending_returns 
                        FROM returns 
                        WHERE store_id = $this->storeId 
                        AND product_id = $productId
                        AND status = 1";

        try {
            $stmt2 = $this->db->query($returnsQuery);
            $returns = $stmt2->fetch();
        } catch (Exception $e) {
            // Silent catch - si falla la tabla de devoluciones, ignoramos
            $returns = ['pending_returns' => 0];
        }

        $availableStock = ($result['stock'] ?? 0) - ($result['reserved_stock'] ?? 0);
        $isAvailable = $availableStock >= $quantity && $returns['pending_returns'] == 0;

        // Modifica estado compartido como side effect
        $this->customerData->lastCheckedId = $productId;
        $this->customerData->lastAvailable = $isAvailable;

        return [
            'available' => $isAvailable,
            'product_id' => $productId,
            'quantity_requested' => $quantity,
            'stock_available' => $availableStock,
            'pending_returns' => (int) $returns['pending_returns'],
        ];
    }

    // Facturacion mezclada con presentacion y logica de negocio
    public function generateInvoice(array $request): array
    {
        $orderId = $request['order_id'];

        // Carga datos del pedido
        $query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
        $stmt = $this->db->query($query);
        $order = $stmt->fetch();

        if (!$order) {
            return ['error' => 'Pedido no encontrado'];
        }

        // Calculo de impuestos - logica que deberia estar en un servicio separado
        $taxes = $this->calculateOrderTaxes($order);

        // Genera numero de factura con logica hardcoded
        $invoiceNumber = 'FAC-' . date('Y') . '-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);

        // SQL injection
        $insertQuery = "INSERT INTO invoices 
                       (order_id, invoice_number, subtotal, tax_amount, total, 
                        tax_rate, issued_at, status)
                       VALUES 
                       ($orderId, '$invoiceNumber', {$order['subtotal']}, 
                        {$taxes['total_tax']}, {$order['total']}, 
                        {$this->taxRates['general']}, datetime('now'), 1)";

        $this->db->exec($insertQuery);
        $invoiceId = (int) $this->db->lastInsertId();

        // Mezcla presentacion con dominio
        if ($this->isInvoiceMode == 1) {
            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'html' => $this->renderInvoiceHtml($order, $taxes, $invoiceNumber),
            ];
        }

        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'taxes' => $taxes,
        ];
    }

    // Gestion de clientes mezclada con la clase
    public function getCustomerInfo(array $request): array
    {
        $customerId = $request['customer_id'];

        $query = "SELECT * FROM customers WHERE id = $customerId";
        $stmt = $this->db->query($query);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['error' => 'Cliente no encontrado'];
        }

        // Carga historial de pedidos - N+1 query pattern
        $ordersQuery = "SELECT * FROM orders WHERE customer_id = $customerId ORDER BY created_at DESC";
        $stmt2 = $this->db->query($ordersQuery);
        $orders = $stmt2->fetchAll();

        // Construye estructura compleja con arrays
        $customerData = [];
        $customerData['info'] = $customer;
        $customerData['total_orders'] = count($orders);
        $customerData['total_spent'] = 0;
        $customerData['orders'] = [];

        foreach ($orders as $order) {
            $customerData['orders'][] = [
                'id' => $order['id'],
                'store' => $order['store_id'],
                'date' => $order['created_at'],
                'total' => $order['total_amount'],
            ];
            $customerData['total_spent'] += $order['total_amount'];
        }

        // Modifica estado compartido
        $this->customerData = new \stdClass();
        $this->customerData->id = $customer['id'];
        $this->customerData->name = $customer['name'];
        $this->customerData->totalSpent = $customerData['total_spent'];

        return $customerData;
    }

    // Mezcla de clientes con logica compleja y efectos secundarios
    public function mergeCustomers(array $request): array
    {
        $sourceId = $request['source_customer_id'];
        $targetId = $request['target_customer_id'];

        if ($sourceId == $targetId) {
            return ['error' => 'No se puede fusionar un cliente consigo mismo'];
        }

        // Carga ambos clientes
        $source = $this->db->query("SELECT * FROM customers WHERE id = $sourceId")->fetch();
        $target = $this->db->query("SELECT * FROM customers WHERE id = $targetId")->fetch();

        if (!$source || !$target) {
            return ['error' => 'Cliente no encontrado'];
        }

        // Actualiza todos los pedidos del source al target
        $this->db->exec("UPDATE orders SET customer_id = $targetId WHERE customer_id = $sourceId");

        // Fusiona datos de contacto - logica ad-hoc
        $updates = [];
        if (empty($target['email']) && !empty($source['email'])) {
            $updates[] = "email = '{$source['email']}'";
        }
        if (empty($target['phone']) && !empty($source['phone'])) {
            $updates[] = "phone = '{$source['phone']}'";
        }

        if (!empty($updates)) {
            $this->db->exec("UPDATE customers SET " . implode(', ', $updates) . " WHERE id = $targetId");
        }

        // "Elimina" el source (soft delete inconsistente)
        $this->db->exec("UPDATE customers SET active = 0, merged_to = $targetId WHERE id = $sourceId");

        return [
            'success' => true,
            'merged_customer_id' => $targetId,
            'removed_customer_id' => $sourceId,
        ];
    }

    // Envios mezclados con gestion de pedidos
    public function assignShipping(array $request): array
    {
        $orderId = $request['order_id'];
        $date = $request['date'] ?? date('Y-m-d');

        // Verifica el pedido
        $query = "SELECT * FROM orders 
                  WHERE store_id = $this->storeId 
                  AND id = $orderId
                  AND status IN (1, 2)";

        $stmt = $this->db->query($query);
        $order = $stmt->fetch();

        if (!$order) {
            return ['error' => 'Pedido no encontrado o no elegible'];
        }

        // Determina tipo de envio basado en cantidad - magic numbers
        $quantity = $order['quantity'];
        if ($quantity > 7) {
            $shippingType = 3; // Envio urgente
            $estimatedDays = 1;
        } elseif ($quantity > 3) {
            $shippingType = 2; // Envio express
            $estimatedDays = 3;
        } else {
            $shippingType = 1; // Envio estandar
            $estimatedDays = 5;
        }

        // Calcula coste de envio con magic numbers
        $shippingCost = $quantity * 2.5 + 5.0;

        // Crea registro de envio
        $insertQuery = "INSERT INTO shipments 
                       (store_id, order_id, shipping_type, 
                        estimated_days, shipping_cost, status, carrier, created_at)
                       VALUES 
                       ($this->storeId, $orderId, 
                        $shippingType, $estimatedDays, $shippingCost, 1, 'Correos', datetime('now'))";

        $this->db->exec($insertQuery);

        return [
            'shipping_created' => true,
            'shipping_type' => $shippingType,
            'estimated_days' => $estimatedDays,
            'shipping_cost' => $shippingCost,
        ];
    }

    public function getShippingStatus(int $orderId): array
    {
        try {
            $query = "SELECT * FROM shipments WHERE order_id = $orderId ORDER BY created_at DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // Silent catch
            return [];
        }
    }

    public function getReturnStatus(int $orderId): array
    {
        try {
            $query = "SELECT * FROM returns WHERE order_id = $orderId";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // Silent catch
            return [];
        }
    }

    // Calculo de impuestos - logica que deberia estar separada
    public function calculateTaxes(array $request): array
    {
        $amount = $request['amount'] ?? 0;
        $taxType = $request['tax_type'] ?? 'general';
        $location = $request['location'] ?? 'peninsula';

        // Logica de impuestos con hardcoded values
        if ($location == 'canarias') {
            $rate = 0.07; // IGIC
        } elseif ($location == 'ceuta_melilla') {
            $rate = 0.04; // IPSI
        } else {
            $rate = $this->taxRates[$taxType] ?? 0.21;
        }

        // Calculo con precision de float (problema para dinero)
        $taxAmount = $amount * $rate;
        $total = $amount + $taxAmount;

        return [
            'base' => $amount,
            'rate' => $rate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $this->currencySymbol,
        ];
    }

    public function applyCoupon(array $request): array
    {
        $orderId = $request['order_id'];
        $couponCode = $request['code'] ?? '';

        // Codigos de cupon hardcoded
        $validCodes = [
            'SUMMER10' => ['type' => 'percentage', 'value' => 10],
            'WINTER15' => ['type' => 'percentage', 'value' => 15],
            'VIP20' => ['type' => 'percentage', 'value' => 20],
            'FLAT50' => ['type' => 'fixed', 'value' => 50],
        ];

        if (!isset($validCodes[$couponCode])) {
            return ['error' => 'Codigo de descuento invalido'];
        }

        $query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
        $order = $this->db->query($query)->fetch();

        if (!$order) {
            return ['error' => 'Pedido no encontrado'];
        }

        $code = $validCodes[$couponCode];
        if ($code['type'] == 'percentage') {
            $discountAmount = $order['total'] * ($code['value'] / 100);
        } else {
            $discountAmount = $code['value'];
        }

        $newTotal = $order['total'] - $discountAmount;

        $this->db->exec("UPDATE orders SET total = $newTotal, coupon_code = '$couponCode', 
                        discount_amount = $discountAmount WHERE id = $orderId");

        return [
            'success' => true,
            'discount_amount' => $discountAmount,
            'new_total' => $newTotal,
        ];
    }

    // Presentacion mezclada con dominio - renderiza HTML
    public function renderOrderSummary(array $request): string
    {
        $orderId = $request['order_id'];

        $query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
        $order = $this->db->query($query)->fetch();

        if (!$order) {
            return '<div class="error">Pedido no encontrado</div>';
        }

        // HTML inline mezclado con logica
        $statusLabels = [
            1 => 'Pendiente',
            2 => 'Procesando',
            3 => 'Enviado',
            4 => 'Cancelado',
            5 => 'Devuelto',
        ];

        $statusColor = $this->getStatusColor($order['status']);

        $html = '<div class="order-summary">';
        $html .= '<h2>Pedido #' . $order['id'] . '</h2>';
        $html .= '<table>';
        $html .= '<tr><td>Cliente:</td><td>' . htmlspecialchars($order['customer_name']) . '</td></tr>';
        $html .= '<tr><td>Fecha:</td><td>' . $order['created_at'] . '</td></tr>';
        $html .= '<tr><td>Cantidad:</td><td>' . $order['quantity'] . '</td></tr>';
        $html .= '<tr><td>Estado:</td><td style="color: ' . $statusColor . '">' . ($statusLabels[$order['status']] ?? 'Desconocido') . '</td></tr>';
        $html .= '<tr><td>Total:</td><td>' . $this->currencySymbol . number_format($order['total'], 2) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    // Export con logica duplicada
    public function exportOrders(array $request): string
    {
        $query = "SELECT * FROM orders WHERE store_id = $this->storeId ORDER BY created_at DESC";
        $stmt = $this->db->query($query);
        $orders = $stmt->fetchAll();

        // Genera CSV inline
        $csv = "ID,Customer,Date,Quantity,Total,Status\n";
        foreach ($orders as $order) {
            $csv .= "{$order['id']},{$order['customer_name']},{$order['created_at']},";
            $csv .= "{$order['quantity']},{$order['total_amount']},{$order['status']}\n";
        }

        return $csv;
    }

    // Metodos auxiliares con logica dispersa

    private function loadStore(): void
    {
        // Carga datos de la tienda - efecto secundario en constructor
        $query = "SELECT * FROM stores WHERE id = $this->storeId";
        try {
            $stmt = $this->db->query($query);
            $store = $stmt->fetch();
            if ($store) {
                $this->settings = $store;
            }
        } catch (Exception $e) {
            // Silent catch - si no existe la tabla, seguimos
        }
    }

    private function getStatusColor(int $status): string
    {
        // Magic numbers para colores
        $colors = [
            1 => '#28a745', // pending
            2 => '#ffc107', // processing
            3 => '#17a2b8', // shipped
            4 => '#dc3545', // cancelled
            5 => '#6c757d', // returned
        ];
        return $colors[$status] ?? '#000000';
    }

    private function getStatusName(int $status): string
    {
        $names = [
            1 => 'pending',
            2 => 'processing',
            3 => 'shipped',
            4 => 'cancelled',
            5 => 'returned',
        ];
        return $names[$status] ?? 'unknown';
    }

    private function countOrderItems(int $orderId): int
    {
        try {
            $query = "SELECT COUNT(*) FROM order_items WHERE order_id = $orderId";
            $stmt = $this->db->query($query);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 1; // magic number fallback
        }
    }

    private function getProductPrice(int $productId): float
    {
        // Query inline
        $query = "SELECT price FROM products WHERE id = $productId AND store_id = $this->storeId";
        try {
            $stmt = $this->db->query($query);
            $product = $stmt->fetch();
            return (float) ($product['price'] ?? 100.0); // magic number default
        } catch (Exception $e) {
            return 100.0; // magic number fallback
        }
    }

    private function markProductForRestock(int $productId): void
    {
        // Efecto secundario oculto
        try {
            $this->db->exec("INSERT INTO restock_queue (product_id, store_id, status) 
                           VALUES ($productId, $this->storeId, 1)");
        } catch (Exception $e) {
            // Silent catch
        }
    }

    private function getShippingTasksForPeriod(string $start, string $end): array
    {
        try {
            $query = "SELECT * FROM shipments 
                     WHERE store_id = $this->storeId 
                     AND created_at BETWEEN '$start' AND '$end'";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private function createShipment(int $orderId, string $date): void
    {
        try {
            $this->db->exec("INSERT INTO shipments (order_id, store_id, created_at, status) 
                           VALUES ($orderId, $this->storeId, '$date', 1)");
        } catch (Exception $e) {
            // Silent catch
        }
    }

    private function rescheduleShipment(int $orderId, string $newDate): void
    {
        try {
            $this->db->exec("UPDATE shipments SET created_at = '$newDate' WHERE order_id = $orderId");
        } catch (Exception $e) {
            // Silent catch
        }
    }

    private function cancelShipments(int $orderId): void
    {
        try {
            $this->db->exec("UPDATE shipments SET status = 0 WHERE order_id = $orderId");
        } catch (Exception $e) {
            // Silent catch
        }
    }

    private function sendConfirmationEmail(string $email, int $orderId, float $total): void
    {
        // Simulacion de envio de email - logica que deberia estar en un servicio
        if ($this->debugMode) {
            error_log("Email to {$email}: Order #{$orderId}, Total: {$this->currencySymbol}{$total}");
        }
    }

    private function calculateOrderTaxes(array $order): array
    {
        $subtotal = (float) $order['subtotal'];
        $rate = $this->taxRates['general'];
        $taxAmount = $subtotal * $rate;

        return [
            'subtotal' => $subtotal,
            'tax_rate' => $rate,
            'total_tax' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ];
    }

    private function renderCatalogHtml(array $data): string
    {
        $html = '<div class="catalog">';
        $html .= '<h3>Catalogo de Pedidos</h3>';

        foreach ($data as $productId => $productData) {
            if ($productId === 'shipping') {
                continue;
            }
            $html .= '<div class="product" data-product="' . $productId . '">';
            $html .= '<h4>Product ' . $productId . '</h4>';

            if (!empty($productData['orders'])) {
                foreach ($productData['orders'] as $order) {
                    $html .= '<div class="order" style="background: ' . $order['color'] . '">';
                    $html .= $order['customer'] . ' - ' . $order['status'];
                    $html .= '</div>';
                }
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function renderOrderConfirmationHtml(int $orderId, array $request, float $total): string
    {
        $html = '<div class="confirmation">';
        $html .= '<h2>Pedido Confirmado</h2>';
        $html .= '<p>Order #' . $orderId . '</p>';
        $html .= '<p>Customer: ' . htmlspecialchars($request['customer_name']) . '</p>';
        $html .= '<p>Total: ' . $this->currencySymbol . number_format($total, 2) . '</p>';
        $html .= '</div>';
        return $html;
    }

    private function renderInvoiceHtml(array $order, array $taxes, string $invoiceNumber): string
    {
        $html = '<div class="invoice">';
        $html .= '<h2>Factura ' . $invoiceNumber . '</h2>';
        $html .= '<p>Customer: ' . htmlspecialchars($order['customer_name']) . '</p>';
        $html .= '<table>';
        $html .= '<tr><td>Subtotal:</td><td>' . $this->currencySymbol . number_format($order['subtotal'], 2) . '</td></tr>';
        $html .= '<tr><td>Tax:</td><td>' . $this->currencySymbol . number_format($taxes['total_tax'], 2) . '</td></tr>';
        $html .= '<tr><td><strong>Total:</strong></td><td><strong>' . $this->currencySymbol . number_format($order['total'], 2) . '</strong></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    private function logPerformance(string $label): void
    {
        if ($this->debugMode) {
            $this->performanceLog[$label] = microtime(true);
        }
    }

    // Getters para estado mutable - expone internals
    public function getCurrentOrder(): \stdClass
    {
        return $this->currentOrder;
    }

    public function getCustomerData(): \stdClass
    {
        return $this->customerData;
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getOrderCache(): array
    {
        return $this->orderCache;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
