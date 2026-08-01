<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\User;
use App\Models\ReportSnapshot;
use Illuminate\Database\Seeder;

class ReportSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        University::all()->each(function (University $university) use ($userIds) {
            ReportSnapshot::factory()
                ->count(rand(2, 4))
                ->create([
                    'university_id' => $university->id,
                    'generated_by' => $userIds->random(),
                ]);
        });
    }
}
