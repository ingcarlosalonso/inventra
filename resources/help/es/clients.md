# Clientes

El módulo de Clientes gestiona el directorio de clientes de tu empresa. Los clientes se utilizan en ventas, pedidos y presupuestos para asociar cada transacción a una persona o empresa.

## Atributos de un cliente

- **Nombre y apellido**: nombre completo del cliente.
- **CUIT / DNI**: número de identificación tributaria o documento.
- **Teléfono**: número de contacto.
- **Email**: correo electrónico.
- **Dirección**: dirección del cliente (útil para pedidos con entrega a domicilio).
- **Notas**: observaciones internas (preferencias, condiciones especiales, etc.).
- **Límite de crédito**: monto máximo que el cliente puede tener en deuda.

## Ver clientes

Navegá a **Clientes** en la barra lateral. Verás un listado con:

- Nombre completo
- CUIT/DNI
- Teléfono y email
- Acciones: editar, eliminar, ver historial

### Búsqueda

Buscá clientes por nombre, apellido, CUIT/DNI o email. El filtro actúa en tiempo real sobre la lista.

## Crear un cliente

1. Hacé clic en **Nuevo Cliente**.
2. Completá el nombre y apellido (obligatorios).
3. Ingresá los datos de contacto: CUIT/DNI, teléfono, email (opcionales).
4. Ingresá la dirección si el cliente recibe pedidos a domicilio.
5. Definí el límite de crédito si aplica (0 = sin límite).
6. Agregá notas internas si es necesario.
7. Hacé clic en **Guardar**.

## Editar un cliente

Hacé clic en el ícono de edición. Podés modificar todos los campos del cliente en cualquier momento.

## Eliminar un cliente

El sistema permite eliminar un cliente solo si no tiene ventas, pedidos ni presupuestos asociados. Si tiene transacciones, el cliente se puede **desactivar** pero no eliminar.

## Historial de compras

Desde el detalle del cliente podés acceder a su historial:

- Listado de ventas realizadas con fechas y montos.
- Total comprado en el período.
- Saldo pendiente de cobro.

También podés consultar el reporte de **Clientes** para ver el ranking de compras y análisis de comportamiento.

## Uso del cliente en otros módulos

- **Ventas**: el cliente es opcional pero recomendable para llevar historial.
- **Pedidos**: se requiere cliente para generar pedidos con datos de entrega.
- **Presupuestos**: asociar el cliente al presupuesto facilita el seguimiento.
- **Reportes**: el reporte de Clientes muestra el ranking de los más activos.

## Límite de crédito

El límite de crédito es un control interno. Si un cliente tiene ventas impagas que superan su límite, el sistema puede advertir al operador al crear una nueva venta. Esto no bloquea la venta automáticamente, pero sirve como alerta.

## Consejos

> **Tip**: Aunque la venta sin cliente es posible, te recomendamos asociar siempre un cliente para llevar un historial de compras preciso.

> **Tip**: Completá la dirección del cliente si usás el módulo de Pedidos con entrega a domicilio: la dirección se pre-llena automáticamente al crear el pedido.

> **Tip**: Usá el campo de notas para registrar preferencias del cliente, como "siempre paga con transferencia" o "solicitar factura A".
