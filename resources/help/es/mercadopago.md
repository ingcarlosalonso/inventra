# Mercado Pago

El módulo de Mercado Pago permite cobrar ventas y pedidos con tarjeta a través de una terminal Point (posnet) de Mercado Pago, vinculada a tu propia cuenta.

> Este módulo es opcional y debe estar habilitado para tu empresa. Si no ves esta sección en Configuración, contactá a soporte.

## Conectar tu cuenta

1. Navegá a **Configuración → Mercado Pago**.
2. Hacé clic en **Conectar con Mercado Pago**.
3. Iniciá sesión con tu cuenta de Mercado Pago y autorizá el acceso.
4. Al volver, verás el estado **Cuenta conectada**.

La conexión usa tu propia cuenta de Mercado Pago — los cobros ingresan directamente a ella, no a una cuenta compartida.

## Elegir el medio de pago local

Una vez conectada la cuenta, seleccioná qué **medio de pago** (de los configurados en Configuración → Medios de Pago) se va a usar para registrar los cobros aprobados por Mercado Pago. Esto determina cómo aparecen esos pagos en la Caja Diaria y en los reportes.

## Asignar una terminal a cada punto de venta

Para cobrar con posnet necesitás vincular una terminal Point física a cada punto de venta:

1. En **Configuración → Mercado Pago**, buscá la tabla de puntos de venta.
2. Para cada punto de venta, elegí la terminal correspondiente en el desplegable.
3. Los puntos de venta sin terminal asignada no podrán cobrar con posnet.

Las terminales disponibles se obtienen directamente de tu cuenta de Mercado Pago.

## Cobrar con posnet

Al ingresar un pago (**Pagos → Ingresar Pago**) para una venta o pedido cuyo punto de venta tiene una terminal asignada, vas a ver la opción **Cobrar con Mercado Pago**:

1. Ingresá el monto a cobrar.
2. Hacé clic en **Cobrar con Mercado Pago**.
3. El monto se envía a la terminal — el cliente completa el pago con tarjeta en el posnet.
4. In-ventra espera la confirmación (podés ver el estado "Esperando el pago en la terminal…").
5. Cuando Mercado Pago confirma el pago, se registra automáticamente como un pago de la venta/pedido, sin necesidad de cargarlo manualmente.

Si el pago es rechazado o cancelado desde la terminal, no se registra ningún pago y podés reintentar.

## Desconectar la cuenta

Desde **Configuración → Mercado Pago**, el botón **Desconectar** elimina la conexión. Los cobros con posnet dejan de estar disponibles hasta volver a conectar una cuenta.

## Consejos

> **Tip**: Verificá que cada punto de venta activo tenga su terminal asignada antes de empezar a operar con posnet.

> **Tip**: Si un cobro queda "esperando" por mucho tiempo, revisá la terminal física — puede que la operación haya expirado o haya sido cancelada manualmente.
