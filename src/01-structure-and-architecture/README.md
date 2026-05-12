# 01 - Structure & Architecture / Estructura y Arquitectura

## El problema

Cuando la **estructura y arquitectura** de un codigo legacy viola los principios basicos de diseño, se crean problemas sistematicos que afectan a todo el codebase. Este grupo cubre los antipatrones estructurales mas comunes observados en codebases PHP legacy de e-commerce.

## Antipatrones identificados

| # | Antipatron | Como se manifiesta | Severidad |
|---|---|---|---|
| 1 | **God Class** | `OrderManager` con 15+ responsabilidades | 🔴 Critico |
| 2 | **God Method** | `executeAction()` con switch de 15+ casos numericos | 🔴 Critico |
| 3 | **Inheritance Abuse** | `OrderManager extends BaseManager` - herencia como mecanismo de inyeccion de dependencias | 🟠 Alto |
| 4 | **Constructor Heavy** | Constructor padre hace 8 cosas + hijo hace 5 mas | 🟠 Alto |
| 5 | **High Cognitive Load** | Rastrear 7+ cosas para entender un flujo | 🟠 Alto |
| 6 | **Leaky Abstractions** | Getters exponen `stdClass` internos, SQL expuesto | 🟠 Alto |
| 7 | **Hardcoded Infrastructure** | IDs (`579314`), tax rates, magic numbers (`36`, `9000`) | 🟠 Alto |
| 8 | **Infrastructure Leakage** | Queries SQL inline mezclados con logica de negocio | 🟠 Alto |

### 1. God Class / Clase Dios

Una clase que hace **demasiadas cosas**, violando el Principio de Responsabilidad Unica (SRP).

```php
// OrderManager hace TODO:
// - Catalogo, pedidos, clientes, envios, devoluciones
// - Calculo de precios, impuestos, cupones
// - Renderizado HTML, emails, analytics
class OrderManager extends BaseManager { /* 1100+ lineas */ }
```

**Impacto:**
- Imposible de testear unitariamente
- Cualquier cambio tiene riesgo de romper funcionalidad no relacionada
- Merge conflicts constantes en equipo
- Onboarding de nuevos desarrolladores: semanas

### 2. God Method / Metodo Dios

Un metodo que contiene logica excesiva y demasiadas responsabilidades.

```php
// Switch numerico sin semantica - que hace la accion 147?
public function executeAction(int $action, array $requestData): mixed {
    switch ($action) {
        case 1: return $this->showCatalog($requestData);
        case 2: return $this->getOrders($requestData);
        // ... hasta case 9000
    }
}
```

**Impacto:**
- Dificultad extrema para añadir nuevas acciones
- El metodo se convierte en bottleneck para cualquier cambio

### 3. Inheritance Abuse / Abuso de Herencia

Uso de herencia para reutilizar codigo en lugar de para modelar relaciones "es-un".

```php
// BaseManager: DB, auth, templates, logging, config, translations, permissions
// OrderManager hereda TODO aunque solo necesita DB
class OrderManager extends BaseManager {
    public function __construct(int $storeId = 579314, array $config = []) {
        parent::__construct($config); // 8 cosas antes de empezar
        // 5 cosas mas...
    }
}
```

**Impacto:**
- Acoplamiento fuerte a la clase padre
- Imposible cambiar la implementacion base sin afectar a todos los hijos

### 4. Constructor Heavy / Constructor Sobrecargado

El constructor realiza demasiada inicializacion y tiene efectos secundarios.

```php
// Parent::__construct() hace:
// 1. DB connection    2. Config desde BD    3. Auth check
// 4. User loading     5. Permissions         6. Environment detection
// 7. Translations     8. Session setup
// + Child constructor:
// 9. Store loading   10. State init         11. Tax rates
// 12. Config merge   13. More state init
```

**Impacto:**
- Imposible instanciar la clase sin un contexto completo
- Testing requiere mockear toda la cadena de inicializacion

