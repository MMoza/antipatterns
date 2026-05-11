# Anti-Patterns Repository

Catalogo de antipatrones de diseño en codigo legacy y estrategias para estrangularlos.

Basado en el analisis real de un sistema de gestion hotelera (PMS) con mas de 26,000 lineas en una sola clase PHP. Los ejemplos de codigo son generico y estan inspirados en la realidad, pero el codigo real **no** sera publicado.

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
