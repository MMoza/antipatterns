# Anti-Patterns Repository

Catalogo de antipatrones de diseño en codigo legacy y estrategias para estrangularlos.

Basado en el analisis de multiples codebases PHP legacy de e-commerce. Los ejemplos de codigo son genericos y estan inspirados en patrones reales observados en la industria, pero todo el codigo es inventado y no pertenece a ningun sistema real.

## Estructura

Cada grupo tematico contiene:

- `antipattern/` - Codigo que demuestra el antipatron
- `solution/` - Codigo refactorizado con buenas practicas
- `README.md` - Documentacion del patron y la solucion

## Instalacion

```bash
composer install
```

## Ejecutar Tests

```bash
vendor/bin/phpunit
```

## Ejecutar Demos

```bash
php examples/01-god-class.php
```

## Documentacion Completa

Ver [ANTIPATRONES.md](ANTIPATRONES.md) para la documentacion de los 77 antipatrones identificados.

## Estructura del Proyecto

Ver [PROJECT-STRUCTURE.md](PROJECT-STRUCTURE.md) para detalles de la arquitectura del repo.
