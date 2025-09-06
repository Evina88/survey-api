<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Survey;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $active = Survey::where('status', 'active')
            ->where('title', 'Post-Purchase Feedback')
            ->first();

        if (!$active) {
            // If someone ran QuestionSeeder alone, ensure survey exists
            $active = Survey::create([
                'title'       => 'Post-Purchase Feedback',
                'description' => 'Tell us about your experience after your recent purchase.',
                'status'      => 'active',
            ]);
        }

        // Seed three mixed-type questions for the active survey
        Question::updateOrCreate(
            ['survey_id' => $active->id, 'question_text' => 'How satisfied are you with your purchase?'],
            ['type' => 'scale']
        );

        Question::updateOrCreate(
            ['survey_id' => $active->id, 'question_text' => 'What did you like the most?'],
            ['type' => 'text']
        );

        Question::updateOrCreate(
            ['survey_id' => $active->id, 'question_text' => 'Would you recommend us to a friend? (Yes/No)'],
            ['type' => 'multiple_choice']
        );

        // Also attach a couple questions to the inactive survey for completeness
        $inactive = Survey::where('status', 'inactive')
            ->where('title', 'Legacy Customer Satisfaction')
            ->first();

        if ($inactive) {
            Question::updateOrCreate(
                ['survey_id' => $inactive->id, 'question_text' => 'Rate your overall satisfaction (legacy).'],
                ['type' => 'scale']
            );

            Question::updateOrCreate(
                ['survey_id' => $inactive->id, 'question_text' => 'Any comments? (legacy)'],
                ['type' => 'text']
            );
        }
    }
}
