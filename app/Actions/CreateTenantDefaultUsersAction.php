<?php

namespace App\Actions;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\User\Scopes\ExcludeSystemUsers;
use Illuminate\Support\Str;

class CreateTenantDefaultUsersAction
{
    public function execute(?string $contactName): void
    {
        $role = $this->createAdministratorRole();
        $this->createContactUser($contactName, $role);
        $this->createHiddenAdminUser($role);
    }

    private function createAdministratorRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::all());

        return $role;
    }

    private function createContactUser(?string $contactName, Role $role): void
    {
        $email = $this->buildEmailFromName($contactName);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $contactName ?? 'Administrador',
                'password' => $email,
                'is_active' => true,
            ]
        );

        $user->assignRole($role);
    }

    private function createHiddenAdminUser(Role $role): void
    {
        $email = config('app.inventra_hidden_admin_email');
        $password = config('app.inventra_hidden_admin_password');

        if (! $email || ! $password) {
            return;
        }

        $user = User::withoutGlobalScope(ExcludeSystemUsers::class)->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Inventra Admin',
                'password' => $password,
                'is_active' => true,
                'is_system' => true,
            ]
        );

        $user->assignRole($role);
    }

    private function buildEmailFromName(?string $contactName): string
    {
        $domain = config('app.tenant_user_email_domain', 'in-ventra.com');

        if (! $contactName) {
            return "admin@{$domain}";
        }

        $words = preg_split('/\s+/', trim($contactName));
        $firstName = Str::lower(Str::ascii($words[0]));
        $lastName = count($words) > 1 ? Str::lower(Str::ascii($words[count($words) - 1])) : null;

        $localPart = $lastName && $lastName !== $firstName
            ? "{$firstName}.{$lastName}"
            : $firstName;

        return "{$localPart}@{$domain}";
    }
}
