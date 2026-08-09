<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::updateOrCreate(
            ['slug' => 'belva-digital'],
            ['name' => 'Belva Digital', 'kind' => 'employer', 'priority_weight' => 1]
        );

        Organization::updateOrCreate(
            ['slug' => 'side-jobs'],
            ['name' => 'Side Jobs', 'kind' => 'client', 'priority_weight' => 2]
        );

        Organization::updateOrCreate(
            ['slug' => 'personal'],
            ['name' => 'Personal', 'kind' => 'personal', 'priority_weight' => 3]
        );
    }
}
