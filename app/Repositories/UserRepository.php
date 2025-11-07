<?php

namespace App\Repositories;


use App\Models\User;

class UserRepository
{
    public function findByTelephone(string $telephone): ?User
    {
        return User::where('telephone', $telephone)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
