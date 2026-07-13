<?php

declare(strict_types=1);

use App\Core\Database\Seeder;
use App\Repositories\RoleRepository;

return new class extends Seeder {
    public function run(): void
    {
        $roles = new RoleRepository();

        $definitions = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full, unrestricted access to the entire platform.', 'is_system' => 1],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Manage users, plans, and platform settings.', 'is_system' => 1],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Standard end-user account. Default role on registration.', 'is_system' => 1],
            ['name' => 'Team Owner', 'slug' => 'team-owner', 'description' => 'Owns a team workspace and its billing.', 'is_system' => 1],
            ['name' => 'Team Member', 'slug' => 'team-member', 'description' => 'Member of a team workspace with edit access.', 'is_system' => 1],
            ['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only access to a team workspace.', 'is_system' => 1],
        ];

        foreach ($definitions as $definition) {
            if ($roles->findBySlug($definition['slug']) !== null) {
                continue;
            }

            $roles->create($definition);
        }
    }
};
