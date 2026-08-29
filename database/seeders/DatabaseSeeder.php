<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $repository = app(UserRepository::class);
        $email = Email::fromString('test@example.com');

        if ($repository->existsByEmail($email)) {
            return;
        }

        $repository->save(User::register(
            id: (string) Str::uuid(),
            name: 'Test User',
            email: $email,
            passwordHash: app(PasswordHasher::class)->hash('password'),
        ));
    }
}