### 5. High Cognitive Load Architecture

La arquitectura requiere mantener demasiado contexto mental para entender el flujo.

Para entender `createOrder()` hay que rastrear:
1. El numero de accion en el switch
2. El metodo correspondiente
3. Las propiedades de estado que usa (`$this->currentOrder`, `$this->taxRates`)
4. Las dependencias heredadas (`$this->db`, `$this->config`)
5. Las queries SQL inline
6. Los efectos secundarios (crea shipment, envia email)
7. El HTML que renderiza

**Impacto:**
- Solo los desarrolladores mas veteranos pueden modificar codigo con confianza
- Bus factor = 1 o 2

### 6. Leaky Abstractions / Abstracciones Permeables

Las abstracciones filtran detalles de implementacion que deberian estar ocultos.

```php
// Expone internals via getters
public function getCurrentOrder(): \stdClass { return $this->currentOrder; }
public function getCustomerData(): \stdClass { return $this->customerData; }

// SQL expuesto directamente en metodos de dominio
$query = "SELECT * FROM orders WHERE id = $orderId AND store_id = $this->storeId";
```

**Impacto:**
- Cambiar la implementacion interna requiere cambiar todos los consumidores
- No se puede refactorizar sin romper compatibilidad

### 7. Hardcoded Infrastructure

Detalles de infraestructura (rutas, nombres de BD, IDs) hardcodeados en el codigo.

```php
$this->storeId = 579314;          // ID hardcodeado
$this->maxItems = 36;             // Magic number
case 9000:                        // Magic number para accion
if ($quantity >= 7) {             // Threshold hardcodeado
    $discount = $subtotal * 0.10; // Porcentaje hardcodeado
}
```

**Impacto:**
- Imposible desplegar en un entorno con estructura diferente
- Migracion de BD requiere buscar y reemplazar en todo el codigo

### 8. Infrastructure Leakage

Detalles de infraestructura (SQL, templates) se filtran en la logica de negocio.

```php
// Logica de negocio con SQL inline
public function createOrder(array $request): array {
    // ... validacion, calculo ...
    $query = "INSERT INTO orders (...) VALUES (...)"; // SQL mezclado
    $this->db->exec($query);
    // ... mas logica ...
}
```

**Impacto:**
- La logica de dominio depende directamente de la implementacion de BD
- Cambiar de base de datos requiere reescribir toda la clase

## Por que es un problema

- **Imposible de testear unitariamente**: No puedes testear un metodo sin inicializar toda la clase y la BD
- **Cualquier cambio tiene riesgo**: Modificar facturacion puede romper envios
- **Merge conflicts constantes**: Todo el equipo edita el mismo archivo
- **Onboarding de semanas**: Solo los veteranos entienden el flujo completo
- **Efectos secundarios impredecibles**: Una "consulta" puede modificar datos

## Solucion

La solucion implica **extraer bounded contexts** a servicios separados con responsabilidades claras:

### Estructura de la solucion

```
src/01-structure-and-architecture/solution/
├── Config.php                    # Configuracion externalizada (replaces #7)
├── Result.php                    # Return type consistente (replaces mixed returns)
├── OrderStatus.php               # Enum para estados (replaces magic ints)
├── ValueObjects/
│   ├── Money.php                 # Dinero con precision (replaces float)
│   ├── OrderId.php               # IDs tipados (replaces raw int)
│   ├── ProductId.php             # IDs tipados
│   └── CustomerId.php            # IDs tipados
├── OrderRepository.php           # Data access (replaces #8, #6)
├── OrderService.php              # Order lifecycle only (replaces #1, #5)
├── PricingService.php            # Tax, discount, coupons (replaces #2)
├── ShippingService.php           # Shipping logic
└── CustomerService.php           # Customer management
```

### Principios aplicados

