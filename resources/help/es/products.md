# Productos

El módulo de Productos centraliza el catálogo de artículos que tu empresa vende. Cada producto puede tener múltiples presentaciones, precios y códigos de barra.

## Conceptos clave

### Producto

Un producto es el artículo base del catálogo. Tiene los siguientes atributos:

- **Nombre**: nombre descriptivo del producto (ej: "Aceite de Girasol").
- **Descripción**: texto opcional con información adicional.
- **Tipo de producto**: categoría a la que pertenece (ej: Aceites, Bebidas, Limpieza).
- **Moneda**: moneda en la que se expresa el precio.
- **Estado**: activo o inactivo. Los productos inactivos no aparecen en la búsqueda al crear ventas.
- **Códigos de barra**: uno o múltiples códigos EAN/QR para escanear el producto.

### Presentación

Una presentación define **cómo se vende o mide** ese producto. Cada producto puede tener varias presentaciones, por ejemplo:

- "1 kg" con precio $500 y stock 100 unidades
- "500 g" con precio $280 y stock 50 unidades
- "1 unidad" con precio $120 y stock 200 unidades

Cada presentación tiene:

- **Tipo de presentación**: la unidad de medida (ej: kg, g, litro, unidad).
- **Cantidad**: cuántas unidades del tipo contiene (ej: 1, 500, 2).
- **Precio de venta**: precio de lista de esta presentación.
- **Stock actual**: cantidad disponible actualmente.
- **Stock mínimo**: umbral de alerta de stock bajo.

## Lista de productos

Al ingresar a **Productos** verás una tabla con:

- Nombre y código del producto
- Tipo de producto
- Estado (activo/inactivo) con badge de color
- Stock total (suma de presentaciones)
- Acciones: editar, ver detalle, activar/desactivar, eliminar

### Búsqueda

Podés buscar productos por **nombre**, **código** o **código de barra**. El buscador filtra en tiempo real.

### Filtrar por estado

Usá el selector de estado para ver solo productos activos, inactivos o todos.

## Crear un producto

1. Hacé clic en **Nuevo Producto**.
2. Completá el nombre, descripción (opcional) y seleccioná el tipo de producto.
3. Elegí la moneda del producto.
4. Agregá al menos una **presentación**: seleccioná tipo, ingresá cantidad, precio y stock.
5. Opcionalmente agregá más presentaciones con el botón **+ Agregar presentación**.
6. Opcionalmente ingresá códigos de barra.
7. Hacé clic en **Guardar**.

## Editar un producto

Hacé clic en el ícono de lápiz en la fila del producto o en el botón Editar del detalle. Podés modificar todos los campos incluyendo presentaciones existentes o agregar nuevas.

> **Importante**: Modificar el precio de una presentación no afecta ventas ya registradas.

## Activar / Desactivar producto

Usá el toggle de estado para activar o desactivar un producto sin eliminarlo. Los productos inactivos:

- No aparecen en búsquedas al crear ventas, pedidos o presupuestos.
- Siguen siendo visibles en el módulo de productos para gestión interna.
- Conservan su historial de movimientos.

## Eliminar un producto

Para eliminar un producto, hacé clic en el ícono de papelera. El sistema pedirá confirmación. No podés eliminar un producto que tenga ventas, pedidos o movimientos de stock asociados.

## Gestión de stock

El stock de cada presentación se actualiza automáticamente cuando:

- **Ingresás una recepción** (aumenta el stock).
- **Registrás una venta** (disminuye el stock).
- **Confirmás un pedido como entregado** (disminuye el stock).
- **Registrás un movimiento extra** de ajuste, pérdida o corrección.

Podés consultar el stock actual en el detalle del producto o en el reporte de Inventario.

## Importación masiva

Podés importar productos desde un archivo Excel (XLSX). El archivo debe tener el formato estándar de In-ventra. Accedé a esta función desde el menú de Productos → **Importar XLSX**.

## Consejos

> **Tip**: Definí el stock mínimo para cada presentación así el Dashboard te alerta cuando hay que reponer.

> **Tip**: Usá los tipos de producto para organizar el catálogo y facilitar filtros en reportes.
