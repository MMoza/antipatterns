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

---

## A. Estructura y Arquitectura

### 1. God Class / Clase Dios

**Severidad:** 🔴 Crítico

**Descripción:** Una clase que hace demasiadas cosas, violando el Principio de Responsabilidad Única (SRP).

**Evidencia:**
- Archivo PHP de **10,000+ líneas** en una sola clase
- Gestiona: planning, reservas, facturación, clientes, participantes, limpieza, mantenimiento, cerraduras, presupuestos, emails, checkin, parte de viajeros, fusión de clientes, operadores, etc.
- **100+ acciones** diferentes en un único switch

**Impacto:**
- Imposible de testear unitariamente
- Cualquier cambio tiene riesgo de romper funcionalidad no relacionada
- Onboarding de nuevos desarrolladores: semanas
- Merge conflicts constantes en equipo

**Cómo estrangularlo:**
- Identificar bounded contexts (Reservas, Facturación, Clientes, etc.)
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
- Llama a `parent::__construct()` que a su vez hace `setValoresPost()`
- `setValoresPost()` carga alojamiento, configura fechas, calcula días máximos, etc.
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
FROM tienda_db.datos_reserva_habitacion
FROM servicios_db.chk_reserva

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
- Propiedades como `$this->id_elemento`, `$this->fecha_inicio`, `$this->alojamiento` se leen y escriben por decenas de métodos
- `$this->ValoresPost_JSON_obj` se modifica en `setValoresPost()` y se lee en todos los métodos
- `$this->Plantilla` se crea en `setPlantilla()` y se usa en todos los métodos que renderizan

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
- `setValoresPost()` modifica `$this->n_dias_max` que afecta a `mostrarPlanning()`
- `cargarAlojamiento()` setea `$this->soloCloud` que se usa en queries de `getReservas()`
- `setPlantilla()` asigna variables al template que se usan en HTML que se envía al JS

**Impacto:**
- Bugs difíciles de rastrear
- Efecto mariposa: cambio pequeño → bug grande en lugar inesperado

---

### 11. Temporal Coupling / Acoplamiento Temporal

**Severidad:** 🟠 Alto

**Descripción:** Los métodos deben llamarse en un orden específico pero no hay forma de saberlo.

**Evidencia:**
- Debes llamar a `cargarAlojamiento()` antes de cualquier método que use `$this->alojamiento`
- `setPlantilla()` debe llamarse antes de cualquier método que use `$this->Plantilla`
- `setValoresPost()` debe ejecutarse antes de leer cualquier `$this->ValoresPost_JSON_obj->request->*`

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
- `getReservas()` puede hacer UPDATE para asignar colores aleatorios a reservas
- Métodos `verX()` que supuestamente solo muestran, pero pueden triggerar cálculos

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
- IDs como `int` en lugar de `ReservaId`, `HabitacionId`, `CasaId`
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
$resulta['reservas'][$r->id_reserva]['datos'] = new stdClass();
$resulta['reservas'][$r->id_reserva]['p'][] = $r;
$resulta['reservas'][$r->id_reserva]['d'][$r->id_habitacion_base][$r->fecha] = $r;
```
- Arrays multidimensionales con claves mágicas: `'datos'`, `'p'`, `'d'`, `'drh'`

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
$this->alojamiento = new stdClass();
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
private $multiplaning=0;
private $vista_hotel=0;
private $facturacion_casa=0;

if($this->ValoresPost_JSON_obj->request->ver_limpieza==1){
if($this->ValoresPost_JSON_obj->request->ver_mantenimiento==1){
```

**Impacto:**
- Combinatoria explosiva de flags
- No queda claro qué significa `1` vs `0` vs `null`

---

### 23. Data Clumps

**Severidad:** 🟡 Medio

**Descripción:** Grupos de datos que siempre viajan juntos pero no están encapsulados.

**Evidencia:**
- `$id_habitacion`, `$id_habitacion_base`, `$fecha` aparecen juntos en decenas de métodos
- `id_elemento`, `tipo_elemento` siempre juntos
- `fecha_inicio`, `fecha_fin`, `n_dias_max` como grupo

---

## D. Seguridad

### 24. SQL Injection

**Severidad:** 🔴 Crítico

**Descripción:** Variables interpoladas directamente en queries SQL sin preparación ni escaping.

