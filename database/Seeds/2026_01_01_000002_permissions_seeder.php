<?php

declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Database\Seeder;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;

return new class extends Seeder {
    public function run(): void
    {
        $permissions = new PermissionRepository();
        $roles = new RoleRepository();

        $definitions = [
            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'group' => 'users'],
            ['name' => 'Suspend Users', 'slug' => 'users.suspend', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],

            // Roles & permissions
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'roles'],

            // Platform settings
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View Audit Logs', 'slug' => 'audit-logs.view', 'group' => 'settings'],

            // Bots / knowledge base (used from Phase 3 onward)
            ['name' => 'View Bots', 'slug' => 'bots.view', 'group' => 'bots'],
            ['name' => 'Create Bots', 'slug' => 'bots.create', 'group' => 'bots'],
            ['name' => 'Update Bots', 'slug' => 'bots.update', 'group' => 'bots'],
            ['name' => 'Delete Bots', 'slug' => 'bots.delete', 'group' => 'bots'],

            // Billing (used from Phase 5 onward)
            ['name' => 'Manage Billing', 'slug' => 'billing.manage', 'group' => 'billing'],
            ['name' => 'View Billing', 'slug' => 'billing.view', 'group' => 'billing'],

            // Platform business administration (Phase 5)
            ['name' => 'Manage Plans', 'slug' => 'plans.manage', 'group' => 'business'],
            ['name' => 'Manage Subscriptions', 'slug' => 'subscriptions.manage', 'group' => 'business'],
            ['name' => 'Manage Coupons', 'slug' => 'coupons.manage', 'group' => 'business'],
            ['name' => 'View Webhook Logs', 'slug' => 'webhook-logs.view', 'group' => 'business'],
        ];

        $permissionIds = [];

        foreach ($definitions as $definition) {
            $existing = $permissions->findBySlug($definition['slug']);

            $permissionIds[$definition['slug']] = $existing !== null
                ? (int) $existing['id']
                : (int) $permissions->create($definition);
        }

        $roleGrants = [
            'super-admin' => array_keys($permissionIds), // everything
            'admin' => [
                'users.view', 'users.create', 'users.update', 'users.suspend',
                'roles.manage', 'settings.manage', 'audit-logs.view',
                'bots.view', 'bots.create', 'bots.update', 'bots.delete',
                'billing.view', 'plans.manage', 'subscriptions.manage',
                'coupons.manage', 'webhook-logs.view',
            ],
            'user' => ['bots.view', 'bots.create', 'bots.update', 'bots.delete', 'billing.view'],
            'team-owner' => ['bots.view', 'bots.create', 'bots.update', 'bots.delete', 'billing.manage', 'billing.view'],
            'team-member' => ['bots.view', 'bots.create', 'bots.update'],
            'viewer' => ['bots.view'],
        ];

        $pdo = Connection::get();

        foreach ($roleGrants as $roleSlug => $slugs) {
            $role = $roles->findBySlug($roleSlug);

            if ($role === null) {
                continue;
            }

            foreach ($slugs as $slug) {
                if (!isset($permissionIds[$slug])) {
                    continue;
                }

                $statement = $pdo->prepare(
                    'INSERT IGNORE INTO role_permission (role_id, permission_id, created_at, updated_at) '
                    . 'VALUES (:role_id, :permission_id, NOW(), NOW())'
                );
                $statement->execute([
                    'role_id' => $role['id'],
                    'permission_id' => $permissionIds[$slug],
                ]);
            }
        }
    }
};