| Principio | Antes | Despues |
|---|---|---|
| **SRP** | 1 clase, 15+ responsabilidades | 4 servicios, 1 responsabilidad cada uno |
| **DI** | Herencia + instanciacion interna | Inyeccion explicita por constructor |
| **Inmutabilidad** | `$this->currentOrder` mutable | Request/Response objects inmutables |
| **CQS** | Consultas con side effects | Separacion clara command/query |
| **Explicito** | Estado implicito compartido | Parametros explicitos |
| **Abstraccion** | SQL inline en dominio | Repository pattern |

### Comparativa

| Aspecto | Antes (God Class) | Despues (Servicios) |
|---|---|---|
| Lineas de codigo | 1100+ en un archivo | 80-150 por servicio |
| Responsabilidades | 15+ | 1 por servicio |
| Herencia | `extends BaseManager` (8 metodos heredados) | Composicion, sin herencia |
| Constructor | 13 inicializaciones | 3-4 dependencias explicitas |
| Testeabilidad | Requiere BD completa + contexto | Mockeable, aislable |
| Acoplamiento | Fuerte via estado compartido | Bajo via interfaces |
| Efectos secundarios | Ocultos y frecuentes | Explicitos y controlados |
| Return types | `mixed`, `array`, `string` (HTML) | `Result` consistente |
| IDs | `int` raw | `OrderId`, `ProductId` tipados |
| Dinero | `float` (precision issues) | `Money` value object |
| Estados | `int` magic (1, 2, 3...) | `OrderStatus` enum |
| Config | Hardcoded en constructor | `Config` object externalizado |
| SQL | Inline con interpolacion | Prepared statements en repository |

### Ejemplo de uso

```php
// ANTES: God Class
$manager = new OrderManager(579314, ['debug' => true]);
$result = $manager->executeAction(4, [
    'customer_name' => 'John',
    'product_id' => 1,
    'quantity' => 4,
]);
// $result['html'] contiene HTML renderizado
// $manager->getCurrentOrder() devuelve estado mutable

// DESPUES: Servicios
$config = Config::default(storeId: 1);
$repository = new OrderRepository($db, 1);
$pricing = new PricingService($config);
$shipping = new ShippingService($db, $config, 1);
$service = new OrderService($repository, $pricing, $shipping);

$result = $service->createOrder(new CreateOrderRequest(
    productId: new ProductId(1),
    customerName: 'John',
    quantity: 4,
));
// $result->data es OrderCreatedResponse con datos tipados
// Sin estado mutable, sin HTML, sin side effects ocultos
```

## Tests

```bash
# Tests del antipatron (demuestran los problemas)
vendor/bin/phpunit tests/01-structure-and-architecture/OrderManagerTest.php

# Tests de la solucion (demuestran el comportamiento correcto)
vendor/bin/phpunit tests/01-structure-and-architecture/OrderServiceTest.php
vendor/bin/phpunit tests/01-structure-and-architecture/PricingServiceTest.php
vendor/bin/phpunit tests/01-structure-and-architecture/MoneyTest.php

# Todos los tests del grupo
vendor/bin/phpunit tests/01-structure-and-architecture/
```

### Lo que demuestran los tests

**Antipattern tests:**
- No puedes testear un metodo en aislamiento
- El estado mutable causa dependencias entre tests
- Los efectos secundarios ocultos hacen los tests impredecibles
- `executeAction()` con numeros es confuso

**Solution tests:**
- Cada servicio es testeable independientemente
- `PricingService` no necesita base de datos
- `Money` value object evita problemas de precision
- No hay estado compartido entre operaciones
- Los return types son consistentes (`Result`)

## Antipatrones relacionados

- [02 - State & Coupling](../02-state-and-coupling/README.md)
- [03 - Data Modeling](../03-data-modeling/README.md)
- [04 - Security](../04-security/README.md)
- [05 - Error Handling](../05-error-handling/README.md)
- [07 - Communication](../07-communication/README.md)
- [08 - Naming](../08-naming/README.md)
