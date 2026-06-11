# Promociones

El módulo de Promociones permite crear ofertas especiales que agrupan varios productos a un precio combinado diferente al precio de lista. Son similares a los productos compuestos, pero con la posibilidad de definir un precio de venta personalizado.

## ¿Qué es una promoción?

Una promoción es una agrupación de productos con un **precio especial de venta**. A diferencia de los kits (productos compuestos) cuyo precio se calcula automáticamente a partir de los componentes, en una promoción podés establecer un precio de venta propio que no necesariamente coincide con la suma de los componentes.

Ejemplo:

**"Promo 2x1 Shampoo"**
- 2x Shampoo 400ml (precio normal: $300 cada uno = $600)
- Precio de la promoción: $450

## Atributos de la promoción

- **Nombre**: nombre descriptivo de la promoción (ej: "Pack Verano").
- **Código**: código interno opcional.
- **Precio de venta**: precio especial que se aplica al vender esta promoción. Si se deja vacío, el precio se calcula como la suma de los componentes.
- **Estado**: activo o inactivo.
- **Ítems**: lista de productos con sus presentaciones y cantidades.

## Diferencia con productos compuestos

| Característica | Producto Compuesto (Kit) | Promoción |
|---|---|---|
| Precio | Calculado automáticamente | Configurable manualmente |
| Propósito | Bundles permanentes | Ofertas especiales con precio distinto |
| Vigencia | Sin fecha límite | Puede activarse/desactivarse según temporada |

## Crear una promoción

1. Navegá a **Productos → Promociones**.
2. Hacé clic en **Nueva Promoción**.
3. Ingresá el nombre y opcionalmente un código.
4. Definí el precio de venta especial (opcional).
5. Hacé clic en **+ Agregar producto** y seleccioná los productos con sus presentaciones y cantidades.
6. Revisá el resumen de componentes y precio.
7. Hacé clic en **Guardar**.

## Editar una promoción

Podés modificar cualquier atributo de la promoción en cualquier momento. Activá o desactivá la promoción según la temporada o campaña.

## Uso en ventas y pedidos

Al crear una venta o pedido, buscá la promoción por nombre o código. El sistema la agrega como un ítem único con el precio configurado. Al confirmar, descuenta el stock de cada componente.

## Disponibilidad de stock

La promoción solo está disponible si todos sus componentes tienen stock suficiente para la cantidad definida en la promoción.

## Activar / Desactivar

Usá el toggle de estado para activar o desactivar una promoción sin eliminarla. Esto es útil para promociones de temporada que se repiten periódicamente.

## Consejos

> **Tip**: Creá una promoción con precio especial para liquidar stock de productos con rotación lenta.

> **Tip**: Activá y desactivá promociones por temporada (Día de la Madre, Navidad, etc.) sin necesidad de recrearlas cada vez.

> **Tip**: Combiná con el módulo de Presupuestos para ofrecer la promoción antes de confirmar la venta.
