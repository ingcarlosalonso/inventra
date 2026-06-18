# Users & Profile

## User Management

The **Users** section lets you manage accounts for everyone who has access to the system.

**Required permission:** `create_edit_delete_users`

### Listing users

The main users screen shows all tenant users with their name, email, assigned roles, and status (active/inactive). You can search by name or email using the search field.

### Creating a user

Fill in the form with:
- **Name** (required)
- **Email** (required, must be unique)
- **Password** (required when creating, minimum 8 characters with uppercase, lowercase letters and numbers)
- **Roles** (optional, multiple allowed)
- **Active status** (active by default)

### Editing a user

When editing a user you can modify all their data. **The password field is optional**: if you leave it blank the current password is kept unchanged. If you fill in a new password it will be updated on save.

> A user **cannot delete their own account**.

### Activate / Deactivate user

Using the toggle button in the list you can activate or deactivate an account without deleting it. Inactive users cannot log in.

---

## Changing another user's password (Admin)

Any user with the `create_edit_delete_users` permission can change any other user's password from the edit screen. The affected user's current password is **not required**.

**Password requirement:** minimum 8 characters, must include uppercase letters, lowercase letters, and numbers.

---

## My Profile — Changing your own password

**Any authenticated user** (regardless of their permissions) can change their own password from the **My Profile** section.

Unlike the admin flow, this requires:
- **Current password** (to verify identity)
- **New password**
- **New password confirmation**

This ensures nobody can change your password without knowing the current one, even if someone momentarily had access to your session.

---

## When to use each option?

- **You want to change your own password** → go to **My Profile**
- **You need to reset another user's password** (they forgot theirs) → use the user edit screen (requires `create_edit_delete_users` permission)