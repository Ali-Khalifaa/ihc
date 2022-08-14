<?php

use Illuminate\Database\Seeder;

class CompanyFollowupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Pre-Qualified',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Qualified',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Not Qualified',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Connected',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Not Connected',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Missing potential customer',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Insignificant potential customer',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Call',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Recall',
        ]);
        $followup = \App\Models\CompanyFollowup::create([
            'name' => 'Junk Lead',
        ]);
    }
}
