# Configuración

La sección de Configuración agrupa todos los parámetros del sistema que definen cómo funciona In-ventra. Está dividida en varias subsecciones.

## Tipos de Producto

Categorías jerárquicas para organizar el catálogo. Ver sección **Tipos de Producto** para más detalle.

Ruta: **Productos → Tipos de Producto**

---

## Tipos de Presentación

Las unidades de medida base del sistema (kg, g, litro, unidad, metro, etc.).

- Cada tipo tiene nombre y abreviación.
- Se usan para crear Presentaciones.

Ruta: **Productos → Tipos de Presentación**

---

## Presentaciones

Combinaciones de tipo y cantidad disponibles para asignar a productos (ej: "1 kg", "500 g", "6 unidades").

Ruta: **Productos → Presentaciones**

---

## Tipos de Movimiento de Producto

Categorías para los movimientos manuales de stock (extras).

Cada tipo de movimiento tiene:
- **Nombre**: descripción del movimiento (ej: "Pérdida por vencimiento", "Corrección de inventario").
- **Dirección**: entrada (aumenta stock) o salida (disminuye stock).
- **Afecta stock**: si el movimiento modifica el stock disponible.

Ejemplos de tipos de movimiento de producto:
- "Ajuste positivo" → entrada → afecta stock
- "Rotura / Pérdida" → salida → afecta stock
- "Inventario" → ambas → afecta stock

Ruta: **Configuración → Tipos de Movimiento de Producto**

---

## Tipos de Movimiento de Caja

Categorías para los movimientos manuales en la caja diaria.

Cada tipo tiene:
- **Nombre**: descripción (ej: "Depósito titular", "Pago de gastos", "Retiro").
- **Dirección**: ingreso (suma al balance) o egreso (resta del balance).

Ejemplos:
- "Depósito de apertura" → ingreso
- "Retiro para gastos" → egreso
- "Cobro de deuda" → ingreso

Ruta: **Configuración → Tipos de Movimiento de Caja**

---

## Puntos de Venta

Los puntos de venta son las sucursales o cajas físicas/virtuales del negocio.

Cada punto de venta tiene:
- **Número**: identificador numérico.
- **Nombre**: nombre descriptivo (ej: "Sucursal Centro", "Tienda Online").
- **Dirección**: ubicación física.
- **Horario de apertura automática**: si se configura, la caja se abre automáticamente a esa hora.
- **Horario de cierre automático**: la caja se cierra automáticamente a esa hora.

Cada venta, pedido y caja diaria está asociada a un punto de venta.

Ruta: **Configuración → Puntos de Venta**

---

## Estados de Venta

Los estados definen el ciclo de vida de una venta. Son configurables según el flujo de trabajo de tu negocio.

Cada estado tiene:
- **Nombre**: descripción del estado (ej: "Pendiente", "Confirmada", "Anulada").
- **Color**: color del badge para identificación visual.
- **Es estado por defecto**: si está activado, las nuevas ventas se crean en este estado automáticamente. Solo puede haber un estado por defecto.
- **Es estado final**: si está activado, las ventas en este estado no pueden modificarse. Indica que la venta está completada o cerrada.
- **Orden de visualización**: controla el orden en que aparecen los estados en los selectores y filtros.

Ruta: **Configuración → Estados de Venta**

---

## Estados de Pedido

Misma lógica que los estados de venta, pero aplicados al ciclo de vida de los pedidos.

Cada estado tiene:
- **Nombre**, **Color**, **Es estado por defecto**, **Es estado final**, **Está activo**, **Orden**.

El campo "Está activo" permite desactivar un estado sin eliminarlo (útil para estados temporalmente en desuso).

Ruta: **Configuración → Estados de Pedido**

---

## Métodos de Pago

Los medios por los que los clientes pueden pagar (efectivo, tarjeta de débito, tarjeta de crédito, transferencia bancaria, etc.).

Son simples: solo tienen **nombre**. Se usan al registrar pagos en ventas y pedidos.

Ruta: **Configuración → Métodos de Pago**

---

## Couriers

Los repartidores asignados a pedidos con entrega a domicilio.

Cada courier tiene:
- **Nombre**: nombre completo del repartidor.
- **Email**: correo de contacto.
- **Teléfono**: número de contacto.
- **Estado**: activo o inactivo.

Los couriers inactivos no aparecen en el selector al crear pedidos.

Ruta: **Configuración → Couriers**

---

## Monedas

El sistema soporta múltiples monedas para ventas y productos.

Cada moneda tiene:
- **Nombre**: nombre completo (ej: "Peso Argentino").
- **Símbolo**: símbolo abreviado (ej: "$", "US$", "€").
- **Código ISO**: código estándar (ej: "ARS", "USD", "EUR").
- **Es moneda por defecto**: una moneda debe ser la predeterminada del sistema.

Ruta: **Configuración → Monedas**

---

## Buenas prácticas de configuración

- Configurá los estados de venta y pedido antes de empezar a operar.
- Definí siempre un estado por defecto y al menos un estado final.
- Cargá todos los métodos de pago que usás habitualmente.
- Definí los tipos de presentación y presentaciones comunes antes de cargar el catálogo.
- Activá el horario de apertura/cierre automático solo si el punto de venta opera en horarios fijos.

## Consejos

> **Tip**: Los colores de los estados de venta y pedido son clave para una operación visual rápida. Usá rojo para estados de alerta, verde para completados y azul para en proceso.

> **Tip**: Si tenés varias sucursales, creá un punto de venta por cada una. Esto permite reportes separados por sucursal.

> **Tip**: Los tipos de movimiento de caja bien definidos simplifican el análisis del reporte de Cajas Diarias.