**Evidencia:**
```php
$QAux = "SELECT * FROM tienda_db.casa AS c WHERE c.id_casa=$this->id_elemento";

$QAux = "SELECT * FROM tienda_db.ofe_ofertas
         WHERE id_casa=$this->id_elemento
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
    $this->reserva_operador = $this->obtenerReservaOperador($this->id_reserva);
} catch(Exception $ex) {
    // Nada - la excepción se silencia
}

// Mismo patrón repetido para cerraduras, contadores, chekin:
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
// Bloque SQL de 30 líneas comentado con "YA NO SE USA RESERVA_PARTICULAR"
/* ---- YA NO SE USA RESERVA_PARTICULAR ... */

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
- Métodos: `mostrarPlanning`, `getReservas`, `cargarAlojamiento`, `setPlantilla`
- Variables: `$fecha_inicio`, `$width_planning`, `$n_dias_max`, `$vista_hotel`
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
$n_dias_max = 36;
$this->width_planning -= 30;
($this->width_planning * 0.90)
```

---

### 33. DRY Violations / Violaciones DRY

**Severidad:** 🟠 Alto

**Descripción:** Código duplicado en múltiples lugares.

**Evidencia:**
- `getReservas()` y `getReservas_VistaSemanal()` con lógica casi idéntica
- Bloques de limpieza/mantenimiento duplicados
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
    $this->Plantilla->fetch('modulos/.../planning.html')
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
    case 1:   $resulta = $this->mostrarPlanning(); break;
    case 2:   $resulta = $this->getReservas(); break;
    case 3:   $resulta = $this->obtenerDatosReserva(); break;
    // ... hasta case 288
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
$retorno['planning'] = utf8_encode_Int($this->IdiomaInterfaz->traducirTPL(
    $this->Plantilla->fetch('modulos/.../planning.html')
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
-- Columna `tipo` en datos_reserva_habitacion:
WHERE drh.tipo = 0    -- 0 = habitación/reserva
WHERE drh.tipo = 1    -- 1 = bloqueo
WHERE drh.tipo = 2    -- 2 = servicios del día
WHERE drh.tipo = 3    -- 3 = servicios extraordinarios
WHERE drh.tipo = 4    -- 4 = extras
WHERE drh.tipo = 5    -- 5 = desayuno
WHERE drh.tipo = 6    -- 6 = media pensión
WHERE drh.tipo = 7    -- 7 = pensión completa

-- Columna `chk_tipo` en servicios_db.chk_reserva:
chk_tipo = 2    -- marca de "vista"
chk_tipo = 3    -- estado checkin
chk_tipo = 4    -- estado checkout
chk_tipo = 5    -- estado bloqueo
chk_tipo = 6    -- estado facturada
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
FROM tienda_db.datos_reserva_habitacion drh
INNER JOIN tienda_db.reserva r USING(id_casa,id_reserva)
INNER JOIN tienda_db.reserva_particular ro ON (...)
LEFT JOIN servicios_db.cmp_comportamiento AS cmp USING (id_comportamiento)
LEFT JOIN servicios_db.chk_reserva chk ON (...)
LEFT JOIN extras_db.exp_textos AS t1 ON (...)
```

**Esquemas involucrados:**
| Esquema | Propósito |
|---|---|
| `tienda_db` | Sistema legacy de reservas |
| `servicios_db` | Motor de servicios, checkins, facturación |
| `extras_db` | Módulo de extras_db/extras |

**Impacto:**
- Migrar un esquema requiere reescribir todas las queries
- Sin transacciones cross-schema garantizadas

---

### 41. Duplicate Table Pairs (Operator vs Particular)

**Severidad:** 🟠 Alto

**Descripción:** Dos sistemas paralelos de reservas con estructuras casi idénticas.

**Evidencia:**
| Sistema Operador | Sistema Particular |
|---|---|
| `reserva_operador` (~130 cols) | `reserva_particular` (~70 cols) |
| `conceptos_operador` | `reserva_particular_conceptos` |

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
| ID alojamiento | `id_casa` | `id_elemento` |
| ID reserva | `id_reserva_original` | `id_reserva` |
| Tipo de elemento | N/A | `tipo_elemento` |
| Tipo de check | N/A | `chk_tipo` |

**Impacto:**
- JOINs confusos
- `USING()` solo funciona cuando los nombres coinciden

---

### 43. Redundant / Duplicated Columns

**Severidad:** 🟡 Medio

**Evidencia:**
- `id_reserva` vs `id_reserva_original` — doble join necesario en cada lookup
- `cliente` vs `cliente_copia` — datos duplicados al momento de reservar
- `id_casa` aparece en casi TODAS las tablas como foreign key redundante
- `fecha` vs `fecha_habitacion` — semántica solapada

---

### 44. JSON / Serialized Data in Columns

**Severidad:** 🟡 Medio

**Evidencia:**
```
par_participante.datos_extra          → JSON participant data
fac_factura.extra                     → JSON invoice extras (con stripslashes!)
fac_config_elemento.data              → JSON e-invoicing config
reserva_operador.cod_aplicado         → pipe-delimited: "tipo|valor"
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
// INSERT uno a uno por noche en lugar de bulk INSERT
foreach ($noches as $noche) {
    $QAux = "INSERT INTO datos_reserva_habitacion (...) VALUES (...)";
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
- `LIKE '%-$id_habitacion_base'` — wildcard inicial impide uso de índices
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
hab_borrada = 0          -- soft delete de habitación
hab_base_borrada = 0     -- soft delete de habitación base
estado = 0               -- en reserva_ko = "pendiente"
estado = 1               -- en chk_reserva = "activo"
activo = 1               -- en ota_datoscasa_habitaciones
```
Inconsistencia: algunos usan 0=activo, otros 1=activo.

---

### 51. Hardcoded Business Logic in SQL

**Severidad:** 🟡 Medio

**Evidencia:**
```sql
WHERE fecha_fin_planning >= '2020-01-01'
WHERE id_elemento IN (100, 200, 300)
WHERE id_elemento != 300
```

---

### 52. Polymorphic Discriminator Columns

**Severidad:** 🟠 Alto

**Descripción:** `tipo_elemento` usado como discriminador polimórfico en casi todas las tablas de `servicios_db`.

**Evidencia:**
```sql
WHERE tipo_elemento = 1    -- 1 = casa/alojamiento
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
WHERE concepto LIKE '%-$id_habitacion_base'
WHERE nombre_completo LIKE '%$name%'
WHERE concepto NOT LIKE 'pendiente_%'
```
Wildcards iniciales = full table scan inevitable.

---

### 54. Wide Table / Tabla Ancha

**Severidad:** 🔴 Crítico

`reserva_operador`: **~130 columnas**
`reserva_particular`: **~70 columnas**

Tablas tan anchas que son imposibles de entender de un vistazo. Mezclan:
- Datos de la reserva
- Datos del cliente (duplicados de tabla `cliente`)
- Configuración de emails
- Configuración de facturas
- Configuración de comisiones
- Estado de cobros
- Bonos
- Códigos de agencia

**Cómo estrangularlo:** Extraer grupos conceptuales a tablas hijas:
- `reserva_cliente_info`
- `reserva_email_config`
- `reserva_factura_config`
- `reserva_comision`
- `reserva_cobro_status`

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
CREATE TABLE reserva_factura_envios (
    id_reserva INT,
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
`precio` double,                    -- datos_reserva_habitacion
`importe_reserva_original` double,  -- reserva_operador
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
- Nombres con caracteres especiales de turistas extranjeros

Esto explica la proliferación de `utf8_encode_Int()`, `utf8_decode_Int()`, y `mb_convert_encoding()` por todo el código PHP.

---

### 60. Over-Indexing

**Severidad:** 🟡 Medio

`datos_reserva_habitacion` tiene 9 índices para una tabla con un PK compuesto de 5 columnas:

```sql
PRIMARY KEY (id_casa, id_habitacion, id_habitacion_base, fecha, tipo)
KEY FK_datos_reserva_2 (id_habitacion, id_habitacion_base,id_casa)     -- redundante con PK
KEY IX_datos_reserva_habitacion_2 (id_habitacion, id_habitacion_base,id_casa)  -- ¡duplicado!
KEY IX_datos_reserva_habitacion_3 (id_habitacion_base)                   -- prefijo del PK
KEY IX_datos_reserva_habitacion_4 (fecha)                                -- prefijo del PK
KEY IX_fecha_base (id_casa, fecha, id_habitacion_base)                   -- solapado
```

Cada INSERT/UPDATE paga el coste de mantener 9 índices.

---

### 61. Missing Foreign Keys

**Severidad:** 🟠 Alto

| Columna | Debería referenciar | FK existe |
|---|---|---|
| `reserva_operador.id_reserva_original` | `reserva.id_reserva` | ❌ |
| `reserva_operador.id_cliente` | `cliente_copia.id_cliente` | ❌ |
| `reserva_particular.id_reserva_original` | `reserva.id_reserva` | ❌ |
| `reserva_particular.id_cliente` | `cliente.id_cliente` | ❌ |
| `chk_reserva.id_reserva` | `reserva.id_reserva` | ❌ |
| `chk_reserva.id_usuario` | `auth_usuario.id_usuario` | ❌ |

Solo `datos_reserva_habitacion` tiene una FK.

---

### 62. Trigger-Based Denormalization

**Severidad:** 🟠 Alto

3 triggers en `reserva_operador` mantienen `reserva_cache`:
- `reserva_cache` (AFTER INSERT)
- `reserva_cache_update` (AFTER UPDATE)
- `reserva_cache_delete` (AFTER DELETE)

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
- Nombre de habitación: `"Habitación Doble"`
- Flags de tipo: `"pendiente_confirmacion"`
- Referencias: `"123-456"` (id_habitacion_base)
- Servicios: `"desayuno incluido"`

El PHP hace `LIKE '%-$id_habitacion_base'` y `LIKE 'pendiente_%'` sobre esta columna.

---

### 64. Composite Primary Key Excesivo

**Severidad:** 🟡 Medio

```sql
-- chk_reserva: PK de 7 columnas
PRIMARY KEY (id_habitacion, id_habitacion_base, fecha_habitacion,
             tipo_elemento, id_elemento, id_reserva, chk_tipo)
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
$width_planning          // inglés + inglés
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
$drh     // ¿datos_reserva_habitacion? ¿derecho?
$ro      // ¿reserva_operador? ¿rollo?
$QAux    // ¿Query Auxiliary?
$ffrr    // ¿fac_factura_rel_reserva?
$pprr    // ¿par_participante_rel_reserva?
$chk     // ¿check? ¿checkpoint?
$pdif    // ¿pago_diferido?
$llave2  // ¿llave? ¿por qué 2?
```

---

### 69. Inconsistent Verb Conventions

**Severidad:** 🟡 Medio

**Evidencia:** Cuatro formas de nombrar métodos que "obtienen" datos:
```
getX()         → getReservas(), getInitialInterface()
obtenerX()     → obtenerDatosReserva(), obtenerReservaOperador()
verX()         → verParticipante(), verElementoEvnt()
mostrarX()     → mostrarPlanning(), mostrarNuevaReserva()
```

¿Cuál es la diferencia semántica entre `getReservas()`, `obtenerDatosReserva()`, `verReservasCamping()` y `mostrarPlanning()`? **Ninguna clara.**

---

### 70. Hungarian Notation Variants

**Severidad:** 🟡 Medio

**Evidencia:**
```
$id_reserva           // tipo implícito (int)
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
tienda_db.casa              → "casa" (español)
servicios_db.cfg_configuracion   → "configuracion" con prefijo "cfg_"
tienda_db.reserva_operador  → sin prefijo
servicios_db.chk_reserva         → con prefijo "chk_"
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
casa           → singular
reserva        → singular
ofe_ofertas    → plural
paises_casa    → plural + singular
datos_reserva_habitacion → singular
conceptos_operador → plural + singular
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
Acción 1   = mostrarPlanning
Acción 2   = getReservas
Acción 3   = obtenerDatosReserva
...
Acción 147 = buscarReservasDeCliente
Acción 288 = getCodigoPostalByMunicipio
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
`central_a`      -- ¿a = alojamiento?
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
`id_reserva_original` int COMMENT 'Relcaionado con reserva.id_reserva'
-- "Relcaionado" → "Relacionado"
```

Indicativo de documentación descuidada.

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
│  │ (288 cases)  │──│ Shared State │──│ with Domain          │  │
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

## Priorización para Estrangulamiento

### Fase 1: Seguridad (Inmediato)
1. SQL Injection → Prepared statements
2. SSL Verification Disabled → Activar verificación
3. Security Anti-Patterns → Hash seguro, validación de inputs

### Fase 2: Separación de Responsabilidades
4. God Class → Extraer bounded contexts
5. Presentation Mixed with Domain → Separar API de templates
6. Action-Based Routing → Endpoints semánticos

### Fase 3: Modelado de Dominio
7. Primitive Obsession → Value objects
8. stdClass como Modelo Universal → DTOs tipados
9. Array-Based Domain Modeling → Objetos con comportamiento

### Fase 4: Estado y Acoplamiento
10. Mutable Shared State → Inmutabilidad
11. Temporal Coupling → Validación de precondiciones
12. Hidden Side Effects → CQS

### Fase 5: Database Refactoring
13. Wide Tables → Extraer tablas hijas
14. Repeating Groups → Normalizar a 1NF
15. DOUBLE/VARCHAR for Money → DECIMAL
16. Missing FKs → Añadir constraints gradualmente
17. Cross-Schema Queries → Capa de abstracción

### Fase 6: Mantenibilidad
18. Dead Code → Eliminar
19. DRY Violations → Extraer métodos compartidos
20. Mixed Language Naming → Estandarizar
21. Magic Numbers → Constantes con nombre

---

## Estrategias de Estrangulamiento

### Strangler Fig Pattern
1. Identificar un bounded context pequeño
2. Crear nuevo servicio/controller para ese contexto
3. Redirigir las acciones correspondientes al nuevo servicio
4. Repetir hasta que la God Class quede como facade vacío
5. Eliminar la God Class

### Parallel Implementation
1. Implementar nueva arquitectura en paralelo
2. Usar feature flags para cambiar entre viejo y nuevo
3. Migrar gradualmente funcionalidad
4. Eliminar código legacy cuando todo esté migrado

### Anti-Corruption Layer
1. Crear capa de adaptación entre legacy y nuevo código
2. El ACL traduce entre modelos antiguos y nuevos
3. Permite que el nuevo código tenga diseño limpio
4. El legacy se deprecia gradualmente

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
| **TOTAL** | **77** | |

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
