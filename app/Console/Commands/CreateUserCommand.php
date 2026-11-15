<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    protected $signature = 'cianita:user:create {email}';

    protected $description = 'Cria uma conta local sem expor a senha no historico do shell';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->fails()) {
            $this->error('E-mail invalido.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ja existe uma conta com esse e-mail.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nome'));
        $password = $this->secret('Senha (minimo 12 caracteres)');
        $confirmation = $this->secret('Confirme a senha');

        if ($name === '' || mb_strlen($name) > 255 || ! is_string($password)
            || strlen($password) < 12 || $password !== $confirmation) {
            $this->error('Dados invalidos ou senhas diferentes.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Conta criada.');

        return self::SUCCESS;
    }
}
