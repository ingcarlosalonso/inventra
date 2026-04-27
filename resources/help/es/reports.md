# Reportes

El módulo de Reportes ofrece análisis detallados de todas las áreas del negocio: ventas, productos, clientes, stock, caja y pedidos. Todos los reportes incluyen filtros por fecha y exportación a Excel.

## Acceder a los reportes

Navegá a **Reportes** en la barra lateral para ver el listado de reportes disponibles. También podés acceder desde el botón **Reportes** en la barra superior.

## Reportes disponibles

### Reporte de Ventas

Analiza el rendimiento de ventas en el período seleccionado.

**Métricas clave:**
- Ingresos totales del período
- Monto total cobrado
- Total de descuentos aplicados
- Ticket promedio por venta
- Cantidad de ventas

**Visualizaciones:**
- Gráfico de evolución de ingresos (línea de tiempo)
- Tabla completa de ventas con filtros por cliente, estado y punto de venta

**Filtros:** rango de fechas, cliente, punto de venta, estado de venta.

---

### Reporte de Productos

Identifica los productos más vendidos y el rendimiento del catálogo.

**Métricas clave:**
- Unidades vendidas por producto
- Ingresos generados por producto
- Ranking de top productos

**Visualizaciones:**
- Gráfico de barras: top productos por unidades
- Gráfico de barras: top productos por ingresos
- Tabla detallada por producto/presentación

**Filtros:** rango de fechas, tipo de producto.

---

### Reporte de Pagos

Analiza cómo pagan los clientes y el flujo de cobros.

**Métricas clave:**
- Total cobrado por método de pago
- Cobros del día / semana / mes
- Promedio diario de cobros

**Visualizaciones:**
- Gráfico de cobros diarios (línea de tiempo)
- Gráfico de torta: distribución por método de pago
- Tabla de movimientos con método, monto y fecha

**Filtros:** rango de fechas, método de pago, punto de venta.

---

### Reporte de Inventario

Muestra el estado actual del stock de todos los productos.

**Métricas clave:**
- Total de productos en stock
- Productos con stock bajo (bajo el mínimo)
- Productos sin stock

**Tabla de stock:**
- Producto y presentación
- Stock actual y stock mínimo
- Estado del stock: OK / Bajo / Sin stock (con colores)
- Valor del stock (cantidad x precio)

**Filtros:** tipo de producto, estado de stock, búsqueda por nombre.

---

### Reporte de Cajas Diarias

Resumen financiero de todas las cajas del período.

**Métricas:**
- Total de ingresos por punto de venta
- Total de egresos
- Diferencias de caja (si las hubo)
- Balance neto del período

**Tabla:**
- Detalle por caja: apertura, cierre, ingresos, egresos, diferencia.

**Filtros:** rango de fechas, punto de venta, estado (abierta/cerrada).

---

### Reporte de Pedidos

Análisis del rendimiento de entregas y logística.

**Métricas:**
- Pedidos por estado
- Pedidos por courier
- Tiempo promedio de entrega
- Total de pedidos y monto

**Visualizaciones:**
- Distribución de pedidos por estado
- Ranking de couriers por cantidad de entregas

**Filtros:** rango de fechas, estado, courier, punto de venta.

---

### Reporte de Clientes

Identifica los clientes más activos y su comportamiento de compra.

**Métricas:**
- Top clientes por monto comprado
- Cantidad de compras por cliente
- Ticket promedio por cliente

**Visualizaciones:**
- Gráfico de barras: top clientes por ingresos

**Filtros:** rango de fechas, cliente.

---

### Reporte de Compras

Analiza las compras a proveedores (recepciones) del período.

**Métricas:**
- Total invertido en compras
- Compras por proveedor
- Costo promedio por recepción

**Tabla:**
- Detalle de cada recepción con proveedor, fecha, ítems y total.

**Filtros:** rango de fechas, proveedor.

---

## Exportar a Excel

Todos los reportes tienen un botón **Exportar Excel** que descarga un archivo XLSX con los datos de la tabla del reporte.

## Filtros comunes

La mayoría de los reportes comparte estos filtros:

- **Rango de fechas**: "Desde" y "Hasta" (o selección rápida: Hoy, Esta semana, Este mes).
- **Punto de venta**: para ver datos de una sucursal específica.
- **Estado**: para filtrar por estado de la transacción.

## Permisos de reportes

El acceso a cada reporte está controlado por permisos. Un usuario puede tener acceso a algunos reportes y no a otros, según los roles configurados por el administrador.

## Consejos

> **Tip**: Revisá el reporte de Inventario semanalmente para detectar productos con stock bajo antes de que se agoten.

> **Tip**: El reporte de Pagos muestra qué medios de pago prefieren tus clientes, útil para negociar condiciones con tarjetas o bancos.

> **Tip**: Exportá los reportes a Excel para crear tus propios dashboards o compartir datos con tu contador.

> **Tip**: Combiná el reporte de Ventas con el de Clientes para identificar qué clientes generan más ingresos.
