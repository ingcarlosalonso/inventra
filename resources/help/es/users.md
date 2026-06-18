# Usuarios y Perfil

## Gestión de Usuarios

La sección de **Usuarios** permite administrar las cuentas de las personas que tienen acceso al sistema.

**Permiso requerido:** `create_edit_delete_users`

### Listar usuarios

Desde la pantalla principal de usuarios podés ver todos los usuarios del tenant, con su nombre, email, roles asignados y estado (activo/inactivo). Podés buscar por nombre o email usando el campo de búsqueda.

### Crear usuario

Completá el formulario con:
- **Nombre** (obligatorio)
- **Email** (obligatorio, debe ser único)
- **Contraseña** (obligatoria al crear, mínimo 8 caracteres con letras mayúsculas, minúsculas y números)
- **Roles** (opcional, múltiples)
- **Estado activo** (por defecto activo)

### Editar usuario

Al editar un usuario podés modificar todos sus datos. **El campo contraseña es opcional**: si lo dejás vacío, la contraseña actual se mantiene sin cambios. Si completás una nueva contraseña, se actualizará al guardar.

> Un usuario **no puede eliminarse a sí mismo**.

### Activar / Desactivar usuario

Usando el botón de toggle en la lista podés activar o desactivar una cuenta sin eliminarla. Los usuarios inactivos no pueden iniciar sesión.

---

## Cambiar contraseña de otro usuario (Admin)

Cualquier usuario con el permiso `create_edit_delete_users` puede cambiar la contraseña de cualquier otro usuario desde la pantalla de edición. No se requiere conocer la contraseña actual del usuario afectado.

**Requisito de contraseña:** mínimo 8 caracteres, debe incluir letras mayúsculas, minúsculas y números.

---

## Mi Perfil — Cambiar mi propia contraseña

**Cualquier usuario autenticado** (sin importar sus permisos) puede cambiar su propia contraseña desde la sección **Mi Perfil**.

A diferencia del flujo de admin, aquí se requiere:
- **Contraseña actual** (para verificar la identidad)
- **Nueva contraseña**
- **Confirmación de la nueva contraseña**

Esto garantiza que nadie pueda cambiar tu contraseña sin conocer la actual, incluso si alguien tuviera acceso momentáneo a tu sesión.

---

## ¿Cuándo usar cada opción?

- **Querés cambiar tu propia contraseña** → andá a **Mi Perfil**
- **Necesitás resetear la contraseña de otro usuario** (olvidó la suya) → usá la edición de usuario (requiere permiso `create_edit_delete_users`)