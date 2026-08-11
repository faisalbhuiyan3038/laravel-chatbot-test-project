<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::updateOrCreate(
            ['slug' => 'ansar_recruitment'],
            [
                'name' => 'Ansar Recruitment',
                'aliases' => ['ansar', 'vdp', 'ansar recruitment'],
                'support_contacts' => [
                    'phone' => '09677112244'
                ],
                'is_active' => true,
            ]
        );
    }
}
