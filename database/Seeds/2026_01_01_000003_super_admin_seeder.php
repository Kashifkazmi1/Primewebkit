<?php

declare(strict_types=1);

use App\Core\Database\Seeder;
use App\Core\Security\PasswordHasher;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

return new class extends Seeder {
    public function run(): void
    {
        $email = (string) env('SEED_SUPER_ADMIN_EMAIL', '');
        $password = (string) env('SEED_SUPER_ADMIN_PASSWORD', '');
        $name = (string) env('SEED_SUPER_ADMIN_NAME', 'Platform Owner');

        if ($email === '' || $password === '') {
            fwrite(STDOUT, "  Skipping super-admin seeder: SEED_SUPER_ADMIN_EMAIL / SEED_SUPER_ADMIN_PASSWORD not set in .env\n");

            return;
        }

        $users = new UserRepository();
        $roles = new RoleRepository();

        if ($users->emailExists($email)) {
            return;
        }

        $role = $roles->findBySlug('super-admin');

        if ($role === null) {
            throw new RuntimeException('The [super-admin] role must be seeded before the super-admin user. Run the roles seeder first.');
        }

        $hasher = new PasswordHasher();

        $users->create([
            'uuid' => str_uuid4(),
            'role_id' => $role['id'],
            'name' => $name,
            'email' => mb_strtolower($email),
            'email_verified_at' => now_utc()->format('Y-m-d H:i:s'),
            'password' => $hasher->hash($password),
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        fwrite(STDOUT, "  Super-admin account created for {$email}\n");
    }
};
