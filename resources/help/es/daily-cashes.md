# Caja Diaria

El módulo de Caja Diaria gestiona el flujo de dinero de cada punto de venta. Registra automáticamente todos los ingresos por ventas y pagos, y permite registrar movimientos manuales de depósito o extracción.

## ¿Qué es una caja diaria?

Una caja diaria es el registro del movimiento de dinero de un punto de venta en un período de tiempo. Tiene:

- **Apertura**: balance inicial al comenzar el día.
- **Movimientos**: ingresos y egresos durante el día.
- **Cierre**: balance final al cerrar el día y conciliación.

Solo puede haber **una caja abierta por punto de venta** al mismo tiempo.

## Abrir una caja

1. Navegá a **Caja Diaria**.
2. Hacé clic en **Abrir Caja**.
3. Seleccioná el **punto de venta**.
4. Ingresá el **balance de apertura** (dinero en caja al iniciar).
5. Hacé clic en **Confirmar Apertura**.

La caja queda abierta y comienza a registrar movimientos.

## Ver el detalle de una caja

Hacé clic en la caja para ver su detalle completo:

### Balance actual

El sistema muestra en tiempo real:

- **Balance de apertura**: monto con el que se inició la caja.
- **Ingresos**: suma de todos los pagos recibidos (ventas, cobros de pedidos).
- **Egresos**: suma de todas las salidas (pagos a proveedores por recepciones, movimientos extras de salida).
- **Balance actual**: apertura + ingresos - egresos.

### Movimientos

La tabla de movimientos muestra cronológicamente:

- **Pagos de ventas**: cada pago recibido en una venta, con método de pago.
- **Pagos de pedidos**: cobros registrados al crear o actualizar pedidos.
- **Recepciones**: pagos a proveedores registrados al ingresar stock.
- **Movimientos extra**: depósitos o extracciones manuales.

Cada movimiento muestra: fecha/hora, tipo, descripción, método de pago y monto (+ o -).

## Movimientos extra manuales

Los movimientos extra permiten registrar entradas o salidas de dinero no relacionadas con ventas:

- **Depósito**: ingreso de dinero (ej: depósito de titular, cobro de deuda anterior).
- **Extracción**: salida de dinero (ej: pago de gastos operativos, retiro de efectivo).

Para agregar un movimiento extra:

1. En el detalle de la caja abierta, hacé clic en **+ Movimiento Extra**.
2. Seleccioná el **tipo de movimiento** (configurado en Configuración → Tipos de Movimiento de Caja).
3. Ingresá el monto.
4. Ingresá una descripción o notas.
5. Confirmar.

## Cerrar una caja

1. En el detalle de la caja abierta, hacé clic en **Cerrar Caja**.
2. El sistema muestra el balance calculado (según movimientos registrados).
3. Ingresá el **balance real** (conteo físico del dinero en caja).
4. Si hay diferencia, el sistema la registra.
5. Confirmá el cierre.

Una vez cerrada, la caja **no puede modificarse ni eliminarse**. El historial queda guardado permanentemente.

## Cajas cerradas

Las cajas cerradas aparecen en el listado con estado "Cerrada". Podés acceder al detalle completo de sus movimientos para consultas y auditorías.

## Apertura/cierre automático

Si el punto de venta tiene configurado horario de apertura y cierre automático, la caja puede abrirse y cerrarse automáticamente. Verificá la configuración en **Configuración → Puntos de Venta**.

## Lista de cajas

En **Caja Diaria** verás el listado de todas las cajas con:

- Punto de venta
- Fecha de apertura
- Balance de apertura
- Balance actual / final
- Estado: Abierta / Cerrada

### Filtros

- Por punto de venta
- Por estado
- Por rango de fechas

## Reporte de Caja

El **Reporte de Cajas Diarias** permite analizar el desempeño de múltiples cajas en un período:

- Balance total por punto de venta.
- Ingresos, egresos y diferencias.
- Exportación a Excel.

## Consejos

> **Tip**: Abrí la caja al iniciar la jornada y cerrala al finalizar, aunque no haya movimientos. Esto mantiene un historial preciso y ordenado.

> **Tip**: Antes de cerrar la caja, contá físicamente el dinero e ingresalo como "balance real" para detectar diferencias de caja.

> **Tip**: Usá los movimientos extra para registrar gastos operativos del día (insumos, servicios) y así tener un registro completo de los egresos.
