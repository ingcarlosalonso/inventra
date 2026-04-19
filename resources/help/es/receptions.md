# Ingreso de Stock (Recepciones)

El módulo de Recepciones registra la entrada de mercadería al depósito. Cada recepción representa una compra o ingreso de productos desde un proveedor, y actualiza automáticamente el stock disponible.

## ¿Qué es una recepción?

Una recepción es el registro de compra de productos a un proveedor. Al confirmarla:

1. **Aumenta el stock** de cada presentación recibida.
2. **Registra el gasto** en la caja diaria vinculada (si se selecciona una caja).

## Atributos de una recepción

**Cabecera:**
- **Proveedor**: quién suministra la mercadería (requerido).
- **Número de factura**: número de factura o remito del proveedor (opcional, para referencia).
- **Fecha de recepción**: fecha en que se recibió la mercadería.
- **Notas**: observaciones internas sobre la entrega.
- **Caja diaria vinculada**: si el pago al proveedor se registra en una caja abierta, se selecciona aquí.

**Ítems:**
- **Producto y presentación**: qué se recibió.
- **Cantidad**: cuántas unidades ingresaron.
- **Costo unitario**: precio de compra por unidad.
- **Subtotal**: calculado automáticamente (cantidad × costo unitario).

## Crear una recepción

1. Navegá a **Ingreso de Stock** en la barra lateral.
2. Hacé clic en **Nueva Recepción**.
3. Seleccioná el **proveedor** de la lista.
4. Completá el número de factura y la fecha (opcional).
5. Si querés registrar el gasto en caja, seleccioná la **caja diaria** abierta.
6. Hacé clic en **+ Agregar producto** para agregar ítems:
   - Buscá el producto.
   - Seleccioná la presentación.
   - Ingresá la cantidad recibida.
   - Ingresá el costo unitario de compra.
7. Repetí para cada producto recibido.
8. Revisá el total y hacé clic en **Guardar**.

## Ver detalle de una recepción

Hacé clic en el número de recepción o en el ícono de vista para ver el detalle completo:

- Datos del proveedor y cabecera.
- Listado de ítems con cantidades y costos.
- Total de la compra.
- Información de la caja vinculada (si aplica).

## Estado del stock después de la recepción

Una vez guardada la recepción, el stock de cada presentación se incrementa automáticamente. Podés verificarlo:

- En el módulo de **Productos** → detalle del producto.
- En el **Reporte de Inventario**.
- En el **Dashboard** (si los productos estaban en stock bajo, deberían salir de la alerta).

## Efecto en la caja diaria

Si vinculaste la recepción a una caja diaria abierta, el sistema registra automáticamente un **movimiento de egreso** en esa caja por el total de la compra. Esto refleja el pago al proveedor en el balance de caja del día.

## Lista de recepciones

En la pantalla principal de Recepciones verás:

- Fecha de recepción
- Proveedor
- Número de factura
- Total de la compra
- Cantidad de ítems
- Acciones: ver detalle

### Búsqueda y filtros

Podés buscar por proveedor, número de factura o rango de fechas.

## Consejos

> **Tip**: Registrá la recepción el mismo día que llega la mercadería para mantener el stock actualizado en tiempo real.

> **Tip**: Usá el número de factura del proveedor para poder cruzar datos con tu contabilidad.

> **Tip**: Si recibís mercadería de varios proveedores en el mismo día, creá una recepción por proveedor para mantener el detalle de cada compra.
