# Tipos de Producto

Los tipos de producto son las **categorías** del catálogo. Permiten organizar y agrupar productos de manera jerárquica para facilitar la búsqueda, filtrado y generación de reportes.

## Estructura jerárquica

Los tipos de producto soportan una estructura de árbol con niveles padre e hijo. Por ejemplo:

```
Alimentos
├── Aceites y grasas
│   ├── Aceites vegetales
│   └── Margarinas
├── Lácteos
│   ├── Leche
│   └── Quesos
└── Bebidas
    ├── Jugos
    └── Aguas
```

Cada tipo puede tener un tipo padre (categoría superior) o ser una categoría raíz.

## Atributos de un tipo de producto

- **Nombre**: nombre de la categoría (ej: "Aceites y grasas").
- **Tipo padre**: categoría superior a la que pertenece (opcional). Si no se selecciona, el tipo será una categoría raíz.

## Ver tipos de producto

Navegá a **Productos → Tipos de Producto** (o **Configuración → Tipos de Producto**). Podés ver la lista en:

- **Vista árbol**: muestra la jerarquía completa con sangría para categorías hijas.
- **Vista lista**: muestra todos los tipos en orden alfabético.

## Crear un tipo de producto

1. Hacé clic en **Nuevo Tipo de Producto**.
2. Ingresá el nombre de la categoría.
3. Si es una subcategoría, seleccioná el **Tipo padre**.
4. Hacé clic en **Guardar**.

## Editar un tipo de producto

Hacé clic en el ícono de edición en la fila del tipo. Podés cambiar el nombre y el padre.

> **Advertencia**: Cambiar el tipo padre mueve la categoría y todas sus subcategorías en el árbol.

## Eliminar un tipo de producto

Para eliminar un tipo de producto se requiere que:

1. No tenga subcategorías activas.
2. No tenga productos asignados.

Si hay subcategorías, primero eliminá o reasigná los hijos. Si hay productos, reasigná los productos a otro tipo antes de eliminar.

## Uso en otros módulos

- **Productos**: cada producto se asigna a un tipo al crearlo.
- **Reportes**: podés filtrar ventas e inventario por tipo de producto.
- **Búsqueda**: en la lista de productos podés filtrar por tipo.

## Buenas prácticas

- Definí los tipos de producto antes de cargar el catálogo.
- Usá una jerarquía de no más de 3 niveles para mantener la navegación simple.
- Los nombres deben ser cortos y descriptivos.

## Consejos

> **Tip**: Una buena categorización desde el inicio simplifica enormemente los reportes por rubro.

> **Tip**: Si tu negocio tiene pocas categorías, podés usar solo tipos raíz sin subcategorías.
