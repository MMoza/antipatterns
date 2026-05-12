# Estructura del Proyecto: Anti-Patterns Repository

## Visión

Catálogo de antipatrones de diseño y cómo estrangularlos, con ejemplos genéricos en PHP simple. Cada grupo temático contiene: código antipatrón, solución refactorizada, documentación y tests comparativos.

---

## Stack

- **PHP 8.2+** (sin framework)
- **Composer** (autoload PSR-4)
- **PHPUnit 11** (tests)
- **PDO** (ejemplos de BD)

---

## Estructura de Directorios

```
anti-patterns-repo/
│
├── README.md
├── composer.json
├── phpunit.xml
├── PROJECT-STRUCTURE.md               # Este archivo
│
├── docs/
│   └── ANTI-PATTERNS.md               # Los 77 antipatrones documentados
│
├── src/
│   ├── Common/                        # Infraestructura mínima
│   │   ├── Database.php               # Wrapper PDO simple
│   │   └── Config.php
│   │
│   ├── 01-structure-and-architecture/
│   │   ├── antipattern/
│   │   │   └── BookingManager.php
│   │   ├── solution/
│   │   │   ├── BookingService.php
│   │   │   ├── AvailabilityService.php
│   │   │   └── BillingService.php
│   │   └── README.md
│   │
│   ├── 02-state-and-coupling/
│   │   ├── antipattern/
│   │   │   └── ReservationContext.php
│   │   ├── solution/
│   │   │   ├── Reservation.php
│   │   │   ├── ReservationFactory.php
│   │   │   └── BookingWorkflow.php
│   │   └── README.md
│   │
│   ├── 03-data-modeling/
│   │   ├── antipattern/
│   │   │   └── BookingRepository.php
│   │   ├── solution/
│   │   │   ├── ValueObjects/
│   │   │   │   ├── BookingId.php
│   │   │   │   ├── DateRange.php
│   │   │   │   ├── Money.php
│   │   │   │   └── GuestCount.php
│   │   │   ├── Dto/
│   │   │   │   └── BookingDetail.php
│   │   │   └── BookingRepository.php
│   │   └── README.md
│   │
│   ├── 04-security/
│   │   ├── antipattern/
│   │   │   └── UserRepository.php
│   │   ├── solution/
│   │   │   ├── UserRepository.php
│   │   │   └── PasswordHasher.php
│   │   └── README.md
│   │
│   ├── 05-error-handling/
│   │   ├── antipattern/
│   │   │   └── PaymentProcessor.php
│   │   ├── solution/
│   │   │   ├── PaymentProcessor.php
│   │   │   └── Result.php
│   │   └── README.md
│   │
│   ├── 06-database-design/
│   │   ├── antipattern/
│   │   │   ├── schema.sql
│   │   │   └── OrderRepository.php
│   │   ├── solution/
│   │   │   ├── schema.sql
│   │   │   └── OrderRepository.php
│   │   └── README.md
│   │
│   ├── 07-communication/
│   │   ├── antipattern/
│   │   │   ├── FrontController.php
│   │   │   └── api.js
│   │   ├── solution/
│   │   │   ├── FrontController.php
│   │   │   └── api.js
│   │   └── README.md
│   │
│   └── 08-naming/
│       ├── antipattern/
│       │   └── MixedCodebase.php
│       ├── solution/
│       │   └── CleanCodebase.php
│       └── README.md
│
├── tests/
│   ├── 01-structure-and-architecture/
│   │   ├── BookingManagerTest.php
│   │   └── BookingServiceTest.php
│   │
│   ├── 02-state-and-coupling/
│   │   ├── ReservationContextTest.php
│   │   └── BookingWorkflowTest.php
│   │
│   ├── 03-data-modeling/
│   │   ├── BookingRepositoryTest.php
│   │   └── ValueObjects/
│   │       ├── BookingIdTest.php
│   │       ├── MoneyTest.php
│   │       └── DateRangeTest.php
│   │
│   ├── 04-security/
│   │   └── UserRepositoryTest.php
│   │
│   ├── 05-error-handling/
│   │   └── PaymentProcessorTest.php
│   │
│   ├── 06-database-design/
│   │   └── OrderRepositoryTest.php
│   │
│   ├── 07-communication/
│   │   └── FrontControllerTest.php
│   │
│   └── 08-naming/
│       └── NamingConsistencyTest.php
│
└── examples/
    ├── 01-structure-and-architecture.php
    ├── 02-state-and-coupling.php
    ├── 03-data-modeling.php
    ├── 04-security.php
    ├── 05-error-handling.php
    ├── 06-database-design.php
    ├── 07-communication.php
    └── 08-naming.php
```

