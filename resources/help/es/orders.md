# Pedidos

El módulo de Pedidos gestiona las órdenes de entrega. Un pedido puede crearse desde una venta, desde un presupuesto o de forma independiente. Permite asignar un courier (repartidor), definir la dirección de entrega y hacer seguimiento del estado de la entrega.

## ¿Qué es un pedido?

Un pedido es una orden de entrega de productos a un cliente. A diferencia de una venta que se realiza en el momento, un pedido representa una entrega programada. El pedido:

- Puede estar vinculado a una venta previa.
- Tiene un courier asignado para la entrega.
- Sigue una máquina de estados configurable (ej: Pendiente → En preparación → En camino → Entregado).
- El stock se descuenta cuando el pedido pasa a estado final de entrega.

## Atributos de un pedido

**Cabecera:**
- **Cliente**: destinatario del pedido.
- **Courier**: repartidor asignado para la entrega.
- **Estado**: estado actual del pedido en el flujo.
- **Punto de venta**: origen del pedido.
- **Moneda**: moneda del pedido.
- **Dirección de entrega**: dónde se entregará.
- **Requiere envío**: indica si el pedido necesita entrega física.
- **Fecha de entrega**: cuándo se planifica entregar.
- **Fecha programada**: fecha acordada con el cliente.
- **Notas**: observaciones internas y del cliente.

**Ítems:** misma estructura que ventas:
- Producto + presentación
- Cantidad
- Precio unitario
- Descuento

**Pagos:** igual que en ventas, podés registrar múltiples pagos.

## Crear un pedido

1. Hacé clic en **Nuevo Pedido** (desde la barra superior o desde Pedidos).
2. Seleccioná el **cliente**.
3. Asigná un **courier** si ya sabés quién realizará la entrega.
4. Seleccioná el **estado inicial** del pedido.
5. Completá la dirección de entrega (se pre-llena con la dirección del cliente si está cargada).
6. Definí las fechas de entrega y programada.
7. Activá **Requiere envío** si el pedido tiene entrega a domicilio.
8. Agregá los **ítems** del pedido.
9. Agregá los **pagos** si el cliente paga al solicitar el pedido.
10. Hacé clic en **Guardar**.

## Crear pedido desde presupuesto o venta

- **Desde presupuesto**: en el detalle del presupuesto, hacé clic en **Convertir a Pedido**. Los ítems se pre-cargan automáticamente.
- **Desde venta**: en el detalle de la venta, hacé clic en **Generar Pedido**. Los ítems de la venta se transfieren al pedido.

## Cambiar estado del pedido

Desde el detalle del pedido, podés cambiar el estado usando el selector de estados. Cada cambio se registra con fecha y hora.

Los estados son configurables en **Configuración → Estados de Pedido**.

## Estado final y descuento de stock

Cuando un pedido llega a un **estado final** (ej: "Entregado"), el sistema registra el descuento de stock de todos los ítems. Si el pedido fue creado desde una venta, el stock puede ya haberse descontado al confirmar la venta.

## Asignación de courier

Podés asignar o reasignar el courier en cualquier momento antes de que el pedido llegue a estado final. El reporte de Pedidos muestra métricas por courier.

## Lista de pedidos

En **Pedidos** verás:

- Número de pedido
- Cliente y courier
- Estado (con badge de color)
- Fechas de entrega y programada
- Total
- Acciones: ver, editar, cambiar estado

### Filtros

- Por estado
- Por cliente
- Por courier
- Por punto de venta
- Por rango de fechas

## Seguimiento y trazabilidad

El detalle del pedido muestra el historial completo:

- Todos los cambios de estado con fecha y usuario.
- Ítems, precios y descuentos.
- Pagos recibidos.
- Notas del operador y del cliente.

## Consejos

> **Tip**: Configurá los estados con colores distintivos para identificar rápidamente en la lista qué pedidos necesitan atención inmediata.

> **Tip**: Asigná el courier lo antes posible para que el reporte de eficiencia de entrega sea preciso.

> **Tip**: Usá el campo "Fecha programada" para ordenar los pedidos por urgencia y organizar la logística del día.

> **Tip**: Si tu negocio tiene múltiples zonas de entrega, podés usar el campo de notas para especificar la zona o usar estados diferenciados por zona.
