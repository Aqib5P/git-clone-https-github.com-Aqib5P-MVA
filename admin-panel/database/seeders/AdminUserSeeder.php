<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = (string) config("admin.username");
        $password = (string) config("admin.password");

        if ($username === "" || $password === "" || $password === "change_me") {
            return;
        }

        AdminUser::updateOrCreate(
            ["username" => $username],
            ["password" => Hash::make($password)]
        );
    }
}