---

## Mapa: Grupos → Antipatrones Cubiertos

| Grupo | Directorio | Antipatrones |
|---|---|---|
| **1. God Class** | `01-structure-and-architecture/` | 1, 2, 3, 4, 5 |
| **2. State & Coupling** | `02-state-and-coupling/` | 9, 10, 11, 12, 13, 14, 15, 16 |
| **3. Data Modeling** | `03-data-modeling/` | 17, 18, 19, 20, 21, 22, 23 |
| **4. Security** | `04-security/` | 24, 25, 26 |
| **5. Error Handling** | `05-error-handling/` | 27, 28, 29 |
| **6. Database Design** | `06-database-design/` | 39-65 |
| **7. Communication** | `07-communication/` | 35, 36, 37, 38 |
| **8. Naming** | `08-naming/` | 31, 66-77 |

---

## Estructura de Cada README Interno

```markdown
# NN - Nombre del Grupo

## El problema

Descripción breve del antipatrón y por qué aparece.

## Antipatrón

```php
// src/NN-grupo/antipattern/Ejemplo.php
```

### Por qué es un problema

- Punto 1
- Punto 2
- Punto 3

## Solución

```php
// src/NN-grupo/solution/Ejemplo.php
```

### Comparativa

| Aspecto | Antes | Después |
|---|---|---|
| Métrica 1 | Valor malo | Valor bueno |
| Métrica 2 | Valor malo | Valor bueno |

## Tests

```bash
# Antes
phpunit tests/NN-grupo/AntipatternTest.php

# Después
phpunit tests/NN-grupo/SolutionTest.php
```

## Antipatrones relacionados

- [Grupo X](../XX-grupo/README.md)
```

---

## Estructura de Cada Example Script

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

echo "=== Nombre del Grupo Demo ===\n\n";

echo "1. ANTE: Descripción del antipatrón\n";
// Código que demuestra el problema
echo "\n";

echo "2. DESPUÉS: Descripción de la solución\n";
// Código que demuestra la solución
echo "\n";
```

---

## composer.json

```json
{
    "name": "tu-usuario/anti-patterns",
    "description": "Catálogo de antipatrones de diseño y cómo estrangularlos",
    "type": "project",
    "require": {
        "php": ">=8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "AntiPatterns\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

---

## phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         displayDetailsOnTestsThatTriggerWarnings="true">
    <testsuites>
        <testsuite name="All">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## Flujo de Trabajo para Añadir un Nuevo Ejemplo

1. Crear directorio `src/NN-nombre/` con `antipattern/`, `solution/` y `README.md`
2. Escribir el código antipatrón en `antipattern/`
3. Escribir la solución refactorizada en `solution/`
4. Crear tests en `tests/NN-nombre/` que demuestren ambos enfoques
5. Crear script demo en `examples/NN-nombre.php`
6. Documentar en el README del grupo
7. Actualizar el README principal si es necesario

---

## Convenciones

- **Namespaces:** `AntiPatterns\GodClass\antipattern\`, `AntiPatterns\GodClass\solution\`
- **Tests:** `Tests\GodClass\BookingManagerTest.php`
- **Nombres de clase:** Descriptivos y en inglés (el dominio es genérico)
- **READMEs:** En español (documentación del repo)
- **Código:** En inglés (estándar de la industria)
- **Sin dependencias externas** más allá de PHPUnit
