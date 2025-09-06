<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Survey;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // Active survey
        Survey::updateOrCreate(
            ['title' => 'Post-Purchase Feedback'],
            [
                'description' => 'Tell us about your experience after your recent purchase.',
                'status'      => 'active',
            ]
        );

        // Inactive survey
        Survey::updateOrCreate(
            ['title' => 'Legacy Customer Satisfaction'],
            [
                'description' => 'Older CSAT form (no longer active).',
                'status'      => 'inactive',
            ]
        );
    }
}
