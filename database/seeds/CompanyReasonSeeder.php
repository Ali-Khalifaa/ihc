<?php

use Illuminate\Database\Seeder;

class CompanyReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Send by Email',
            'company_followup_id' => 2
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Send by Whats app',
            'company_followup_id' => 2
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Send by SMS',
            'company_followup_id' => 2
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Follow Up',
            'company_followup_id' => 3
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'I cannot confirm time',
            'company_followup_id' => 3
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Time is not fit to me',
            'company_followup_id' => 4
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'I am not ready',
            'company_followup_id' => 4
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Cancel for time',
            'company_followup_id' => 5
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Cancel for financial reasons',
            'company_followup_id' => 5
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'No Answer',
            'company_followup_id' => 6
        ]);

        $reason = \App\Models\CompanyFollowupReason::create([
            'name' => 'Is not available',
            'company_followup_id' => 6
        ]);
    }
}
