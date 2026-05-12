# AGENTS.md - Guía de Desarrollo

## Dominio

**Todos los antipatrones usan el dominio E-Commerce como ejemplo.**

Nada de hoteles, reservas, habitaciones, check-in, limpieza, planning.

### Entidades del dominio

| Entidad | Descripción |
|---|---|
| **Product** | Productos del catálogo (nombre, precio, stock, categoría) |
| **Cart** | Carrito de compra de un usuario |
| **Order** | Pedido realizado por un cliente |
| **Customer** | Cliente/usuario de la tienda |
| **Coupon** | Cupones de descuento |
| **Shipment** | Envío/paquete asociado a un pedido |
| **Return** | Devolución de productos |
| **Review** | Valoraciones de productos |
| **Category** | Categorías de productos |

### Operaciones típicas del dominio

- Navegar catálogo, buscar productos, filtrar por categoría
- Añadir/quitar productos del carrito
- Checkout: crear pedido, calcular envío, aplicar cupones, impuestos
- Gestionar pedidos: cambiar estado, cancelar, rastrear envío
- Devoluciones: solicitar, procesar reembolso
- Gestión de clientes: direcciones, historial de pedidos
- Inventario: comprobar stock, alertas de reposición
- Analytics: ventas, productos más vendidos, estadísticas

## Estructura del Proyecto

```
antipatterns/
├── AGENTS.md                    # Este archivo
├── ANTIPATRONES.md              # Documentación de los 77 antipatrones
├── PROJECT-STRUCTURE.md         # Estructura de directorios
├── README.md                    # Descripción del proyecto
├── composer.json
├── phpunit.xml
├── src/
│   ├── Common/
│   │   └── Database.php         # Wrapper PDO (SQLite en memoria)
│   ├── StructureAndArchitecture/
│   │   ├── antipattern/
│   │   │   └── OrderManager.php
│   │   ├── solution/            # Código refactorizado (vacío por ahora)
│   │   └── README.md
│   └── ... (más grupos)
├── tests/
│   └── StructureAndArchitecture/
│       └── OrderManagerTest.php
└── examples/
    └── 01-structure-and-architecture.php
```

## Convenciones de Código

### Namespaces (PSR-4)

```
AntiPatterns\{Group}\antipattern\{ClassName}
AntiPatterns\{Group}\solution\{ClassName}
Tests\{Group}\{ClassName}Test
```

Ejemplo: `AntiPatterns\StructureAndArchitecture\antipattern\OrderManager`

### Idioma

- **Código**: inglés (nombres de clases, métodos, variables)
- **Comentarios**: español
- **Documentación**: español (READMEs, AGENTS.md, ANTIPATRONES.md)
- **Strings de UI en ejemplos**: español (para reflejar el antipatron de mixed language)

### PHP

- PHP 8.2+
- `declare(strict_types=1);` en todos los archivos
- Sin framework, PHP puro
- PDO con SQLite en memoria para tests y demos

### Estilo

- Namespaces en minúsculas: `antipattern`, `solution`
- Clases: PascalCase
- Métodos: camelCase
- Variables: camelCase
- En el **antipattern**: intencionalmente se pueden incluir nombres spanglish como parte del antipatron

## Flujo de Trabajo para Añadir un Nuevo Antipatrón

### 1. Crear estructura de directorios

```
src/{GroupName}/antipattern/
src/{GroupName}/solution/
tests/{GroupName}/
```

### 2. Escribir el código antipatrón

- Debe ser **genérico** e inspirado en la realidad, NO copiar código real
- Debe demostrar los antipatrones documentados en ANTIPATRONES.md
- Usar el dominio E-Commerce
- Incluir múltiples antipatrones relacionados en cada grupo

### 3. Escribir tests

- Tests que demuestren **por qué es un problema** el antipatrón
- Deben pasar (el código funciona, pero es difícil de mantener/testear)
- Usar SQLite en memoria via `AntiPatterns\Common\Database`
- Resetear BD en `setUp()` y `tearDown()`

### 4. Crear README del grupo

- Descripción del problema
- Tabla de antipatrones identificados
- Por qué es un problema
- Preview de la solución
- Comparativa antes/después

### 5. Crear script demo en `examples/`

- Ejecutable por CLI: `php examples/NN-group-name.php`
- Muestra el antipatrón en acción
- Imprime explicaciones de lo que se está demostrando

### 6. Ejecutar tests y verificar

```bash
vendor/bin/phpunit tests/{GroupName}/
```

## Grupo 1: God Class (ya implementado)

### Antipatrones cubiertos

| # | Antipatrón | Cómo se manifiesta |
|---|---|---|
| 1 | God Class | `OrderManager` con 15+ responsabilidades |
| 2 | God Method | `executeAction()` con switch numérico |
| 3 | Constructor Heavy | Constructor que hace 10 cosas |
| 4 | Mutable Shared State | `$this->currentOrder`, `$this->cart` |
| 5 | High Cognitive Load | Rastrear 7+ cosas para entender un flujo |

### Archivos

- `src/StructureAndArchitecture/antipattern/OrderManager.php` - La clase Dios
- `tests/StructureAndArchitecture/OrderManagerTest.php` - 10 tests demostrando problemas
- `src/StructureAndArchitecture/README.md` - Documentación
- `examples/01-structure-and-architecture.php` - Demo CLI

## Base de Datos para Tests

`AntiPatterns\Common\Database` proporciona un singleton PDO con SQLite en memoria.

Cada test debe crear sus propias tablas en `setUp()`:

```php
protected function setUp(): void
{
    Database::reset();
    $db = Database::getInstance();
    $db->exec("CREATE TABLE products (...)");
    $db->exec("CREATE TABLE orders (...)");
    // etc.
}

protected function tearDown(): void
{
    Database::reset();
}
```

## Comandos Útiles

```bash
# Instalar dependencias
composer install

# Ejecutar todos los tests
vendor/bin/phpunit

# Ejecutar tests de un grupo
vendor/bin/phpunit tests/StructureAndArchitecture/

# Ejecutar un test específico
vendor/bin/phpunit tests/StructureAndArchitecture/OrderManagerTest.php --filter=testMethodName

# Demo CLI
php examples/01-structure-and-architecture.php

# Regenerar autoload
composer dump-autoload
```

## Reglas

1. **NO copiar código real** del sistema original. Todo debe ser código inventado inspirado en la realidad.
2. **NO publicar** ninguna lógica de negocio real del sistema.
3. **SIEMPRE** usar el dominio E-Commerce.
4. **SIEMPRE** mantener los tests pasando.
5. **SIEMPRE** usar SQLite en memoria para tests (no requiere MySQL).
6. **NO** añadir dependencias externas más allá de PHPUnit.
7. **NO** usar frameworks. PHP puro.
8. Los antipatrones deben ser **evidentes** pero el código debe **funcionar**.

## Mapa de Grupos Planificados

| Grupo | Directorio | Antipatrones | Estado |
|---|---|---|---|
| 1. God Class | `StructureAndArchitecture/` | 1, 2, 3, 4, 5 | Implementado |
| 2. State & Coupling | `StateAndCoupling/` | 9-16 | Pendiente |
| 3. Data Modeling | `DataModeling/` | 17-23 | Pendiente |
| 4. Security | `Security/` | 24-26 | Pendiente |
| 5. Error Handling | `ErrorHandling/` | 27-29 | Pendiente |
| 6. Database Design | `DatabaseDesign/` | 39-65 | Pendiente |
| 7. Communication | `Communication/` | 35-38 | Pendiente |
| 8. Naming | `Naming/` | 31, 66-77 | Pendiente |
