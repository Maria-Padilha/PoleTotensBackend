<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['slug' => 'basico'],
            [
                'name' => 'Básico',
                'price' => 99.90,
                'billing_period' => 'monthly',
                'max_totems' => 2,
                'is_active' => true,
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'profissional'],
            [
                'name' => 'Profissional',
                'price' => 249.90,
                'billing_period' => 'monthly',
                'max_totems' => 5,
                'is_active' => true,
            ]
        );
    }
}
