# Antipatrones de Diseño en Código Legacy

> **Nota:** Este documento recopila antipatrones observados en el analisis de multiples codebases PHP legacy del sector e-commerce. Los ejemplos de codigo son genericos e inventados, inspirados en patrones reales de la industria. Ningun codigo pertenece a un sistema real.
>
> El objetivo es identificar, documentar y aprender a **estrangular** cada antipatron con ejemplos illustrativos.

---

## Índice por Categorías

### [A. Estructura y Arquitectura](#a-estructura-y-arquitectura)
1. [God Class / Clase Dios](#1-god-class--clase-dios)
2. [God Method / Método Dios](#2-god-method--método-dios)
3. [Inheritance Abuse / Abuso de Herencia](#3-inheritance-abuse--abuso-de-herencia)
4. [Constructor Heavy / Constructor Sobrecargado](#4-constructor-heavy--constructor-sobrecargado)
5. [High Cognitive Load Architecture](#5-high-cognitive-load-architecture)
6. [Leaky Abstractions / Abstracciones Permeables](#6-leaky-abstractions--abstracciones-permeables)
7. [Hardcoded Infrastructure](#7-hardcoded-infrastructure)
8. [Infrastructure Leakage](#8-infrastructure-leakage)

### [B. Estado y Acoplamiento](#b-estado-y-acoplamiento)
9. [Mutable Shared State / Estado Compartido Mutable](#9-mutable-shared-state--estado-compartido-mutable)
10. [Action at Distance / Acción a Distancia](#10-action-at-distance--acción-a-distancia)
11. [Temporal Coupling / Acoplamiento Temporal](#11-temporal-coupling--acoplamiento-temporal)
12. [Implicit Workflow Coupling](#12-implicit-workflow-coupling)
13. [Service Locator by Object State](#13-service-locator-by-object-state)
14. [Hidden Side Effects / Efectos Secundarios Ocultos](#14-hidden-side-effects--efectos-secundarios-ocultos)
15. [Recursive Service Instantiation](#15-recursive-service-instantiation)
16. [Environment Logic Scattered](#16-environment-logic-scattered)

### [C. Modelado de Datos (PHP)](#c-modelado-de-datos-php)
17. [Primitive Obsession / Obsesión por Primitivos](#17-primitive-obsession--obsesión-por-primitivos)
18. [Array-Based Domain Modeling](#18-array-based-domain-modeling)
19. [stdClass como Modelo Universal](#19-stdclass-como-modelo-universal)
20. [Anemic Domain Model / Modelo de Dominio Anémico](#20-anemic-domain-model--modelo-de-dominio-anémico)
21. [Stringly Typed Architecture](#21-stringly-typed-architecture)
22. [Boolean / Integer Flags](#22-boolean--integer-flags)
23. [Data Clumps](#23-data-clumps)

### [D. Seguridad](#d-seguridad)
24. [SQL Injection](#24-sql-injection)
25. [Security Anti-Patterns](#25-security-anti-patterns)
26. [SSL Verification Disabled](#26-ssl-verification-disabled)

### [E. Manejo de Errores](#e-manejo-de-errores)
27. [Silent Catch / Captura Silenciosa](#27-silent-catch--captura-silenciosa)
28. [Exception Handling as Business Logic](#28-exception-handling-as-business-logic)
29. [Fear-Driven Logging](#29-fear-driven-logging)

### [F. Código y Mantenibilidad](#f-código-y-mantenibilidad)
30. [Dead Code / Fossil Code](#30-dead-code--fossil-code)
31. [Mixed Language Naming](#31-mixed-language-naming)
32. [Magic Numbers / Números Mágicos](#32-magic-numbers--números-mágicos)
33. [DRY Violations / Violaciones DRY](#33-dry-violations--violaciones-dry)
34. [Long Parameter List](#34-long-parameter-list)

### [G. Presentación y Comunicación](#g-presentación-y-comunicación)
35. [Presentation Mixed with Domain](#35-presentation-mixed-with-domain)
36. [Output Format Confusion](#36-output-format-confusion)
37. [Action-Based Routing (Numerical Actions)](#37-action-based-routing-numerical-actions)
38. [Server-Rendered HTML over AJAX](#38-server-rendered-html-over-ajax)

### [H. Database Design](#h-database-design)
39. [Magic Values as Type Discriminators](#39-magic-values-as-type-discriminators)
40. [Cross-Schema Queries Without Abstraction](#40-cross-schema-queries-without-abstraction)
41. [Duplicate Table Pairs (Operator vs Particular)](#41-duplicate-table-pairs-operator-vs-particular)
42. [Inconsistent Column Naming Across Schemas](#42-inconsistent-column-naming-across-schemas)
43. [Redundant / Duplicated Columns](#43-redundant--duplicated-columns)
44. [JSON / Serialized Data in Columns](#44-json--serialized-data-in-columns)
45. [N+1 Query Patterns in Loops](#45-n1-query-patterns-in-loops)
46. [SELECT * Anti-Pattern](#46-select--anti-pattern)
47. [Missing Indexes Evidence](#47-missing-indexes-evidence)
48. [UNION Without ALL](#48-union-without-all)
49. [Foreign Keys Disabled for Operations](#49-foreign-keys-disabled-for-operations)
50. [Soft Deletes Without Consistency](#50-soft-deletes-without-consistency)
51. [Hardcoded Business Logic in SQL](#51-hardcoded-business-logic-in-sql)
52. [Polymorphic Discriminator Columns](#52-polymorphic-discriminator-columns)
53. [LIKE with Leading Wildcards](#53-like-with-leading-wildcards)
54. [Wide Table / Tabla Ancha](#54-wide-table--tabla-ancha)
55. [Repeating Groups (1NF Violation)](#55-repeating-groups-1nf-violation)
56. [DOUBLE for Money](#56-double-for-money)
57. [VARCHAR for Numeric Values](#57-varchar-for-numeric-values)
58. [Zero Date Default](#58-zero-date-default)
59. [Charset Obsoleto (latin1)](#59-charset-obsoleto-latin1)
60. [Over-Indexing](#60-over-indexing)
61. [Missing Foreign Keys](#61-missing-foreign-keys)
62. [Trigger-Based Denormalization](#62-trigger-based-denormalization)
63. [Catch-All Varchar Column](#63-catch-all-varchar-column)
64. [Composite Primary Key Excesivo](#64-composite-primary-key-excesivo)
65. [Truncated Column Comments](#65-truncated-column-comments)

### [I. Naming](#i-naming)
66. [Mixed Language Naming (Spanglish)](#66-mixed-language-naming-spanglish)
67. [Inconsistent Prefix Conventions](#67-inconsistent-prefix-conventions)
68. [Ambiguous Abbreviations](#68-ambiguous-abbreviations)
69. [Inconsistent Verb Conventions](#69-inconsistent-verb-conventions)
70. [Hungarian Notation Variants](#70-hungarian-notation-variants)
71. [Schema-Level Naming Inconsistency](#71-schema-level-naming-inconsistency)
72. [Table Naming: Singular vs Plural](#72-table-naming-singular-vs-plural)
73. [Cryptic Variable Names](#73-cryptic-variable-names)
74. [Action Numbers Instead of Names](#74-action-numbers-instead-of-names)
75. [Cryptic Column Abbreviations](#75-cryptic-column-abbreviations)
76. [Numeric Suffix Columns](#76-numeric-suffix-columns)
77. [Typo in Comments](#77-typo-in-comments)

### [J. Integraciones y APIs](#j-integraciones-y-apis)
78. [Retry Storm / Reintentos Descontrolados](#78-retry-storm--reintentos-descontrolados)
79. [No Idempotency in External Operations](#79-no-idempotency-in-external-operations)
80. [API Response Shape Coupling](#80-api-response-shape-coupling)
81. [Vendor SDK Domain Leakage](#81-vendor-sdk-domain-leakage)

### [K. Concurrencia y Consistencia](#k-concurrencia-y-consistencia)
82. [Check-Then-Act Race Condition](#82-check-then-act-race-condition)
83. [Transaction Script Without Transactions](#83-transaction-script-without-transactions)
84. [Distributed Transaction Illusion](#84-distributed-transaction-illusion)

### [L. Performance y Escalabilidad](#l-performance-y-escalabilidad)
85. [Cache Aside Chaos](#85-cache-aside-chaos)
86. [Premature Micro-Optimization](#86-premature-micro-optimization)
87. [Batch Processing via Memory Explosion](#87-batch-processing-via-memory-explosion)

### [M. Observabilidad y Operaci�n](#m-observabilidad-y-operacion)
88. [Log-and-Pray](#88-log-and-pray)
89. [Monitoring Blind Spots](#89-monitoring-blind-spots)
90. [Configuration by Database](#90-configuration-by-database)

### [N. Framework Casero Legacy](#n-framework-casero-legacy)
91. [Homemade Framework Syndrome](#91-homemade-framework-syndrome)
92. [Copy-Paste Inheritance Framework](#92-copy-paste-inheritance-framework)

### [O. Deployment y Entornos](#o-deployment-y-entornos)
93. [Snowflake Server](#93-snowflake-server)
94. [Environment Drift](#94-environment-drift)
95. [Feature Flags by Commenting Code](#95-feature-flags-by-commenting-code)

### [P. Testing Legacy](#p-testing-legacy)
96. [Integration Test as Unit Test](#96-integration-test-as-unit-test)
97. [Mock Everything Syndrome](#97-mock-everything-syndrome)
98. [Golden Master Dependency](#98-golden-master-dependency)

### [Q. Dominio y Negocio](#q-dominio-y-negocio)
99. [Business Rules by Convention](#99-business-rules-by-convention)
100. [Zombie Features](#100-zombie-features)

### [R. Legacy Socio-Technical Patterns](#r-legacy-socio-technical-patterns)
101. [Fear-Driven Development](#101-fear-driven-development)
102. [Knowledge Silos](#102-knowledge-silos)
103. [Bus Factor One](#103-bus-factor-one)
104. [Tribal Knowledge Architecture](#104-tribal-knowledge-architecture)
105. [Ticket-Driven Architecture](#105-ticket-driven-architecture)
106. [Copy-Paste Onboarding](#106-copy-paste-onboarding)
---

## A. Estructura y Arquitectura

### 1. God Class / Clase Dios

**Severidad:** 🔴 Crítico

**Descripción:** Una clase que hace demasiadas cosas, violando el Principio de Responsabilidad Única (SRP).

**Evidencia:**
- Archivo PHP de **10,000+ líneas** en una sola clase
- Gestiona: catalogo de productos, carrito de compra, pedidos, facturacion, clientes, inventario, envios, devoluciones, cupones, emails, fusion de cuentas, operadores, analytics, y renderizado HTML.
- **100+ acciones** diferentes en un único switch

**Impacto:**
- Imposible de testear unitariamente
- Cualquier cambio tiene riesgo de romper funcionalidad no relacionada
- Onboarding de nuevos desarrolladores: semanas
- Merge conflicts constantes en equipo

**Cómo estrangularlo:**
- Identificar bounded contexts (Pedidos, Facturacion, Clientes, etc.)
- Extraer cada contexto a su propio servicio/controller
- Usar Facade para mantener compatibilidad durante la transición

---

### 2. God Method / Método Dios

**Severidad:** 🔴 Crítico

**Descripción:** Un método que contiene lógica excesiva y demasiadas responsabilidades.

**Evidencia:**
- Método `executeAction()` con un **switch de 100+ casos**
- Cada caso delega a un método diferente, pero el switch en sí es un punto de acoplamiento masivo
- Queries SQL de 60+ líneas dentro de métodos

**Impacto:**
- Dificultad extrema para añadir nuevas acciones
- El método se convierte en bottleneck para cualquier cambio

---

### 3. Inheritance Abuse / Abuso de Herencia

**Severidad:** 🟠 Alto

**Descripción:** Uso de herencia para reutilizar código en lugar de para modelar relaciones "es-un".

**Evidencia:**
- La clase extiende de `BaseManager`
- Hereda métodos como `comprobarAutenticacion()`, `setValoresPost()`, `getResultFromSelectSQL()`
- La herencia se usa como mecanismo de inyección de dependencias implícita

**Impacto:**
- Acoplamiento fuerte a la clase padre
- Imposible cambiar la implementación base sin afectar a todos los hijos

---

### 4. Constructor Heavy / Constructor Sobrecargado

**Severidad:** 🟠 Alto

**Descripción:** El constructor realiza demasiada inicialización y tiene efectos secundarios.

**Evidencia:**
- El constructor instancia múltiples dependencias internas
- Llama a `parent::__construct()` que a su vez hace `cargarConfiguracion()`
- `cargarConfiguracion()` carga la tienda, configura fechas, calcula limites, etc.
- IDs hardcodeados en el constructor

**Impacto:**
- Imposible instanciar la clase sin un contexto completo
- Testing requiere mockear toda la cadena de inicialización

---

### 5. High Cognitive Load Architecture

**Severidad:** 🟠 Alto

**Descripción:** La arquitectura requiere mantener demasiado contexto mental para entender el flujo.

**Evidencia:**
- Para entender una acción simple hay que rastrear:
  1. El número de acción en el switch
  2. El método correspondiente
  3. Las propiedades de estado que usa
  4. Las dependencias que instancia
  5. Las queries SQL que ejecuta
  6. El template que renderiza
  7. El callback JS que procesa la respuesta

**Impacto:**
- Solo los desarrolladores más veteranos pueden modificar código con confianza
- Bus factor = 1 o 2

---

### 6. Leaky Abstractions / Abstracciones Permeables

**Severidad:** 🟠 Alto

**Descripción:** Las abstracciones filtran detalles de implementación que deberían estar ocultos.

**Evidencia:**
- Los métodos de base de datos exponen stdClass directamente
- Los templates acceden a propiedades internas del objeto
- El JS conoce la estructura interna de las respuestas del servidor
- Métricas de rendimiento expuestas en headers HTTP

**Impacto:**
- Cambiar la implementación interna requiere cambiar todos los consumidores
- No se puede refactorizar sin romper compatibilidad

---

### 7. Hardcoded Infrastructure

**Severidad:** 🟠 Alto

**Descripción:** Detalles de infraestructura (rutas, nombres de BD, IDs) hardcodeados en el código.

**Evidencia:**
```php
// Rutas relativas hardcodeadas
include_once dirname(__FILE__)."/../../../../lib/u_globales.php";
$this->ruta_raiz='/../../../../';

// Nombres de bases de datos
FROM tienda_db.order_items
FROM servicios_db.order_checks

// IDs hardcodeados
$this->id_elemento=12345;
```

**Impacto:**
- Imposible desplegar en un entorno con estructura diferente
- Migración de BD requiere buscar y reemplazar en todo el código

---

### 8. Infrastructure Leakage

**Severidad:** 🟡 Medio

**Descripción:** Detalles de infraestructura (S3, BD, templates) se filtran en la lógica de negocio.

**Evidencia:**
- Lógica de negocio que escribe directamente en S3
- Queries SQL con nombres de tablas específicas esparcidos por toda la clase
- Templates referenciados directamente en métodos de dominio

---

## B. Estado y Acoplamiento

### 9. Mutable Shared State / Estado Compartido Mutable

**Severidad:** 🔴 Crítico

**Descripción:** Estado mutable compartido entre métodos que se modifica de forma implícita.

**Evidencia:**
- Propiedades como `$this->id_elemento`, `$this->fecha_inicio`, `$this->storeConfig` se leen y escriben por decenas de metodos
- `$this->ValoresPost_JSON_obj` se modifica en `cargarConfiguracion()` y se lee en todos los metodos
- `$this->Plantilla` se crea en `setPlantilla()` y se usa en todos los metodos que renderizan

**Impacto:**
- Orden de llamadas importa (acoplamiento temporal)
- Efectos secundarios impredecibles
- Imposible paralelizar o cachear

**Cómo estrangularlo:**
- Hacer las propiedades inmutables (readonly)
- Pasar datos como parámetros explícitos
- Usar value objects

---

### 10. Action at Distance / Acción a Distancia

**Severidad:** 🟠 Alto

**Descripción:** Un cambio en un lugar causa efectos en otro lugar no obvio.

**Evidencia:**
- `cargarConfiguracion()` modifica `$this->maxItems` que afecta a `mostrarCatalogo()`
- `cargarTienda()` setea `$this->soloOnline` que se usa en queries de `getPedidos()`
- `setPlantilla()` asigna variables al template que se usan en HTML que se envia al JS

**Impacto:**
- Bugs difíciles de rastrear
- Efecto mariposa: cambio pequeño → bug grande en lugar inesperado

---

### 11. Temporal Coupling / Acoplamiento Temporal

**Severidad:** 🟠 Alto

**Descripción:** Los métodos deben llamarse en un orden específico pero no hay forma de saberlo.

**Evidencia:**
- Debes llamar a `cargarTienda()` antes de cualquier metodo que use `$this->storeConfig`
- `setPlantilla()` debe llamarse antes de cualquier metodo que use `$this->Plantilla`
- `cargarConfiguracion()` debe ejecutarse antes de leer cualquier `$this->ValoresPost_JSON_obj->request->*`

**Impacto:**
- La clase solo funciona si se usa en el orden "correcto"
- No hay validación de precondiciones

---

### 12. Implicit Workflow Coupling

**Severidad:** 🟡 Medio

**Descripción:** El flujo de trabajo está implícito en el orden de las llamadas, no explícito.

**Evidencia:**
- El orden de las operaciones se documenta con comentarios, no con código
- Métricas de timing llamadas manualmente entre cada paso

---

### 13. Service Locator by Object State

**Severidad:** 🟠 Alto

**Descripción:** Las dependencias se resuelven a través del estado del objeto en lugar de inyección explícita.

**Evidencia:**
```php
// Las dependencias se instancian usando propiedades del objeto
$this->Pricing = new PricingService(['store_id' => $this->storeId, 'type' => 1]);
$Shipping = new ShippingService(['store_id' => $this->storeId, 'type' => 1]);
$Catalog = new ProductCatalog(['store_id' => $this->storeId]);
```

**Impacto:**
- Imposible mockear dependencias en tests
- Acoplamiento fuerte a constructores específicos

---

### 14. Hidden Side Effects / Efectos Secundarios Ocultos

**Severidad:** 🟠 Alto

**Descripción:** Métodos que parecen de consulta pero modifican estado.

**Evidencia:**
- `getPedidos()` puede hacer UPDATE para asignar colores aleatorios a pedidos
- Metodos `verX()` que supuestamente solo muestran, pero pueden triggerar calculos

**Impacto:**
- Violación de Command-Query Separation (CQS)
- Imposible saber si un método es seguro para llamar repetidamente

---

### 15. Recursive Service Instantiation

**Severidad:** 🟡 Medio

**Descripción:** Servicios que instancian otros servicios que instancian más servicios, creando cadenas profundas.

**Evidencia:**
- Cada clase instancia sus propias dependencias internamente
- Cadenas: `OrderManager` → `ProductCatalog` → `PricingService` → `ShippingService` → ...

---

### 16. Environment Logic Scattered

**Severidad:** 🟡 Medio

**Descripción:** Lógica condicional de entorno (producción, test, local) esparcida por todo el código.

**Evidencia:**
- `ONLOCAL ? 'INNER JOIN' : 'STRAIGHT_JOIN'`
- `HostProduccion` controla carga de scripts minificados
- Checks de `ENPRODUCCION` en templates

---

## C. Modelado de Datos (PHP)

### 17. Primitive Obsession / Obsesión por Primitivos

**Severidad:** 🟠 Alto

**Descripción:** Uso de tipos primitivos (int, string, bool) para representar conceptos de dominio.

**Evidencia:**
- IDs como `int` en lugar de `OrderId`, `ProductId`, `StoreId`
- Fechas como `string` ("Y-m-d") en lugar de objetos `DateTime` o value objects
- Estados como `int` (0, 1, 2) en lugar de enums
- Precios como `float` en lugar de `Money`

**Impacto:**
- Confusión entre tipos de IDs
- Sin validación de formato de fecha
- Problemas de precisión con floats para dinero

---

### 18. Array-Based Domain Modeling

**Severidad:** 🟠 Alto

**Descripción:** Estructuras de dominio modeladas como arrays asociativos en lugar de objetos con comportamiento.

**Evidencia:**
```php
$resulta['pedidos'][$r->id_pedido]['datos'] = new stdClass();
$resulta['pedidos'][$r->id_pedido]['items'][] = $r;
$resulta['pedidos'][$r->id_pedido]['lineas'][$r->id_producto_base][$r->fecha] = $r;
```
- Arrays multidimensionales con claves magicas: `'datos'`, `'items'`, `'lineas'`, `'drh'`

**Impacto:**
- Sin validación de estructura
- Typos en claves = bugs silenciosos
- Imposible añadir comportamiento a los datos

---

### 19. stdClass como Modelo Universal

**Severidad:** 🟠 Alto

**Descripción:** Uso de `stdClass` como contenedor de datos genérico en lugar de DTOs o value objects tipados.

**Evidencia:**
```php
$this->storeConfig = new stdClass();
$temp = new stdClass();
$temp->dia = date('d', strtotime($fecha));
$return = new stdClass();
$return->resultado = $resulta;
```

**Impacto:**
- Sin autocompletado en IDE
- Sin validación de propiedades
- Propiedades opcionales vs requeridas no diferenciadas

---

### 20. Anemic Domain Model / Modelo de Dominio Anémico

**Severidad:** 🟡 Medio

**Descripción:** Objetos que solo tienen datos (getters/setters) sin comportamiento de dominio.

**Evidencia:**
- `stdClass` por todas partes sin métodos
- Entidades de BD mapeadas a objetos sin lógica
- Toda la lógica de negocio está en la clase God, no en los objetos de dominio

---

### 21. Stringly Typed Architecture

**Severidad:** 🟠 Alto

**Descripción:** Uso de strings donde deberían haber tipos específicos.

**Evidencia:**
- Nombres de templates como strings
- Claves de arrays como strings mágicas
- Acciones identificadas por strings en algunos casos

---

### 22. Boolean / Integer Flags

**Severidad:** 🟡 Medio

**Descripción:** Uso de flags booleanos o enteros para controlar comportamiento.

**Evidencia:**
```php
private $multicatalog=0;
private $vista_tienda=0;
private $facturacion_directa=0;

if($this->ValoresPost_JSON_obj->request->ver_envios==1){
if($this->ValoresPost_JSON_obj->request->ver_devoluciones==1){
```

**Impacto:**
- Combinatoria explosiva de flags
- No queda claro qué significa `1` vs `0` vs `null`

---

### 23. Data Clumps

**Severidad:** 🟡 Medio

**Descripción:** Grupos de datos que siempre viajan juntos pero no están encapsulados.

**Evidencia:**
- `$id_producto`, `$id_producto_base`, `$fecha` aparecen juntos en decenas de metodos
- `id_elemento`, `tipo_elemento` siempre juntos
- `fecha_inicio`, `fecha_fin`, `max_items` como grupo

---

## D. Seguridad

### 24. SQL Injection

**Severidad:** 🔴 Crítico

**Descripción:** Variables interpoladas directamente en queries SQL sin preparación ni escaping.

**Evidencia:**
```php
$QAux = "SELECT * FROM tienda_db.products AS p WHERE p.store_id=$this->id_elemento";

$QAux = "SELECT * FROM tienda_db.ofertas
         WHERE store_id=$this->id_elemento
         AND fecha BETWEEN '$this->fecha_inicio' AND '$this->fecha_fin'";
```

**Impacto:**
- Vulnerabilidad de seguridad crítica
- Potencial para data breach completo

**Cómo estrangularlo:**
- Migrar a prepared statements
- Usar un ORM o query builder
- Validar y sanear todos los inputs

---

### 25. Security Anti-Patterns

**Severidad:** 🔴 Crítico

**Descripción:** Múltiples patrones inseguros más allá de SQL injection.

**Evidencia:**
- Hash débil para autenticación: `sha1($id.'secret'.date('Y-m-d'))` — predecible
- Variables POST expuestas en templates
- Imágenes base64 sin validación de tipo o tamaño

---

### 26. SSL Verification Disabled

**Severidad:** 🔴 Crítico

**Descripción:** Verificación SSL deshabilitada en llamadas HTTP externas.

**Impacto:**
- Vulnerable a ataques Man-in-the-Middle
- Datos sensibles pueden ser interceptados

---

## E. Manejo de Errores

### 27. Silent Catch / Captura Silenciosa

**Severidad:** 🟠 Alto

**Descripción:** Excepciones capturadas pero ignoradas completamente.

**Evidencia:**
```php
try {
    $this->order_operator = $this->obtenerOrderOperator($this->id_pedido);
} catch(Exception $ex) {
    // Nada - la excepcion se silencia
}

// Mismo patron repetido para locks, contadores, tracking:
try {
    $locks = new LockService(array());
    $locks->getArrayLocks(...);
} catch (Exception $e) {
    // Silencio absoluto
}
```

**Impacto:**
- Errores invisibles en producción
- Debugging imposible
- Estado inconsistente sin notificación

---

### 28. Exception Handling as Business Logic

**Severidad:** 🟡 Medio

**Descripción:** Uso de excepciones para controlar flujo de negocio en lugar de errores reales.

**Evidencia:**
- Excepciones con códigos de error personalizados (1555, 2002, 2003, etc.)
- Re-lanzamiento de excepciones con transformación de mensajes
- Códigos de error mapeados a mensajes de UI

---

### 29. Fear-Driven Logging

**Severidad:** 🟡 Medio

**Descripción:** Logging excesivo y defensivo, probablemente añadido después de bugs difíciles de diagnosticar.

**Evidencia:**
- Tracking de rendimiento llamado después de cada operación
- Logging en S3 en múltiples puntos de error
- Dump completo de request en logs de error

**Impacto:**
- Ruido en logs
- Posible impacto de rendimiento
- Dificultad para encontrar información relevante

---

## F. Código y Mantenibilidad

### 30. Dead Code / Fossil Code

**Severidad:** 🟡 Medio

**Descripción:** Código comentado, bloques `if(false)`, y código que ya no se usa pero se mantiene "por si acaso".

**Evidencia:**
```php
// Bloque SQL de 30 lineas comentado con "YA NO SE USA ORDER_PARTICULAR"
/* ---- YA NO SE USA ORDER_PARTICULAR ... */

if (false && $this->ModulosLicencia[32]) { ... }

if(true||count((array) $conceptos)==0) { ... }

// Casos comentados en el switch
//case 137: $resulta = $this->verEditarOperador(); break;
```

**Impacto:**
- Confusión sobre qué código es el vigente
- Aumenta el tamaño del archivo innecesariamente

---

### 31. Mixed Language Naming

**Severidad:** 🟡 Medio

**Descripción:** Mezcla de idiomas (español, inglés, spanglish) en nombres de variables, métodos y clases.

**Evidencia:**
- Metodos: `mostrarCatalogo`, `getPedidos`, `cargarTienda`, `setPlantilla`
- Variables: `$fecha_inicio`, `$width_catalog`, `$max_items`, `$vista_tienda`
- Clases: `OrderManager_v2`, `ProductCatalogNew`, `ShippingService2`

---

### 32. Magic Numbers / Números Mágicos

**Severidad:** 🟡 Medio

**Descripción:** Números sin significado aparente esparcidos por el código.

**Evidencia:**
```php
$this->id_elemento = 12345;
$this->tipo_elemento = 1;
$AccionesSinHash = 9000;
$max_items = 36;
$this->width_catalog -= 30;
($this->width_catalog * 0.90)
```

---

### 33. DRY Violations / Violaciones DRY

**Severidad:** 🟠 Alto

**Descripción:** Código duplicado en múltiples lugares.

**Evidencia:**
- `getPedidos()` y `getPedidos_VistaMensual()` con logica casi identica
- Bloques de envios/devoluciones duplicados
- Lógica de precios duplicada para diferentes modos

---

### 34. Long Parameter List

**Severidad:** 🟡 Medio

**Descripción:** Métodos con demasiados parámetros o que pasan objetos JSON como parámetros únicos.

**Evidencia:**
- JSON objects pasados como parámetros con estructura implícita
- Arrays de request con decenas de campos posibles

---

## G. Presentación y Comunicación

### 35. Presentation Mixed with Domain

**Severidad:** 🟠 Alto

**Descripción:** Lógica de presentación (templates, HTML) mezclada con lógica de dominio.

**Evidencia:**
```php
$this->setPlantilla();
$this->Plantilla->assign('dias', $dias);
// ... lógica de negocio intercalada con asignaciones de template ...
return utf8_encode_Int($this->IdiomaInterfaz->traducirTPL(
    $this->Plantilla->fetch('modulos/.../catalogo.html')
));
```

**Impacto:**
- Imposible reutilizar lógica de dominio sin la capa de presentación
- Dificultad para crear API REST sin templates

---

### 36. Output Format Confusion

**Severidad:** 🟡 Medio

**Descripción:** Métodos que a veces devuelven HTML, a veces arrays, a veces stdClass.

**Evidencia:**
- Algunos métodos retornan HTML renderizado
- Otros retornan arrays de datos
- Otros retornan stdClass con propiedades

---

### 37. Action-Based Routing (Numerical Actions)

**Severidad:** 🟠 Alto

**Descripción:** Protocolo de comunicación basado en números de acción en lugar de endpoints semánticos.

**Evidencia:**
```php
switch ($this->ValoresPost_JSON_obj->request->accion) {
    case 1:   $resulta = $this->mostrarCatalogo(); break;
    case 2:   $resulta = $this->getPedidos(); break;
    case 3:   $resulta = $this->obtenerDatosPedido(); break;
    // ... hasta case 100
}
```

**Impacto:**
- Sin semántica: ¿qué hace la acción 147?
- Colisiones potenciales al añadir nuevas acciones
- Imposible usar herramientas estándar de API (Swagger, OpenAPI)

**Cómo estrangularlo:**
- Migrar a endpoints REST con nombres semánticos
- Usar action names en lugar de números durante la transición

---

### 38. Server-Rendered HTML over AJAX

**Severidad:** 🟡 Medio

**Descripción:** El servidor renderiza HTML completo que el cliente inyecta directamente en el DOM.

**Evidencia:**
```php
// PHP
$retorno['catalogo'] = utf8_encode_Int($this->IdiomaInterfaz->traducirTPL(
    $this->Plantilla->fetch('modulos/.../catalogo.html')
));

// JS
elem.innerHTML = Datos.response.resultado;
```

**Impacto:**
- Acoplamiento fuerte entre servidor y cliente
- Imposible reutilizar datos en otro formato (mobile app, API)
- Traducción de HTML completo en cada request

---

## H. Database Design

### 39. Magic Values as Type Discriminators

**Severidad:** 🔴 Crítico

**Descripción:** Columnas `tipo` y `chk_tipo` con valores numéricos sin documentación que controlan comportamiento.

**Evidencia:**
```sql
-- Columna `tipo` en order_items:
WHERE oi.tipo = 0    -- 0 = producto/pedido
WHERE oi.tipo = 1    -- 1 = bloqueo de stock
WHERE oi.tipo = 2    -- 2 = servicios del dia
WHERE oi.tipo = 3    -- 3 = servicios extraordinarios
WHERE oi.tipo = 4    -- 4 = extras
WHERE oi.tipo = 5    -- 5 = envio estandar
WHERE oi.tipo = 6    -- 6 = envio express
WHERE oi.tipo = 7    -- 7 = envio urgente

-- Columna `chk_tipo` en servicios_db.order_checks:
chk_tipo = 2    -- marca de "vista"
chk_tipo = 3    -- estado processing
chk_tipo = 4    -- estado shipped
chk_tipo = 5    -- estado blocked
chk_tipo = 6    -- estado invoiced
chk_tipo = 7    -- check adicional
```

**Impacto:**
- Sin ENUMs ni tablas de referencia
- Añadir un nuevo tipo requiere buscar TODOS los `WHERE tipo = X`
- Bugs silenciosos si se usa un valor no contemplado

---

### 40. Cross-Schema Queries Without Abstraction

**Severidad:** 🟠 Alto

**Descripción:** Queries que cruzan 3 esquemas diferentes sin capa de abstracción.

**Evidencia:**
```sql
FROM tienda_db.order_items oi
INNER JOIN tienda_db.orders o USING(store_id,id_pedido)
INNER JOIN tienda_db.order_b2c ob ON (...)
LEFT JOIN servicios_db.cmp_comportamiento AS cmp USING (id_comportamiento)
LEFT JOIN servicios_db.order_checks chk ON (...)
LEFT JOIN extras_db.exp_textos AS t1 ON (...)
```

**Esquemas involucrados:**
| Esquema | Proposito |
|---|---|
| `tienda_db` | Sistema legacy de pedidos |
| `servicios_db` | Motor de servicios, tracking, facturacion |
| `extras_db` | Modulo de extras/promociones |

**Impacto:**
- Migrar un esquema requiere reescribir todas las queries
- Sin transacciones cross-schema garantizadas

---

### 41. Duplicate Table Pairs (Operator vs Particular)

**Severidad:** 🟠 Alto

**Descripcion:** Dos sistemas paralelos de pedidos con estructuras casi identicas.

**Evidencia:**
| Sistema B2B | Sistema B2C |
|---|---|
| `order_b2b` (~130 cols) | `order_b2c` (~70 cols) |
| `conceptos_b2b` | `order_b2c_conceptos` |

Cada query debe hacer UNION o branching.

**Impacto:**
- Duplicación de lógica en cada query
- Inconsistencias potenciales entre ambos sistemas
- Cada bug fix debe aplicarse en dos sitios

---

### 42. Inconsistent Column Naming Across Schemas

**Severidad:** 🟠 Alto

**Descripción:** El mismo concepto tiene nombres diferentes según el esquema.

**Evidencia:**
| Concepto | `tienda_db` | `servicios_db` |
|---|---|---|
| ID tienda | `store_id` | `id_elemento` |
| ID pedido | `id_pedido_original` | `id_pedido` |
| Tipo de elemento | N/A | `tipo_elemento` |
| Tipo de check | N/A | `chk_tipo` |

**Impacto:**
- JOINs confusos
- `USING()` solo funciona cuando los nombres coinciden

---

### 43. Redundant / Duplicated Columns

**Severidad:** 🟡 Medio

**Evidencia:**
- `id_pedido` vs `id_pedido_original` — doble join necesario en cada lookup
- `customer` vs `customer_copy` — datos duplicados al momento de ordenar
- `store_id` aparece en casi TODAS las tablas como foreign key redundante
- `fecha` vs `fecha_pedido` — semantica solapada

---

### 44. JSON / Serialized Data in Columns

**Severidad:** 🟡 Medio

**Evidencia:**
```
order_b2c.customer_data          → JSON customer data
fac_factura.extra                     → JSON invoice extras (con stripslashes!)
fac_config_elemento.data              → JSON e-invoicing config
order_b2b.applied_code         → pipe-delimited: "tipo|valor"
lic_elementos.extra                   → JSON view config
```

**Impacto:**
- Sin validación de schema
- Queries no pueden filtrar por contenido JSON eficientemente

---

### 45. N+1 Query Patterns in Loops

**Severidad:** 🟠 Alto

**Evidencia:**
```php
// INSERT uno a uno por linea de pedido en lugar de bulk INSERT
foreach ($lineas as $linea) {
    $QAux = "INSERT INTO order_items (...) VALUES (...)";
    $this->executeSQL($QAux);
}
```

---

### 46. SELECT * Anti-Pattern

**Severidad:** 🟡 Medio

**Evidencia:** 100+ instancias de `SELECT *` en tablas anchas.

---

### 47. Missing Indexes Evidence

**Severidad:** 🟠 Alto

**Evidencia:**
- `SELECT * ... LIMIT 10000` + filtrado en PHP — sin FULLTEXT index
- `LIKE '%-$id_producto_base'` — wildcard inicial impide uso de indices
- `FORCE INDEX` — evidencia de que el optimizador no elige el índice correcto
- `GROUP BY localizador` sin agregación

---

### 48. UNION Without ALL

**Severidad:** 🟡 Medio

**Descripción:** UNIONs donde UNION ALL sería suficiente (no se necesita deduplicación).

**Evidencia:** 9+ UNIONs sin ALL — overhead de deduplicación innecesario.

---

### 49. Foreign Keys Disabled for Operations

**Severidad:** 🟠 Alto

**Evidencia:**
```php
$this->executeSQL("SET FOREIGN_KEY_CHECKS=0;");
// ... bulk operations ...
$this->executeSQL("SET FOREIGN_KEY_CHECKS=1;");
```

**Impacto:**
- Operaciones que requieren desactivar FKs indican diseño problemático
- Posible dejar BD en estado inconsistente si falla a mitad

---

### 50. Soft Deletes Without Consistency

**Severidad:** 🟡 Medio

**Evidencia:**
```sql
product_deleted = 0          -- soft delete de producto
product_base_deleted = 0     -- soft delete de producto base
estado = 0               -- en order_failed = "pendiente"
estado = 1               -- en order_checks = "activo"
activo = 1               -- en marketplace_products
```
Inconsistencia: algunos usan 0=activo, otros 1=activo.

---

### 51. Hardcoded Business Logic in SQL

**Severidad:** 🟡 Medio

**Evidencia:**
```sql
WHERE fecha_fin_catalog >= '2020-01-01'
WHERE id_elemento IN (100, 200, 300)
WHERE id_elemento != 300
```

---

### 52. Polymorphic Discriminator Columns

**Severidad:** 🟠 Alto

**Descripción:** `tipo_elemento` usado como discriminador polimórfico en casi todas las tablas de `servicios_db`.

**Evidencia:**
```sql
WHERE tipo_elemento = 1    -- 1 = store/tienda
-- ¿Qué es 2? ¿3? ¿4?
```

**Impacto:**
- Imposible añadir foreign keys reales
- Cada tabla necesita `WHERE tipo_elemento = 1` en cada query

---

### 53. LIKE with Leading Wildcards

**Severidad:** 🟡 Medio

**Evidencia:**
```sql
WHERE concepto LIKE '%-$id_producto_base'
WHERE nombre_completo LIKE '%$name%'
WHERE concepto NOT LIKE 'pendiente_%'
```
Wildcards iniciales = full table scan inevitable.

---

### 54. Wide Table / Tabla Ancha

**Severidad:** 🔴 Crítico

`order_b2b`: **~130 columnas**
`order_b2c`: **~70 columnas**

Tablas tan anchas que son imposibles de entender de un vistazo. Mezclan:
- Datos del pedido
- Datos del cliente (duplicados de tabla `customer`)
- Configuracion de emails
- Configuracion de facturas
- Configuracion de comisiones
- Estado de cobros
- Bonos
- Codigos de agencia

**Como estrangularlo:** Extraer grupos conceptuales a tablas hijas:
- `order_customer_info`
- `order_email_config`
- `order_invoice_config`
- `order_commission`
- `order_payment_status`

---

### 55. Repeating Groups (1NF Violation)

**Severidad:** 🟠 Alto

```sql
-- 7 emails de factura como columnas separadas
factura_email_1, factura_email_2, factura_email_3, factura_email_4,
factura_email_5, factura_email_6, factura_email_7

-- 7 fechas de factura
factura_fecha_1, factura_fecha_2, ... factura_fecha_7

-- 7 números de factura
factura_nfactura_1, ... factura_nfactura_7

-- 7 condiciones de factura
factura_condiciones_1, ... factura_condiciones_7

-- 7 flags de comisión activa
comision_activa_1, ... comision_activa_7
```

**Diseño correcto:**
```sql
CREATE TABLE order_invoice_shipments (
    id_pedido INT,
    tipo_envio INT,
    email VARCHAR(255),
    fecha DATE,
    nfactura VARCHAR(50),
    condiciones TEXT
);
```

---

### 56. DOUBLE for Money

**Severidad:** 🟠 Alto

```sql
`precio` double,                    -- order_items
`importe_pedido_original` double,  -- order_b2b
`sobre_tarifa` double,
`valor_fee` double,
`comision_casa_fee` double,
`porcentaje_fee` double,
`iva_operador` double DEFAULT '16',
```

`DOUBLE` tiene problemas de precisión con decimales. `0.1 + 0.2 = 0.30000000000000004`.

**Correcto:** `DECIMAL(10,2)` o almacenar céntimos como `INT`.

---

### 57. VARCHAR for Numeric Values

**Severidad:** 🟠 Alto

```sql
`comision` varchar(15),          -- ¿por qué no DECIMAL?
`comision_minorista` varchar(15),
`neto` varchar(50),              -- dinero como string
`iva` varchar(50),               -- dinero como string
`n_supletorias` varchar(50),     -- cantidad como string
`n_cunas` varchar(50),           -- cantidad como string
`central_anticipo` varchar(50) DEFAULT '0',
```

Imposible hacer `SUM(neto)`, `WHERE neto > 100`, o validación de rango en BD.

---

### 58. Zero Date Default

**Severidad:** 🟡 Medio

```sql
`fecha_entrada` date NOT NULL DEFAULT '0000-00-00',
`fecha_salida` date NOT NULL DEFAULT '0000-00-00',
```

`'0000-00-00'` no existe. Requiere `NO_ZERO_DATE` desactivado en MySQL. PHP lo convierte a `null` o causa errores de parsing.

---

### 59. Charset Obsoleto (latin1)

**Severidad:** 🟡 Medio

Todas las tablas usan `CHARSET=latin1`. No soporta:
- Caracteres UTF-8
- Nombres con caracteres especiales de clientes extranjeros

Esto explica la proliferación de `utf8_encode_Int()`, `utf8_decode_Int()`, y `mb_convert_encoding()` por todo el código PHP.

---

### 60. Over-Indexing

**Severidad:** 🟡 Medio

`order_items` tiene 9 indices para una tabla con un PK compuesto de 5 columnas:

```sql
PRIMARY KEY (store_id, product_id, product_base_id, fecha, tipo)
KEY FK_order_items_2 (product_id, product_base_id, store_id)     -- redundante con PK
KEY IX_order_items_2 (product_id, product_base_id, store_id)  -- ¡duplicado!
KEY IX_order_items_3 (product_base_id)                   -- prefijo del PK
KEY IX_order_items_4 (fecha)                                -- prefijo del PK
KEY IX_fecha_base (store_id, fecha, product_base_id)                   -- solapado
```

Cada INSERT/UPDATE paga el coste de mantener 9 índices.

---

### 61. Missing Foreign Keys

**Severidad:** 🟠 Alto

| Columna | Deberia referenciar | FK existe |
|---|---|---|
| `order_b2b.id_pedido_original` | `orders.id_pedido` | ❌ |
| `order_b2b.id_customer` | `customer_copy.id_customer` | ❌ |
| `order_b2c.id_pedido_original` | `orders.id_pedido` | ❌ |
| `order_b2c.id_customer` | `customer.id_customer` | ❌ |
| `order_checks.id_pedido` | `orders.id_pedido` | ❌ |
| `order_checks.id_usuario` | `auth_usuario.id_usuario` | ❌ |

Solo `order_items` tiene una FK.

---

### 62. Trigger-Based Denormalization

**Severidad:** 🟠 Alto

3 triggers en `order_b2b` mantienen `order_cache`:
- `order_cache` (AFTER INSERT)
- `order_cache_update` (AFTER UPDATE)
- `order_cache_delete` (AFTER DELETE)

Problemas:
- Código duplicado entre los 3 triggers (~80 líneas cada uno)
- Queries cross-schema dentro de triggers
- Si el trigger falla → cache inconsistente
- Hard to debug: ¿por qué cambió este dato? ¿fue la app o el trigger?
- Impacto en rendimiento de cada INSERT/UPDATE/DELETE

---

### 63. Catch-All Varchar Column

**Severidad:** 🟡 Medio

```sql
`concepto` varchar(255)
```

Se usa para:
- Nombre de producto: `"Producto Doble"`
- Flags de tipo: `"pendiente_confirmacion"`
- Referencias: `"123-456"` (id_producto_base)
- Servicios: `"envio incluido"`

El PHP hace `LIKE '%-$id_producto_base'` y `LIKE 'pendiente_%'` sobre esta columna.

---

### 64. Composite Primary Key Excesivo

**Severidad:** 🟡 Medio

```sql
-- order_checks: PK de 7 columnas
PRIMARY KEY (product_id, product_base_id, fecha_pedido,
             tipo_elemento, id_elemento, id_pedido, chk_tipo)
```

PKs tan anchos que cada índice secundario los replica completamente.

---

### 65. Truncated Column Comments

**Severidad:** 🟡 Medio

```sql
`tipo_pago` int COMMENT '0=TrCasa, 1=TPVCas, 2=TrOp Todo, 3=TPVOp todo, 4=TranO Ant,5'
```
El comentario se trunca — no sabemos qué significa el `5`.

---

## I. Naming

### 66. Mixed Language Naming (Spanglish)

**Severidad:** 🟠 Alto

**Descripción:** Mezcla caótica de español, inglés y spanglish en todos los niveles.

**Evidencia:**

**Métodos PHP:**
```
mostrarPlanning()        // español + inglés
getReservas()            // inglés + español
cargarAlojamiento()      // español puro
setPlantilla()           // español
TCD_GetOrderList_GetOrderList()  // prefix + ingles mal escrito
```

**Variables:**
```
$fecha_inicio            // español
$width_catalog          // ingles + ingles
$n_dias_max              // español abreviado
$multiplaning            // inglés (mal escrito, falta 'n')
```

**Clases:**
```
OrderManager_v2_ProductList    // ingles
ProductCatalogNew              // ingles + ingles
ShippingService2               // ingles + numero
```

**Impacto:**
- Búsqueda imposible: ¿buscas `getReserva`, `obtenerReserva`, `verReserva`?
- Onboarding de devs no hispanohablantes: imposible

---

### 67. Inconsistent Prefix Conventions

**Severidad:** 🟡 Medio

**Evidencia:**
```
OrderManager_v2_     // modulo + version
TCD_          // acronimo (TotalCustomerData)
TMr           // "Mister" (de MisterPlan)
TExp          // "Experiencia"
ProductCatalog         // sin prefijo claro
ShippingService2    // nombre + numero de version
```

La `T` inicial parece ser convención de "Tipo/Clase" pero no se aplica consistentemente.

---

### 68. Ambiguous Abbreviations

**Severidad:** 🟡 Medio

**Evidencia:**
```
$drh     // ¿order_item_data? ¿derecho?
$ro      // ¿order_b2b? ¿rollo?
$QAux    // ¿Query Auxiliary?
$ffrr    // ¿fac_factura_rel_pedido?
$pprr    // ¿par_participante_rel_pedido?
$chk     // ¿check? ¿checkpoint?
$pdif    // ¿pago_diferido?
$llave2  // ¿llave? ¿por qué 2?
```

---

### 69. Inconsistent Verb Conventions

**Severidad:** 🟡 Medio

**Evidencia:** Cuatro formas de nombrar métodos que "obtienen" datos:
```
getX()         → getPedidos(), getInitialInterface()
obtenerX()     → obtenerDatosPedido(), obtenerOrderB2B()
verX()         → verParticipante(), verElementoEvnt()
mostrarX()     → mostrarCatalogo(), mostrarNuevoPedido()
```

Cual es la diferencia semantica entre `getPedidos()`, `obtenerDatosPedido()`, `verPedidosCamping()` y `mostrarCatalogo()`? **Ninguna clara.**

---

### 70. Hungarian Notation Variants

**Severidad:** 🟡 Medio

**Evidencia:**
```
$id_pedido           // tipo implicito (int)
$fecha_inicio         // tipo implícito (string date)
$n_dias_max           // la 'n' indica número
$n_personas           // la 'n' indica número
```

La `n_` prefix se usa inconsistentemente — a veces sí, a veces no.

---

### 71. Schema-Level Naming Inconsistency

**Severidad:** 🟠 Alto

**Evidencia:**
```
tienda_db.products              → "products" (ingles)
servicios_db.cfg_configuracion   → "configuracion" con prefijo "cfg_"
tienda_db.order_b2b  → sin prefijo
servicios_db.chk_pedido         → con prefijo "chk_"
servicios_db.fac_factura         → con prefijo "fac_"
servicios_db.pdif_pago_diferido  → con prefijo "pdif_"
tienda_db.ofe_ofertas       → con prefijo "ofe_"
servicios_db.par_participante    → con prefijo "par_"
```

Algunas tablas tienen prefijo de módulo, otras no. No hay patrón consistente.

---

### 72. Table Naming: Singular vs Plural

**Severidad:** 🟡 Medio

**Evidencia:**
```
products           → plural
order        → singular
ofe_ofertas    → plural
paises_products    → plural + plural
datos_order_item → singular
conceptos_b2b → plural + singular
```

---

### 73. Cryptic Variable Names

**Severidad:** 🟡 Medio

**Evidencia:**
```php
$resulta        // ¿resultado? ¿result array?
$QAux           // Query Auxiliary
$temp           // ¿temporal? ¿temporadas?
$outHTML        // output HTML
$d              // ¿datos? ¿drh?
$p              // ¿parcial? ¿periodo?
```

---

### 74. Action Numbers Instead of Names

**Severidad:** 🟠 Alto

**Descripción:** Protocolo de comunicación basado en números sin semántica.

**Evidencia:**
```
Accion 1   = mostrarCatalogo
Accion 2   = getPedidos
Accion 3   = obtenerDatosPedido
...
Accion 147 = buscarPedidosDeCliente
Accion 100 = getCodigoPostalByMunicipio
```

**Impacto:**
- Sin autodescubrimiento de API
- Documentación obligatoria externa
- Colisiones al añadir acciones

---

### 75. Cryptic Column Abbreviations

**Severidad:** 🟡 Medio

**Evidencia:**
```sql
`central_a`      -- ¿a = article?
`central_a_d`    -- ¿a_d = ?
`central_mp`     -- ¿mp = media pensión?
`central_pc`     -- ¿pc = pensión completa?
`central_ti`     -- ¿ti = ?
`mbo_id_operador` -- ¿mbo = ?
`n_cuenta`       -- ¿n = número?
```

---

### 76. Numeric Suffix Columns

**Severidad:** 🟠 Alto

Ya documentado en Repeating Groups, pero merece mención específica de naming: `factura_email_1` ... `factura_email_7` es un anti-patrón tanto de diseño como de naming. Los números en nombres de columnas casi siempre indican que debería ser una tabla hija.

---

### 77. Typo in Comments

**Severidad:** 🟡 Medio

```sql
`id_pedido_original` int COMMENT 'Relcaionado con orders.id_pedido'
-- "Relcaionado" → "Relacionado"
```

Indicativo de documentación descuidada.

---

## J. Integraciones y APIs

### 78. Retry Storm / Reintentos Descontrolados

**Severidad:** 🔴 Crítico

**Descripción:** Reintentos automáticos sin backoff exponencial, sin límite máximo y sin circuit breaker que colapsan servicios externos.

**Evidencia:**
```php
// Reintento infinito sin límite ni backoff
while (!$response->success) {
    $response = $this->callExternalAPI($data);
    // Sin sleep, sin max retries, sin circuit breaker
    // Si el servicio externo está caído, bucle infinito
}
```

**Impacto:**
- Colapso en cascada de servicios externos
- Consumo excesivo de CPU y red
- DDoS accidental a APIs de terceros
- Bloqueo del proceso principal

**Cómo estrangularlo:**
1. Implementar backoff exponencial con jitter
2. Limitar reintentos a 3-5 máximo
3. Añadir circuit breaker (ej: después de 5 fallos, abrir circuito por 30s)
4. Usar colas asíncronas para reintentos en lugar de bucles síncronos

### 79. No Idempotency in External Operations

**Severidad:** 🔴 Crítico

**Descripción:** Operaciones externas (pagos, envíos, notificaciones) que no son idempotentes y se ejecutan múltiples veces ante timeouts o reintentos.

**Evidencia:**
```php
// Si el timeout ocurre, se reintenta sin idempotency key
$payment = $this->paymentGateway->charge($amount, $cardToken);
// Timeout → reintento → doble cobro al cliente
// No hay idempotency key ni verificación de estado previo
```

**Impacto:**
- Dobles cobros a clientes
- Pedidos duplicados en sistemas externos
- Notificaciones duplicadas (emails, SMS)
- Inconsistencia financiera

**Cómo estrangularlo:**
1. Generar idempotency keys únicas por operación (UUID basado en orderId + action)
2. Verificar estado previo antes de reintentar
3. Usar transacciones con estado "pending" hasta confirmación
4. Implementar compensación (reembolsos automáticos si se detecta duplicado)

### 80. API Response Shape Coupling

**Severidad:** 🟠 Alto

**Descripción:** El código interno depende directamente de la estructura anidada de respuestas de APIs externas sin capa de adaptación.

**Evidencia:**
```php
// Acoplamiento directo al shape de la API externa
$price = $response['data']['product']['pricing']['final']['amount'];
$currency = $response['data']['product']['pricing']['final']['currency'];
// Si la API cambia un nivel, todo se rompe
// Si 'pricing' es null → Warning: Undefined array key
```

**Impacto:**
- Cambios en APIs externas rompen el sistema internamente
- Sin validación de estructura de respuesta
- Errores silenciosos o warnings en producción

**Cómo estrangularlo:**
1. Crear capa Anti-Corruption Layer (ACL) que adapte respuestas externas a modelos internos
2. Validar estructura de respuesta con schemas (JSON Schema, assert)
3. Usar DTOs tipados para respuestas externas
4. Implementar fallbacks graceful cuando la estructura cambia

### 81. Vendor SDK Domain Leakage

**Severidad:** 🟡 Medio

**Descripción:** Objetos del SDK del proveedor (Stripe, PayPal, FedEx) se filtran directamente en la lógica de dominio.

**Evidencia:**
```php
// El dominio conoce el SDK de Stripe directamente
public function processPayment(\Stripe\Charge $stripeCharge): void {
    $this->amount = $stripeCharge->amount;
    $this->status = $stripeCharge->status;
    // Si cambiamos de proveedor, hay que reescribir todo el dominio
}

// Retorno de SDK expuesto en la capa de dominio
public function getPayment(): \Stripe\Charge {
    return $this->payment;
}
```

**Impacto:**
- Imposible cambiar de proveedor sin reescribir dominio
- Tests requieren mocks complejos del SDK
- El dominio depende de librerías externas

**Cómo estrangularlo:**
1. Crear interfaces de abstracción (`PaymentGateway`, `ShippingProvider`)
2. Adaptar respuestas del SDK a modelos internos (`PaymentResult`, `ShipmentStatus`)
3. El dominio solo conoce interfaces, nunca implementaciones concretas
4. Usar patrón Adapter para cada proveedor

---

## K. Concurrencia y Consistencia

### 82. Check-Then-Act Race Condition

**Severidad:** 🔴 Crítico

**Descripción:** Verificar una condición y luego actuar sobre ella sin atomicidad, creando ventana de race condition.

**Evidencia:**
```php
// TOCTOU: Time of Check to Time of Use
if ($product->stock >= $quantity) {
    // Entre el check y el update, otro proceso puede comprar el mismo stock
    $this->db->exec("UPDATE products SET stock = stock - $quantity WHERE id = $id");
}
// Resultado: stock negativo bajo carga concurrente
```

**Impacto:**
- Stock negativo
- Overbooking de productos
- Ventas que no se pueden cumplir
- Inconsistencia de datos bajo carga

**Cómo estrangularlo:**
1. Usar UPDATE atómico con condición: `UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?`
2. Verificar rows affected para saber si se ejecutó
3. Usar transacciones con nivel de aislamiento adecuado
4. Implementar locks optimistas (version column) o pesimistas (SELECT ... FOR UPDATE)

### 83. Transaction Script Without Transactions

**Severidad:** 🟠 Alto

**Descripción:** Operaciones que modifican múltiples tablas sin envolver en transacciones de base de datos.

**Evidencia:**
```php
// 3 updates separados, sin BEGIN/COMMIT
$this->db->exec("UPDATE orders SET status = 2 WHERE id = $id");
$this->db->exec("INSERT INTO shipments (order_id, status) VALUES ($id, 'pending')");
$this->db->exec("UPDATE inventory SET stock = stock - 1 WHERE product_id = $pid");
// Si el segundo falla, el primero ya se ejecutó → orden pagada sin shipment
```

**Impacto:**
- Estado inconsistente si falla a mitad del proceso
- Imposible rollback automático
- Datos huérfanos en tablas hijas
- Pedidos en estado imposible de reconciliar

**Cómo estrangularlo:**
1. Envolver operaciones relacionadas en `$db->beginTransaction()` / `$db->commit()`
2. Usar `$db->rollBack()` en catch
3. Identificar boundaries de transacción (todo lo que debe ser atómico junto)
4. Considerar saga pattern para operaciones que cruzan múltiples bounded contexts

### 84. Distributed Transaction Illusion

**Severidad:** 🟠 Alto

**Descripción:** Operaciones que cruzan múltiples bases de datos o esquemas sin garantía de atomicidad, asumiendo que "casi siempre funciona".

**Evidencia:**
```php
// Update en tienda_db y servicios_db sin two-phase commit
$this->db1->exec("UPDATE tienda_db.orders SET status = 2 WHERE id = $id");
$this->db2->exec("UPDATE servicios_db.shipments SET status = 1 WHERE order_id = $id");
// Si el segundo falla, el primero ya se ejecutó
// No hay forma de garantizar atomicidad cross-schema
```

**Impacto:**
- Inconsistencia entre esquemas
- Sin forma de garantizar atomicidad cross-schema
- Operaciones huérfanas en un esquema cuando el otro falla

**Cómo estrangularlo:**
1. Implementar saga pattern con compensación (si falla paso 2, deshacer paso 1)
2. Usar event sourcing para reconstruir estado consistente
3. Crear tabla de "outbox" para garantizar entrega eventual
4. Diseñar sistemas para consistencia eventual en lugar de atomicidad distribuida

---

## L. Performance y Escalabilidad

### 85. Cache Aside Chaos

**Severidad:** 🟠 Alto

**Descripción:** Cache invalidado de forma inconsistente en múltiples puntos del código, a veces sí, a veces no.

**Evidencia:**
```php
// Cache se invalida en 5 lugares diferentes, de formas distintas
public function updateProduct($id, $data) {
    $this->db->update('products', $data, $id);
    // A veces invalida cache, a veces no (depende de quién llama)
    if ($data['price'] !== null) { // ¿Solo invalida si cambió precio?
        $this->cache->delete("product_$id");
    }
    // Pero no invalida "product_list" cache → lista muestra precio viejo
}
```

**Impacto:**
- Datos stale en producción
- Bugs intermitentes difíciles de reproducir
- Inconsistencia entre cache y base de datos
- Usuarios ven información incorrecta

**Cómo estrangularlo:**
1. Centralizar invalidación de cache en un solo lugar (CacheManager)
2. Usar patrón Cache-Aside consistente: invalidar SIEMPRE después de write
3. Implementar TTLs cortos como fallback de seguridad
4. Usar versionado de keys de cache para invalidación másica

### 86. Premature Micro-Optimization

**Severidad:** 🟡 Medio

**Descripción:** Optimizaciones prematuras que complican el código sin beneficio medible, basadas en suposiciones en lugar de profiling.

**Evidencia:**
```php
// "Optimización" prematura: evita foreach por "performance"
for ($i = 0, $count = count($items); $i < $count; $i++) {
    // Código menos legible sin beneficio real
    // count() fuera del loop no mejora nada en PHP
    processItem($items[$i]);
}

// "Optimización": usar isset() en lugar de property_exists()
// para "ahorrar nanosegundos" pero rompe con propiedades null
```

**Impacto:**
- Código menos legible y mantenible
- Bugs introducidos por "optimizaciones"
- Tiempo desperdiciado optimizando lo que no necesita optimización
- El verdadero bottleneck sigue sin resolver

**Cómo estrangularlo:**
1. Medir primero con profiling (Xdebug, Blackfire, New Relic)
2. Optimizar solo lo que el profiling muestra como bottleneck real
3. Escribir código claro primero, optimizar después si es necesario
4. Documentar por qué se hizo una optimización no obvia

### 87. Batch Processing via Memory Explosion

**Severidad:** 🟠 Alto

**Descripción:** Procesamiento de lotes que carga todos los registros en memoria en lugar de usar chunks o cursores.

**Evidencia:**
```php
// Carga 100,000 registros en memoria para procesarlos
$allOrders = $this->db->query("SELECT * FROM orders WHERE status = 1")->fetchAll();
foreach ($allOrders as $order) {
    $this->processOrder($order);
    // 100,000 objetos Order en memoria simultáneamente
}
// Memory exhausted en producción cuando orders crece
```

**Impacto:**
- Out of memory en producción
- Imposible escalar con crecimiento de datos
- Degradación progresiva (funciona con 1000 registros, falla con 100,000)

**Cómo estrangularlo:**
1. Usar chunks: `SELECT ... LIMIT 1000 OFFSET $offset` en bucle
2. Usar cursores (PDO::FETCH_ASSOC con fetch() en lugar de fetchAll())
3. Implementar procesamiento asíncrono con colas
4. Usar generators de PHP para iterar sin cargar todo en memoria

---

## M. Observabilidad y Operación

### 88. Log-and-Pray

**Severidad:** 🟡 Medio

**Descripción:** Logging excesivo sin estructura, sin niveles apropiados, sin request ID de correlación.

**Evidencia:**
```php
// Logs sin estructura, sin request ID, sin contexto útil
error_log("ERROR: algo falló");
error_log("DEBUG: valor de x = " . $x);
error_log("INFO: proceso terminado");
error_log(print_r($order, true)); // Dump de objeto completo en cada request
// Imposible correlacionar logs de una misma request
// 50GB de logs al día, 99% ruido
```

**Impacto:**
- Logs inutilizables para debugging
- Sin forma de trazar una request completa
- Almacenamiento excesivo de logs
- Información sensible puede acabar en logs

**Cómo estrangularlo:**
1. Usar logger estructurado (PSR-3) con niveles apropiados
2. Añadir request ID/correlation ID a todos los logs
3. Loggear contexto relevante (orderId, userId) no dumps completos
4. Implementar log sampling para requests de alto volumen

### 89. Monitoring Blind Spots

**Severidad:** 🟡 Medio

**Descripción:** Métricas solo en puntos obvios (inicio/fin), sin coverage de flujos críticos intermedios.

**Evidencia:**
```php
// Solo se loggea el inicio y fin, nada del medio
$this->log("Proceso de checkout iniciado");
// ... 200 líneas de lógica sin ningún log ni métrica ...
// Cálculo de envío, aplicación de cupones, validación de stock, pago ...
$this->log("Proceso de checkout terminado");
// Si falla en medio, no hay forma de saber dónde ni por qué
```

**Impacto:**
- Incidents sin contexto para debugging
- MTTR (Mean Time to Resolution) alto
- Imposible detectar degradación gradual
- "Funciona o no funciona" sin puntos intermedios

**Cómo estrangularlo:**
1. Identificar puntos críticos del flujo y añadir métricas
2. Usar distributed tracing (OpenTelemetry, Jaeger)
3. Implementar health checks para dependencias externas
4. Crear dashboards con SLOs por componente

### 90. Configuration by Database

**Severidad:** 🟡 Medio

**Descripción:** Configuración del sistema almacenada en base de datos en lugar de environment variables o archivos de configuración.

**Evidencia:**
```php
// Configuración leída de BD en cada request
$config = $this->db->query("SELECT * FROM system_config")->fetchAll();
define('MAX_ITEMS', $config['max_items']);
define('DEBUG_MODE', $config['debug']);
define('API_KEY', $config['api_key']); // ¡Secrets en BD!
// Query adicional en cada request para leer config
```

**Impacto:**
- Query adicional en cada request (performance)
- Configuración no versionable en git
- Secrets en BD en lugar de vault/env vars
- Imposible tener config por entorno fácilmente

**Cómo estrangularlo:**
1. Mover configuración a environment variables (.env)
2. Usar archivos de configuración versionables (YAML, PHP arrays)
3. Cache de configuración en memoria (opcode cache)
4. Secrets en vault o servicio de gestión de secretos

---

## N. Framework Casero Legacy

### 91. Homemade Framework Syndrome

**Severidad:** 🟠 Alto

**Descripción:** Framework propio construido sobre años con patrones inconsistentes, sin documentación y con sintaxis única.

**Evidencia:**
```php
// Router propio con sintaxis única que nadie conoce
$router->add('accion', function($params) {
    // Cada módulo tiene su propia convención de nombres
    // Algunos usan $params, otros $_GET, otros $this->request
});

// "Helper" global que hace de todo
function helper($action, $data = null) {
    switch ($action) {
        case 'db': return Database::getInstance();
        case 'log': return Logger::getInstance();
        case 'auth': return Auth::check();
        // 50 cases más...
    }
}
```

**Impacto:**
- Onboarding de semanas para entender el framework
- Sin documentación externa disponible
- Imposible encontrar ayuda en StackOverflow
- No sigue ningún estándar (PSR, MVC, etc.)

**Cómo estrangularlo:**
1. Identificar bounded contexts y extraerlos como módulos independientes
2. Implementar adaptadores que traduzcan del framework legacy a estándares
3. Migrar gradualmente a framework estándar (Symfony, Laravel)
4. Usar Strangler Fig: nuevo código en framework moderno, legacy se deprecia

### 92. Copy-Paste Inheritance Framework

**Severidad:** 🟡 Medio

**Descripción:** "Reutilización" de código vía copiar y pegar archivos base entre módulos en lugar de herencia o composición.

**Evidencia:**
```
modules/invoices/controller.php    ← copiado de modules/orders/controller.php
modules/returns/controller.php     ← copiado de modules/invoices/controller.php
modules/shipping/controller.php    ← copiado de modules/returns/controller.php
```

```php
// Cada controller tiene el mismo código con pequeñas variaciones
public function listAction() {
    // 80 líneas idénticas en los 4 módulos
    // Si hay un bug, hay que fixear en 4 lugares
    // Y siempre se olvida alguno
}
```

**Impacto:**
- Bug fix en un módulo requiere copiar a todos los demás
- Divergencia silenciosa entre módulos
- Imposible saber cuál versión es la "correcta"
- Código duplicado crece exponencialmente

**Cómo estrangularlo:**
1. Identificar patrones comunes y extraer a clase base o trait
2. Usar composición en lugar de herencia para comportamiento compartido
3. Implementar patrón Template Method para variaciones
4. Crear tests que verifiquen comportamiento consistente entre módulos

---

## O. Deployment y Entornos

### 93. Snowflake Server

**Severidad:** 🟠 Alto

**Descripción:** Servidores de producción configurados manualmente, cada uno único e irrepetible, sin infrastructure as code.

**Evidencia:**
- SSH para configurar cada servidor manualmente
- "Este servidor tiene un fix especial que no está en los demás"
- Sin Ansible, Terraform, Docker ni nada reproducible
- Solo una persona sabe cómo está configurado

**Impacto:**
- Imposible reproducir bugs de producción
- Deployments inconsistentes entre servidores
- Bus factor = 1 (solo una persona sabe la config)
- Recovery ante desastre imposible

**Cómo estrangularlo:**
1. Documentar configuración actual de cada servidor
2. Implementar infrastructure as code (Terraform, Ansible)
3. Containerizar aplicación (Docker)
4. Automatizar deployments (CI/CD)

### 94. Environment Drift

**Severidad:** 🟡 Medio

**Descripción:** Diferencias significativas entre dev, staging y producción en versiones, configuración y datos.

**Evidencia:**
- Dev: SQLite, staging: MySQL 5.7, prod: MySQL 8.0
- Diferentes versiones de PHP entre entornos (dev: 8.2, prod: 7.4)
- Configuración de BD diferente en cada entorno
- "En staging funciona" es la respuesta habitual

**Impacto:**
- "Funciona en mi máquina"
- Bugs solo aparecen en producción
- Imposible reproducir issues localmente
- Testing poco confiable

**Cómo estrangularlo:**
1. Usar Docker para igualar entornos
2. Versionar configuración de entornos
3. Usar same DB engine en todos los entornos
4. Implementar CI/CD que use el mismo artefacto en todos los entornos

### 95. Feature Flags by Commenting Code

**Severidad:** 🟡 Medio

**Descripción:** Activar/desactivar funcionalidad comentando y descomentando código en lugar de usar feature flags reales.

**Evidencia:**
```php
// TODO: descomentar cuando el módulo de envíos esté listo
// $this->calculateShipping();
// $this->assignCarrier();

// FIXME: desactivado temporalmente por bug
// $this->sendConfirmationEmail($order);
```

**Impacto:**
- Merge conflicts frecuentes
- Sin forma de toggle en runtime
- Código muerto que nunca se limpia
- Historial de git lleno de "comenté/descomenté"

**Cómo estrangularlo:**
1. Implementar sistema de feature flags (configuración o servicio)
2. Usar feature flags para toggles en runtime
3. Limpiar código comentado periódicamente
4. Usar branching strategy adecuada (feature flags > comentar código)

---

## P. Testing Legacy

### 96. Integration Test as Unit Test

**Severidad:** 🟡 Medio

**Descripción:** Tests llamados "unitarios" que requieren base de datos, Redis, APIs externas y otros servicios corriendo.

**Evidencia:**
```php
// Se llama "test unitario" pero requiere:
// - Base de datos real corriendo
// - Redis corriendo
// - API externa disponible
public function testCreateOrder() {
    $order = $this->orderManager->createOrder([...]);
    $this->assertNotEmpty($order);
    // Si MySQL no está corriendo, el test falla
    // Si la API de pagos está caída, el test falla
}
```

**Impacto:**
- Tests lentos (segundos en lugar de milisegundos)
- Flaky tests por dependencias externas
- Imposible ejecutar tests sin infraestructura
- CI/CD lento y poco confiable

**Cómo estrangularlo:**
1. Usar SQLite en memoria para tests de BD
2. Mockear APIs externas con responses predefinidas
3. Separar tests unitarios (rápidos) de integration (lentos)
4. Usar test containers para tests de integración controlados

### 97. Mock Everything Syndrome

**Severidad:** 🟡 Medio

**Descripción:** Tests que mockean tantas cosas que no testean nada real, dando falsa sensación de seguridad.

**Evidencia:**
```php
// Test que mockea todo y no verifica comportamiento real
$mockDb->method('query')->willReturn([]);
$mockEmail->method('send')->willReturn(true);
$mockPayment->method('charge')->willReturn(['success' => true]);
$mockInventory->method('reserve')->willReturn(true);
// El test pasa pero no verifica nada útil
// Si el código real cambia, el test sigue pasando
```

**Impacto:**
- Tests verdes pero código roto en producción
- Falsa sensación de seguridad
- Tests que no detectan regresiones
- Código no testeado disfrazado de testeado

**Cómo estrangularlo:**
1. Mockear solo dependencias externas, no lógica interna
2. Usar tests de integración para verificar flujos completos
3. Verificar comportamiento, no solo que se llamen métodos
4. Implementar contract tests para integraciones

### 98. Golden Master Dependency

**Severidad:** 🟡 Medio

**Descripción:** Tests que dependen de un "golden master" o fixture de hace años que nadie entiende ni sabe si es correcto.

**Evidencia:**
```php
// Test compara output con archivo "golden" de hace 3 años
$expected = file_get_contents('tests/fixtures/golden_output_2021.json');
$this->assertEquals($expected, $actual);
// Nadie sabe por qué ese es el output correcto
// Si el golden master tiene bugs, el test valida bugs
```

**Impacto:**
- Tests imposibles de actualizar con confianza
- Golden master puede ser incorrecto desde el inicio
- Cambios legítimos rompen tests sin forma de validar
- Fixture crece y se vuelve inmanejable

**Cómo estrangularlo:**
1. Reemplazar golden master con aserciones específicas
2. Verificar propiedades del output en lugar de equality total
3. Crear tests que verifiquen comportamiento, no output exacto
4. Usar snapshot testing con revisión manual de cambios

---

## Q. Dominio y Negocio

### 99. Business Rules by Convention

**Severidad:** 🟠 Alto

**Descripción:** Reglas de negocio implícitas en el código, no explícitas ni documentadas, descubribles solo leyendo implementación.

**Evidencia:**
```php
// Regla de negocio: pedidos > 5000 son VIP y tienen descuento especial
// Pero no está documentada, solo existe en el código
if ($total > 5000) {
    $order->is_vip = 1;
    $order->discount = 0.05; // 5% de descuento "mágico"
}
// Si alguien cambia 5000 a 3000, rompe la regla de negocio sin saberlo
// Nadie sabe por qué es 5000, ni quién lo decidió
```

**Impacto:**
- Reglas de negocio perdidas en el código
- Cambios accidentales rompen lógica de negocio
- Imposible auditar reglas de negocio
- Nuevos desarrolladores no conocen las reglas

**Cómo estrangularlo:**
1. Extraer reglas de negocio a clases específicas (Specification pattern)
2. Documentar reglas de negocio en código y documentación externa
3. Usar nombres descriptivos para constantes y umbrales
4. Crear tests que verifiquen reglas de negocio explícitamente

### 100. Zombie Features

**Severidad:** 🟡 Medio

**Descripción:** Funcionalidades que existen en el código pero nadie usa, mantenidas "por si acaso".

**Evidencia:**
```php
// Módulo de "wishlist" que nadie ha usado en 2 años
public function addToWishlist($productId, $userId) {
    // 50 líneas de lógica...
    // Tabla wishlist en BD con 3 registros desde 2022
}
// No hay analytics que muestren uso
// Pero no se puede eliminar "por si acaso"
// Cada deploy se prueba "por si acaso"
```

**Impacto:**
- Código muerto que se mantiene y prueba
- Complejidad innecesaria
- Confusión para nuevos desarrolladores
- Costo de mantenimiento continuo

**Cómo estrangularlo:**
1. Implementar analytics para verificar uso real
2. Desactivar feature con feature flag antes de eliminar
3. Si nadie reporta ausencia en 30 días, eliminar código
4. Archivar código en git tag por si se necesita restaurar

---

## R. Legacy Socio-Technical Patterns

### 101. Fear-Driven Development

**Severidad:** 🟠 Alto

**Descripción:** Miedo a tocar código legacy que "funciona", resultando en workarounds en lugar de fixes reales.

**Evidencia:**
- "No toques ese módulo, lleva funcionando 5 años"
- PRs rechazados por "demasiado riesgo"
- Workarounds en lugar de fixes: "mejor añade otro if en lugar de cambiar la lógica"
- Código legacy rodeado de `// NO TOCAR` comments

**Impacto:**
- Deuda técnica acumulativa
- Imposibilidad de innovar o mejorar
- Código cada vez más complejo por workarounds
- Moral del equipo afectada

**Cómo estrangularlo:**
1. Añadir tests de caracterización antes de tocar código legacy
2. Refactorizar incrementalmente con seguridad de tests
3. Celebrar mejoras de código legacy, no castigar fallos
4. Usar Strangler Fig para reemplazar gradualmente

### 102. Knowledge Silos

**Severidad:** 🟠 Alto

**Descripción:** Solo una persona entiende cada módulo del sistema, creando bottlenecks y riesgo operativo.

**Evidencia:**
- "Pregunta a Carlos, él escribió eso"
- "Ese módulo solo lo entiende María"
- Documentación inexistente o desactualizada
- Carlos y María no pueden tomar vacaciones

**Impacto:**
- Bottleneck en personas específicas
- Imposibilidad de redistribuir trabajo
- Riesgo si alguien se va de la empresa
- Velocidad de desarrollo limitada

**Cómo estrangularlo:**
1. Pair programming para transferir conocimiento
2. Documentar módulos críticos (READMEs, diagrams)
3. Rotar responsabilidades entre equipo
4. Crear sesiones de "code walkthrough" regulares

### 103. Bus Factor One

**Severidad:** 🔴 Crítico

**Descripción:** Si una persona se va, el sistema colapsa porque es la única que sabe operaciones críticas.

**Evidencia:**
- Una persona es el único que sabe deployar a producción
- Una persona es el único que entiende el esquema de BD
- Una persona es el único que puede debugear ciertos errores
- "Si Juan se va, estamos jodidos"

**Impacto:**
- Riesgo existencial para el negocio
- Imposibilidad de vacaciones para esa persona
- Presión extrema sobre individuos clave
- Imposibilidad de escalar equipo

**Cómo estrangularlo:**
1. Documentar procedimientos críticos inmediatamente
2. Cross-training: cada conocimiento debe tener al menos 2 personas
3. Automatizar procesos manuales (deployments, backups)
4. Crear runbooks para operaciones comunes

### 104. Tribal Knowledge Architecture

**Severidad:** 🟠 Alto

**Descripción:** La arquitectura y decisiones de diseño existen solo en la cabeza de los veteranos, no en documentación.

**Evidencia:**
- Diagramas de arquitectura desactualizados (o inexistentes)
- "Así se hizo porque en 2018 había un bug con el servidor de emails"
- Decisiones de diseño no documentadas
- Nuevos desarrolladores toman decisiones que contradicen decisiones pasadas

**Impacto:**
- Nuevos desarrolladores toman decisiones incorrectas
- Se repiten errores del pasado
- Arquitectura inconsistente
- Pérdida de contexto cuando alguien se va

**Cómo estrangularlo:**
1. Sesiones de documentación con veteranos
2. Architecture Decision Records (ADRs) para decisiones futuras
3. Diagramas actualizados en el repositorio
4. Onboarding estructurado con contexto histórico

### 105. Ticket-Driven Architecture

**Severidad:** 🟡 Medio

**Descripción:** La arquitectura evoluciona solo vía tickets individuales, sin visión global ni plan de refactorización.

**Evidencia:**
- Cada ticket se resuelve de forma aislada
- Sin refactorización planificada
- "Si no está en el ticket, no lo toques"
- Parches sobre parches sin visión de conjunto

**Impacto:**
- Arquitectura reactiva, no proactiva
- Deuda técnica acumulativa
- Soluciones subóptimas por scope limitado
- Imposibilidad de mejoras estructurales

**Cómo estrangularlo:**
1. Incluir "tech debt" como categoría de tickets
2. Dedicar % de sprint a mejoras arquitectónicas
3. Crear roadmap técnico junto a roadmap de producto
4. Revisar arquitectura periódicamente

### 106. Copy-Paste Onboarding

**Severidad:** 🟡 Medio

**Descripción:** Onboarding de nuevos desarrolladores vía "copia lo que hizo el anterior" en lugar de setup automatizado.

**Evidencia:**
- "Para configurar tu entorno, copia la carpeta de Juan"
- Sin setup automatizado (Makefile, docker-compose up)
- Documentación de setup desactualizada
- "Si te da error X, haz Y" (conocimiento tribal)

**Impacto:**
- Onboarding de semanas en lugar de horas
- Entornos de desarrollo inconsistentes
- "Funciona en mi máquina" desde el día 1
- Dependencia de personas para setup

**Cómo estrangularlo:**
1. Crear setup automatizado (docker-compose, Makefile)
2. Documentar setup paso a paso y verificarlo con nuevos devs
3. Un comando para tener el entorno funcionando
4. CI que verifique que el setup funciona desde cero

---

## Mapa de Relaciones entre Antipatrones

```
┌─────────────────────────────────────────────────────────────────┐
│                        GOD CLASS                                │
│                    (10,000+ líneas)                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ God Method   │  │ Mutable      │  │ Presentation Mixed   │  │
│  │ (100+ cases) │──│ Shared State │──│ with Domain          │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│         │                   │                      │            │
│         ▼                   ▼                      ▼            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ Action-Based │  │ Temporal     │  │ Server-Rendered HTML │  │
│  │ Routing      │──│ Coupling     │──│ over AJAX            │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│         │                   │                      │            │
│         ▼                   ▼                      ▼            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ Primitive    │  │ Hidden Side  │  │ Output Format        │  │
│  │ Obsession    │──│ Effects      │──│ Confusion            │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│         │                                                    │
│         ▼                                                    │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │  stdClass + Array-Based Modeling + Anemic Domain Model   │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  SQL Injection + Security Anti-Patterns + Silent Catch   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Wide Tables + Repeating Groups + DOUBLE for Money       │  │
│  │  + Missing FKs + Cross-Schema Queries                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Resumen por Conteo

| Categoría | # Antipatrones | Rango |
|---|---|---|
| A. Estructura y Arquitectura | 8 | 1-8 |
| B. Estado y Acoplamiento | 8 | 9-16 |
| C. Modelado de Datos (PHP) | 7 | 17-23 |
| D. Seguridad | 3 | 24-26 |
| E. Manejo de Errores | 3 | 27-29 |
| F. Código y Mantenibilidad | 5 | 30-34 |
| G. Presentación y Comunicación | 4 | 35-38 |
| H. Database Design | 27 | 39-65 |
| I. Naming | 12 | 66-77 |
| J. Integraciones y APIs | 4 | 78-81 |
| K. Concurrencia y Consistencia | 3 | 82-84 |
| L. Performance y Escalabilidad | 3 | 85-87 |
| M. Observabilidad y Operación | 3 | 88-90 |
| N. Framework Casero Legacy | 2 | 91-92 |
| O. Deployment y Entornos | 3 | 93-95 |
| P. Testing Legacy | 3 | 96-98 |
| Q. Dominio y Negocio | 2 | 99-100 |
| R. Legacy Socio-Technical | 6 | 101-106 |
| **TOTAL** | **106** | |

---

## Referencias

- **Strangler Fig Pattern:** Martin Fowler - https://martinfowler.com/bliki/StranglerFigApplication.html
- **God Class:** Code Smells - https://refactoring.guru/smells/god-class
- **Primitive Obsession:** Martin Fowler - https://martinfowler.com/bliki/PrimitiveObsession.html
- **Anemic Domain Model:** Martin Fowler - https://martinfowler.com/bliki/AnemicDomainModel.html
- **Silent Catch:** CWE-391 - https://cwe.mitre.org/data/definitions/391.html
- **SQL Injection:** OWASP - https://owasp.org/www-community/attacks/SQL_Injection
- **Repeating Groups (1NF):** Database Normalization - https://en.wikipedia.org/wiki/First_normal_form
- **DOUBLE for Money:** Floating Point Guide - https://floating-point-gui.de/
