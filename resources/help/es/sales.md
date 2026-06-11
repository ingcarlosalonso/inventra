# Ventas

El módulo de Ventas es el corazón de In-ventra. Permite registrar todas las transacciones de venta con sus ítems, descuentos, pagos y stock asociado.

## Flujo de una venta

1. **Crear la venta**: seleccioná cliente, punto de venta y estado.
2. **Agregar ítems**: buscá productos, definí cantidades y descuentos.
3. **Registrar pagos**: seleccioná métodos de pago y montos.
4. **Confirmar**: el sistema valida el stock, registra la venta y actualiza los saldos.

## Crear una venta

Hacé clic en **Nueva Venta** (desde la barra superior o desde el listado de Ventas).

### Paso 1: Datos generales

- **Cliente**: buscá y seleccioná el cliente (opcional). Para ventas sin cliente registrado, podés dejar en blanco o seleccionar un cliente genérico "Mostrador".
- **Punto de venta**: sucursal o caja donde se realiza la venta.
- **Estado**: el estado inicial se asigna automáticamente según la configuración del punto de venta. Podés cambiarlo manualmente.
- **Moneda**: moneda de la venta.
- **Notas**: observaciones internas.

### Paso 2: Agregar ítems

1. En la sección de ítems, buscá el producto por nombre, código o código de barra.
2. Seleccioná la **presentación** del producto.
3. Ajustá la **cantidad** (por defecto 1).
4. El **precio unitario** se pre-llena automáticamente con el precio de la presentación. Podés modificarlo.
5. Aplicá un **descuento** si aplica:
   - Porcentual: ej. 10% → el sistema calcula el monto.
   - Monto fijo: ej. $50 → se descuenta directamente del subtotal.
6. Hacé clic en **+ Agregar** para confirmar el ítem.
7. Repetí para cada producto.

### Paso 3: Pagos

1. En la sección de pagos, seleccioná el **método de pago** (efectivo, tarjeta, transferencia, etc.).
2. Ingresá el **monto** pagado con ese método.
3. El sistema muestra:
   - **Total de la venta**
   - **Total pagado**
   - **Saldo restante** (en rojo si falta pagar)
   - **Vuelto** (si se pagó de más en efectivo)
4. Podés agregar múltiples pagos con diferentes métodos (ej: parte en efectivo + parte en tarjeta).

### Paso 4: Confirmar

Hacé clic en **Guardar Venta**. El sistema:
- Valida que haya stock suficiente para cada ítem.
- Descuenta el stock de cada presentación.
- Registra los pagos en la caja diaria del punto de venta.
- Genera el número de venta.

## Detalle de una venta

Hacé clic en el número de venta para ver el detalle completo:

- Datos del cliente y punto de venta.
- Listado de ítems con precios, descuentos y subtotales.
- Resumen de pagos por método.
- Totales: subtotal, total de descuentos, total de venta, total cobrado, saldo.

## Lista de ventas

Navegá a **Ventas** en la barra lateral. Verás el listado con:

- Número de venta
- Fecha y hora
- Cliente
- Estado (con badge de color)
- Total y total cobrado
- Acciones: ver detalle, editar (si no está en estado final), anular

### Filtros disponibles

- Por rango de fechas
- Por cliente
- Por estado
- Por punto de venta

## Estados de venta

Los estados son configurables en **Configuración → Estados de Venta**. Pueden ser:

- **Estado por defecto**: se asigna automáticamente a nuevas ventas (ej: "Pendiente").
- **Estado final**: indica que la venta está cerrada (ej: "Confirmada", "Anulada"). Las ventas en estado final no pueden modificarse.

## Descuentos

Podés aplicar descuentos a nivel de ítem:

- **Porcentual** (ej: 15%): el sistema calcula el monto = precio × cantidad × porcentaje.
- **Monto fijo** (ej: $100): se resta directamente del subtotal del ítem.

Los descuentos se acumulan para mostrar el total de descuentos en el resumen.

## Múltiples métodos de pago

Una venta puede tener varios registros de pago. Por ejemplo:

- $500 en efectivo
- $800 con tarjeta de débito

Esto es útil cuando el cliente paga parcialmente con diferentes medios. El sistema calcula automáticamente el saldo restante.

## Crear venta desde presupuesto

Desde el módulo de **Presupuestos**, podés convertir un presupuesto aceptado en una venta. El sistema pre-carga todos los ítems del presupuesto en el formulario de venta para que solo agregues los pagos y confirmes.

## Consejos

> **Tip**: Configurá un estado de venta "Pendiente" como default y "Confirmada" como estado final. Así podés registrar ventas a crédito sin cobro inmediato y luego confirmarlas cuando se cobra.

> **Tip**: Usá la búsqueda por código de barra para agilizar el proceso de carga de ítems.

> **Tip**: Si la venta tiene descuento general (no por ítem), podés aplicar el descuento al primer o último ítem de la lista.
