# Presupuestos

> **Nota**: Pedidos y Presupuestos es un módulo contratable por separado. Si no ves esta sección en tu menú, contactá al administrador de tu cuenta para activarlo.

El módulo de Presupuestos permite generar cotizaciones para clientes antes de concretar una venta. Los presupuestos no afectan el stock hasta que se convierten en venta u orden.

## ¿Qué es un presupuesto?

Un presupuesto es una propuesta de venta con un listado de productos y precios que se entrega al cliente para su evaluación. Tiene validez hasta una fecha determinada. Si el cliente acepta, se convierte en venta.

## Atributos de un presupuesto

**Cabecera:**
- **Cliente**: destinatario del presupuesto (opcional).
- **Fecha de inicio**: desde cuándo es válido el presupuesto.
- **Fecha de vencimiento**: hasta cuándo es válido.
- **Notas**: condiciones, observaciones o aclaraciones para el cliente.
- **Estado**: Pendiente o Convertido.

**Ítems:** misma estructura que una venta:
- Producto + presentación
- Cantidad
- Precio unitario (editable)
- Descuento (porcentual o monto fijo)
- Subtotal

## Crear un presupuesto

1. Hacé clic en **Nuevo Presupuesto** (desde la barra superior o desde Presupuestos).
2. Seleccioná el cliente (opcional).
3. Definí las fechas de inicio y vencimiento.
4. Agregá los ítems: buscá el producto, seleccioná la presentación, definí cantidad y precio.
5. Aplicá descuentos si corresponde.
6. Agregá notas con condiciones del presupuesto.
7. Hacé clic en **Guardar**.

## Ver un presupuesto

Hacé clic en el número de presupuesto para ver el detalle completo con todos los ítems, totales y fechas de validez.

## Convertir a venta

Cuando el cliente acepta el presupuesto:

1. Abrí el detalle del presupuesto.
2. Hacé clic en **Convertir a Venta**.
3. El sistema abre el formulario de nueva venta pre-cargado con los ítems del presupuesto.
4. Seleccioná el punto de venta y completá los pagos.
5. Confirmá la venta.

El presupuesto cambia automáticamente a estado **Convertido** y queda vinculado a la venta creada.

## Lista de presupuestos

Navegá a **Presupuestos** para ver el listado:

- Número de presupuesto
- Fecha y vencimiento
- Cliente
- Total
- Estado: Pendiente / Convertido (con badge de color)
- Acciones: ver, convertir, eliminar

### Filtros

- Por búsqueda (texto libre)

## Presupuestos vencidos

Los presupuestos con fecha de vencimiento pasada se marcan visualmente como **vencidos** pero permanecen en la lista.

## Crear un presupuesto con el Asistente de IA

También podés pedirle al **Asistente de IA** (ícono en la barra superior) que cree el presupuesto por vos, describiendo cliente, productos y vencimiento en lenguaje natural. Te muestra un borrador antes de confirmar. Ver [Asistente de IA](/help/ai-assistant).

## Consejos

> **Tip**: Usá el campo de notas para incluir condiciones de pago, tiempos de entrega o cualquier consideración especial del presupuesto.

> **Tip**: Los presupuestos no afectan el stock, por lo que podés crear tantos como necesites para diferentes clientes sobre los mismos productos.

> **Tip**: Configurá un período de validez estándar (ej: 15 días) para que los vendedores tengan un criterio uniforme al presupuestar.
