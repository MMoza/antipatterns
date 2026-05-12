# 02 - State & Coupling / Estado y Acoplamiento

## El problema

Cuando una clase mantiene **estado mutable compartido** entre sus metodos, se crean dependencias ocultas que hacen el codigo impredecible, imposible de testear en aislamiento, y propenso a bugs sutiles.

## Antipatrones identificados

| # | Antipatron | Como se manifiesta | Severidad |
|---|---|---|---|
| 9 | **Mutable Shared State** | `$this->context` se modifica desde todos los metodos | 🔴 Critico |
| 10 | **Action at Distance** | Cambiar un flag afecta metodos no relacionados | 🟠 Alto |
| 11 | **Temporal Coupling** | Los metodos deben llamarse en orden especifico | 🟠 Alto |
| 12 | **Implicit Workflow** | El flujo esta en comentarios, no en codigo | 🟡 Medio |
| 13 | **Service Locator** | Dependencias se resuelven via estado del objeto | 🟠 Alto |
| 14 | **Hidden Side Effects** | Metodos de consulta que modifican datos | 🟠 Alto |
| 15 | **Recursive Instantiation** | Servicios que instancian otros servicios en cadena | 🟡 Medio |
| 16 | **Environment Scattered** | Logica de prod/dev/test esparcida por todo | 🟡 Medio |

### 9. Mutable Shared State

```php
// Todo se lee y escribe desde cualquier metodo
private array $context;
private ?object $currentReservation;
private array $prices;
private ?object $customer;

// Cualquier metodo puede modificar el estado
$this->context['total'] = $newTotal;
$this->currentReservation = (object) $row;
```

### 10. Action at Distance

```php
// loadReservation() setea un flag que afecta a calculateShipping()
if ($row['status'] >= 3) {
    $this->context['shipping_locked'] = true; // <-- afecta a otro metodo
}

// calculateShipping() cambia su comportamiento silenciosamente
if (!empty($this->context['shipping_locked'])) {
    return ['cost' => 0, 'method' => 'already_shipped'];
}
```

### 11. Temporal Coupling

```php
// Estos metodos DEBEN llamarse en este orden exacto:
$this->context->loadReservation(1);    // PASO 1: obligatorio
$this->context->calculatePrices();     // PASO 2: requiere paso 1
$this->context->calculateShipping();   // PASO 3: requiere paso 2
$this->context->applyDiscounts('X');   // PASO 4: requiere paso 2
$this->context->processPayment('cc');  // PASO 5: requiere paso 2, 3, 4
$this->context->sendConfirmation();    // PASO 6: requiere paso 5

// Si te saltas uno: Exception("Must call X() first")
```

### 12. Implicit Workflow

```php
// El flujo esta documentado en comentarios, no en codigo:
// PASO 1: loadReservation()
// PASO 2: calculatePrices()
// PASO 3: calculateShipping()
// PASO 4: applyDiscounts()
// PASO 5: processPayment()
// PASO 6: sendConfirmation()
// Si te saltas un paso o cambias el orden, las cosas se rompen
```

### 13. Service Locator by Object State

```php
// Las dependencias se crean a partir del estado interno
private function getPricingService(): object {
    return new PricingService([
        'store_id' => $this->context['store_id'], // <-- del estado interno
        'currency' => $this->context['currency'], // <-- del estado interno
    ]);
}
```

### 14. Hidden Side Effects

```php
// Parece una consulta pero modifica la BD
public function getAvailableDates(string $start, string $end): array {
    $dates = $this->db->query("SELECT ...")->fetchAll();
    
    // Side effect oculto: incrementa view_count
    foreach ($dates as $date) {
        $this->db->exec("UPDATE availability SET view_count = view_count + 1 ...");
    }
    
    // Otro side effect: crea alertas automaticamente
    if ($availableCount < 5) {
        $this->db->exec("INSERT INTO availability_alerts ...");
    }
    
    return $dates;
}
```

### 15. Recursive Service Instantiation

```php
// Cada servicio crea su propia cadena de dependencias:
ReservationContext
  -> PricingService
       -> TaxService
            -> TaxRateLoader -> DB
       -> DiscountService -> DB
  -> ShippingService
       -> CarrierService -> DB
       -> ZoneService -> DB
  -> PaymentService
       -> PaymentGateway
  -> EmailService
       -> TemplateEngine -> DB
```

### 16. Environment Logic Scattered

```php
// Logica de entorno esparcida en 6+ metodos diferentes
private function detectEnvironment(): string { ... }

public function calculateShipping(): array {
    if ($this->environment === 'production') { ... }
    else { /* dev/test usa costos fijos */ }
}

private function determineShippingMethod(): string {
    if ($this->environment === 'production') { ... }
    else { return 'standard'; }
}

private function determineEstimatedDays(): int {
    if ($this->environment === 'production') { ... }
    else { return 1; }
}
```

