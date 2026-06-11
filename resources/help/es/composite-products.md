# Productos Compuestos

Los productos compuestos (también llamados **kits**) son agrupaciones de productos simples que se venden juntos como una unidad. Son ideales para armar combos, packs o sets de productos relacionados.

## ¿Qué es un producto compuesto?

Un producto compuesto es un conjunto de productos del catálogo que se vende como un solo ítem. Ejemplo:

**"Kit Limpieza Hogar"** compuesto por:
- 1x Detergente 1 litro
- 2x Esponja de cocina
- 1x Lavandina 500 ml

Al vender este kit, el sistema descuenta automáticamente el stock de cada componente.

## Diferencias con productos simples

| Característica | Producto simple | Producto compuesto |
|---|---|---|
| Precio propio | Sí | No (derivado de componentes) |
| Stock propio | Sí | No (disponibilidad según componentes) |
| Componentes | No | Sí (lista de productos con cantidades) |
| Venta en ventas/pedidos | Sí | Sí |

## Atributos del producto compuesto

- **Nombre**: nombre del kit o combo (ej: "Starter Kit Premium").
- **Código**: código interno opcional para identificación rápida.
- **Estado**: activo o inactivo. Los inactivos no aparecen en ventas.
- **Ítems**: lista de productos que componen el kit, cada uno con:
  - Producto seleccionado
  - Presentación del producto
  - Cantidad requerida

## Crear un producto compuesto

1. Navegá a **Productos → Productos Compuestos**.
2. Hacé clic en **Nuevo Producto Compuesto**.
3. Ingresá el nombre del kit y opcionalmente un código.
4. Hacé clic en **+ Agregar componente**.
5. Buscá y seleccioná el producto, elegí la presentación y la cantidad.
6. Repetí para cada componente del kit.
7. Hacé clic en **Guardar**.

## Editar un producto compuesto

Podés modificar el nombre, código, estado y la lista de componentes. Cambiar los componentes no afecta ventas ya registradas.

## Disponibilidad y precio

- **Precio**: el sistema calcula automáticamente el precio del kit como la suma de los precios de sus componentes por cantidad.
- **Disponibilidad**: el kit está disponible para venta solo si todos sus componentes tienen stock suficiente. Si algún componente se agota, el kit queda sin stock disponible.

## Uso en ventas y pedidos

Al crear una venta o un pedido, podés buscar el kit por nombre o código. El sistema lo agrega como un solo ítem, pero al confirmar la venta registra los movimientos de stock de cada componente individualmente.

## Activar / Desactivar

Igual que los productos simples, podés activar o desactivar un kit sin eliminarlo. Los kits inactivos no aparecen en el selector de productos al crear ventas.

## Eliminar un producto compuesto

Solo se puede eliminar si no tiene ventas ni pedidos asociados. El sistema pedirá confirmación antes de eliminar.

## Consejos

> **Tip**: Usá productos compuestos para crear combos promocionales de temporada sin necesidad de crear un nuevo producto simple.

> **Tip**: Si un kit tiene componentes con stock bajo, aparecerá en el Dashboard como kit con disponibilidad limitada.
