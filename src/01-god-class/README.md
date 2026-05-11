# 01 - God Class / Clase Dios

## El problema

Una clase que hace **demasiadas cosas**, violando el Principio de Responsabilidad Unica (SRP). En el sistema real, una sola clase PHP gestionaba: catalogo de productos, carrito de compra, pedidos, calculo de precios, gestion de clientes, inventario, envios, devoluciones, notificaciones, analytics, y renderizado HTML.

## Antipatron

```php
// src/GodClass/antipattern/OrderManager.php
```

### Problemas identificados

| Antipatron | Como se manifiesta |
|---|---|
| **God Class** | Una clase con 15+ responsabilidades diferentes |
| **God Method** | `executeAction()` con switch de 15+ casos numericos |
| **Constructor Heavy** | Constructor que carga config, conecta BD, inicializa estado |
| **Mutable Shared State** | `$this->currentOrder`, `$this->customerData` leidos/escritos por todos los metodos |
| **Hidden Side Effects** | `getOrders()` crea registros de devoluciones mientras "consulta" |
| **Temporal Coupling** | `cancelOrder()` funciona diferente si se llamo a otro metodo antes |
| **Presentation Mixed with Domain** | Metodos que devuelven HTML inline mezclado con datos |
| **SQL Injection** | Queries con interpolacion directa de variables |
| **Silent Catch** | Bloques `try/catch` vacios que ocultan errores |
| **DRY Violations** | `getOrders()` y `getOrdersMonthlyView()` con logica duplicada |
| **Primitive Obsession** | IDs como `int`, fechas como `string`, estados como `int` |
| **Array-Based Domain Modeling** | `$result['orders'][$id]['data']->customer` |
| **Magic Numbers** | `36` items max, `0.10` descuento, `9000` accion especial |
| **Boolean Flags** | `$isInvoiceMode`, `$showShipping`, `$showAnalytics` |

### Por que es un problema

- **Imposible de testear unitariamente**: No puedes testear un metodo sin inicializar toda la clase y la BD
- **Cualquier cambio tiene riesgo**: Modificar facturacion puede romper envios
- **Merge conflicts constantes**: Todo el equipo edita el mismo archivo
- **Onboarding de semanas**: Solo los veteranos entienden el flujo completo
- **Efectos secundarios impredecibles**: Una "consulta" puede modificar datos

## Solucion

La solucion implica **extraer bounded contexts** a servicios separados:

```php
// src/GodClass/solution/OrderService.php
// src/GodClass/solution/CartService.php
// src/GodClass/solution/PricingService.php
```

### Principios aplicados

1. **Single Responsibility Principle**: Cada servicio tiene una unica responsabilidad
2. **Dependency Injection**: Las dependencias se inyectan, no se instancian internamente
3. **Inmutabilidad**: Los datos se pasan como parametros, no se comparten via estado
4. **Command-Query Separation**: Los metodos de consulta no modifican estado
5. **Explicit over Implicit**: Los flujos son explicitos, no dependen de orden de llamadas

### Comparativa

| Aspecto | Antes (God Class) | Despues (Servicios) |
|---|---|---|
| Lineas de codigo | 500+ en un archivo | 80-120 por servicio |
| Responsabilidades | 15+ | 1 por servicio |
| Testeabilidad | Requiere BD completa | Mockeable con interfaces |
| Acoplamiento | Fuerte via estado compartido | Bajo via inyeccion |
| Efectos secundarios | Ocultos y frecuentes | Explicitos y controlados |
| Nuevas funcionalidades | Riesgo de romper todo | Aislado en su servicio |

## Tests

```bash
# Ejecutar tests del antipatron
vendor/bin/phpunit tests/GodClass/OrderManagerTest.php
```

Los tests demuestran:
- No puedes testear un metodo en aislamiento
- El estado mutable causa dependencias entre tests
- Los efectos secundarios ocultos hacen los tests impredecibles

## Antipatrones relacionados

- [02 - State & Coupling](../StateAndCoupling/README.md)
- [03 - Data Modeling](../DataModeling/README.md)
- [07 - Communication](../Communication/README.md)