## Por que es un problema

- **Tests impredecibles**: El orden de ejecucion afecta los resultados
- **Bugs silenciosos**: Un metodo modifica estado que afecta a otro metodo no relacionado
- **Imposible paralelizar**: El estado compartido impide ejecucion concurrente
- **Acoplamiento temporal**: No puedes llamar a un metodo sin llamar a otros antes
- **Efectos secundarios ocultos**: Los "getters" modifican la base de datos
- **Cadenas de dependencias ocultas**: Cada servicio crea 3+ servicios internos

## Solucion

La solucion implica **eliminar el estado compartido** y hacer todo **explicito**:

### Estructura de la solucion

```
src/02-state-and-coupling/solution/
├── Reservation.php           # Value object inmutable (replaces #9)
├── ReservationStatus.php     # Enum para estados
├── PaymentStatus.php         # Enum para pagos
├── ReservationFactory.php    # Creacion explicita (replaces #11, #13)
├── StatusTransition.php      # Maquina de estados explicita (replaces #10, #12)
├── ShippingCalculator.php    # Funcion pura de calculo (replaces #14, #16)
├── PricingCalculator.php     # Funcion pura de precios
├── Coupon.php                # Value object para cupones
└── BookingWorkflow.php       # Workflow explicito paso a paso (replaces #12)
```

### Principios aplicados

| Principio | Antes | Despues |
|---|---|---|
| **Inmutabilidad** | `$this->context` mutable | `Reservation` readonly |
| **Explicito** | Orden implicito de llamadas | Workflow con pasos claros |
| **Pure Functions** | Getters con side effects | Calculadores sin efectos |
| **State Machine** | Transiciones implicitas | `StatusTransition` validado |
| **DI** | Service locator interno | Inyeccion por constructor |
| **Environment** | Logica esparcida | Config inyectada |

### Comparativa

| Aspecto | Antes (Context) | Despues (Workflow) |
|---|---|---|
| Estado | Mutable compartido | Inmutable por operacion |
| Orden de llamadas | Implicito (comentarios) | Explicito (workflow) |
| Side effects | Ocultos en getters | Separados de consultas |
| Dependencias | Service locator interno | Inyeccion por constructor |
| Transiciones de estado | Implicitas | Validadas por StatusTransition |
| Environment | Logica esparcida | Config inyectada |
| Testeabilidad | Requiere contexto completo | Cada servicio aislable |
| Resultado | `array` inconsistente | `WorkflowResult` tipado |

### Ejemplo de uso

```php
// ANTES: Context con estado mutable y orden implicito
$context = new ReservationContext(['store_id' => 1]);
$context->loadReservation(1);           // PASO 1: obligatorio
$context->calculatePrices();            // PASO 2: requiere paso 1
$context->calculateShipping();          // PASO 3: requiere paso 2
$context->applyDiscounts('SUMMER20');   // PASO 4: requiere paso 2
$context->processPayment('credit_card');// PASO 5: requiere todo lo anterior
// Estado compartido entre todos los pasos

// DESPUES: Workflow explicito sin estado compartido
$workflow = new BookingWorkflow($factory, $shipping, $pricing, $transitions, $db);
$result = $workflow->confirmReservation(1);
// Cada operacion es independiente, sin estado compartido
```

## Tests

```bash
# Tests del antipatron (demuestran los problemas)
vendor/bin/phpunit tests/02-state-and-coupling/ReservationContextTest.php

# Tests de la solucion (demuestran el comportamiento correcto)
vendor/bin/phpunit tests/02-state-and-coupling/BookingWorkflowTest.php

# Todos los tests del grupo
vendor/bin/phpunit tests/02-state-and-coupling/
```

### Lo que demuestran los tests

**Antipattern tests (11 tests):**
- El estado mutable se expone y puede corromperse
- Los metodos requieren un orden especifico de llamadas
- Las acciones a distancia cambian comportamiento silenciosamente
- Los getters tienen side effects ocultos en la BD
- El estado se filtra entre operaciones
- No se puede aislar una sola operacion para testear

**Solution tests (17 tests):**
- Cada operacion es independiente
- Las transiciones de estado estan validadas
- Los objetos son inmutables
- Los calculadores son funciones puras
- No hay side effects en operaciones de lectura
- Cada paso se puede testear aisladamente

## Antipatrones relacionados

- [01 - God Class](../01-structure-and-architecture/README.md)
- [03 - Data Modeling](../03-data-modeling/README.md)
- [05 - Error Handling](../05-error-handling/README.md)
