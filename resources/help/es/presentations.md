# Presentaciones y Tipos de Presentación

Las presentaciones definen **cómo se mide y vende** cada producto. Este módulo gestiona las unidades de medida disponibles en el sistema.

## Tipos de Presentación

Un **Tipo de Presentación** es la categoría o unidad de medida base. Ejemplos:

| Tipo de Presentación | Abreviación | Descripción |
|---|---|---|
| Kilogramo | kg | Unidad de peso |
| Gramo | g | Unidad de peso (menor) |
| Litro | l | Unidad de volumen |
| Mililitro | ml | Unidad de volumen (menor) |
| Unidad | u | Ítem individual |
| Metro | m | Unidad de longitud |

### Crear un tipo de presentación

1. Navegá a **Configuración → Tipos de Presentación**.
2. Hacé clic en **Nuevo Tipo de Presentación**.
3. Ingresá el nombre (ej: "Kilogramo") y la abreviación (ej: "kg").
4. Hacé clic en **Guardar**.

## Presentaciones

Una **Presentación** combina un tipo de presentación con una cantidad específica. Por ejemplo:

| Presentación | Tipo | Cantidad | Descripción |
|---|---|---|---|
| 1 kg | Kilogramo | 1 | Un kilogramo |
| 500 g | Gramo | 500 | Quinientos gramos |
| 1 litro | Litro | 1 | Un litro |
| 6 unidades | Unidad | 6 | Pack de seis |

Las presentaciones son globales y reutilizables: se crean una vez y se asignan a múltiples productos.

### Crear una presentación

1. Navegá a **Configuración → Presentaciones**.
2. Hacé clic en **Nueva Presentación**.
3. Seleccioná el **Tipo de presentación** (ej: Kilogramo).
4. Ingresá la **cantidad** (ej: 1 para "1 kg", o 500 para "500 g").
5. Hacé clic en **Guardar**.

La presentación quedará disponible para asignar a productos.

## Relación con Productos

Al crear o editar un producto, en la sección de presentaciones:

1. Seleccioná una presentación del listado global (ej: "1 kg").
2. Definí el **precio de venta** para esa presentación de ese producto.
3. Definí el **stock actual** y el **stock mínimo**.

Un mismo producto puede tener múltiples presentaciones con diferentes precios y stocks. Por ejemplo, "Arroz" puede tener:
- Presentación "500 g" → precio $150, stock 80
- Presentación "1 kg" → precio $280, stock 120
- Presentación "5 kg" → precio $1.200, stock 30

## Editar y eliminar

- **Editar**: podés cambiar el nombre del tipo o la cantidad de la presentación en cualquier momento.
- **Eliminar**: solo si no está asignada a ningún producto activo.

## Consejos

> **Tip**: Definí primero todos los tipos de presentación y las presentaciones comunes antes de empezar a cargar el catálogo de productos.

> **Tip**: Una presentación bien definida ("500 g", "1 kg", "2 litros") facilita la búsqueda por escáner de código de barras ya que podés asignar un código específico por presentación.

> **Tip**: Para productos que se venden en unidades simples, usá el tipo "Unidad" con cantidad 1.
