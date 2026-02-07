<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Presentation;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presentation = Presentation::where('title', 'Laravel Knowledge Quiz')->first();

        if (! $presentation) {
            $this->command->warn('Laravel Knowledge Quiz presentation not found. Run PresentationSeeder first.');

            return;
        }

        $this->createLaravelQuestions($presentation);

        $this->command->info("Created questions for presentation: {$presentation->title}");
    }

    private function createLaravelQuestions(Presentation $presentation): void
    {
        // Question 1: Laravel Version
        $q1 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'What is the current LTS version of Laravel?',
            ],
            'time_limit_seconds' => 30,
            'order' => 1,
        ]);

        Option::create(['question_id' => $q1->id, 'text' => 'Laravel 10', 'is_correct' => false]);
        Option::create(['question_id' => $q1->id, 'text' => 'Laravel 11', 'is_correct' => true]);
        Option::create(['question_id' => $q1->id, 'text' => 'Laravel 9', 'is_correct' => false]);
        Option::create(['question_id' => $q1->id, 'text' => 'Laravel 8', 'is_correct' => false]);

        // Question 2: Eloquent ORM
        $q2 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'Which method is used to retrieve all records from a database table in Eloquent?',
            ],
            'time_limit_seconds' => 25,
            'order' => 2,
        ]);

        Option::create(['question_id' => $q2->id, 'text' => 'get()', 'is_correct' => false]);
        Option::create(['question_id' => $q2->id, 'text' => 'all()', 'is_correct' => true]);
        Option::create(['question_id' => $q2->id, 'text' => 'fetch()', 'is_correct' => false]);
        Option::create(['question_id' => $q2->id, 'text' => 'select()', 'is_correct' => false]);

        // Question 3: Blade Templates
        $q3 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'What is the Blade directive to display escaped data?',
            ],
            'time_limit_seconds' => 20,
            'order' => 3,
        ]);

        Option::create(['question_id' => $q3->id, 'text' => '{!! $data !!}', 'is_correct' => false]);
        Option::create(['question_id' => $q3->id, 'text' => '{{ $data }}', 'is_correct' => true]);
        Option::create(['question_id' => $q3->id, 'text' => '@{{ $data }}', 'is_correct' => false]);
        Option::create(['question_id' => $q3->id, 'text' => '<%= $data %>', 'is_correct' => false]);

        // Question 4: Artisan Commands
        $q4 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'Which Artisan command creates a new migration file?',
            ],
            'time_limit_seconds' => 20,
            'order' => 4,
        ]);

        Option::create(['question_id' => $q4->id, 'text' => 'php artisan create:migration', 'is_correct' => false]);
        Option::create(['question_id' => $q4->id, 'text' => 'php artisan make:migration', 'is_correct' => true]);
        Option::create(['question_id' => $q4->id, 'text' => 'php artisan new:migration', 'is_correct' => false]);
        Option::create(['question_id' => $q4->id, 'text' => 'php artisan generate:migration', 'is_correct' => false]);

        // Question 5: Middleware
        $q5 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'What is the purpose of middleware in Laravel?',
            ],
            'time_limit_seconds' => 30,
            'order' => 5,
        ]);

        Option::create(['question_id' => $q5->id, 'text' => 'To style the application', 'is_correct' => false]);
        Option::create(['question_id' => $q5->id, 'text' => 'To filter HTTP requests', 'is_correct' => true]);
        Option::create(['question_id' => $q5->id, 'text' => 'To connect to databases', 'is_correct' => false]);
        Option::create(['question_id' => $q5->id, 'text' => 'To compile assets', 'is_correct' => false]);

        // Question 6: Route Parameters
        $q6 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'How do you define an optional route parameter in Laravel?',
            ],
            'time_limit_seconds' => 25,
            'order' => 6,
        ]);

        Option::create(['question_id' => $q6->id, 'text' => 'Route::get("/user/{id}", ...)', 'is_correct' => false]);
        Option::create(['question_id' => $q6->id, 'text' => 'Route::get("/user/{id?}", ...)', 'is_correct' => true]);
        Option::create(['question_id' => $q6->id, 'text' => 'Route::get("/user/[id]", ...)', 'is_correct' => false]);
        Option::create(['question_id' => $q6->id, 'text' => 'Route::get("/user/:id", ...)', 'is_correct' => false]);

        // Question 7: Database Relationships
        $q7 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'Which Eloquent relationship method defines a one-to-many relationship?',
            ],
            'time_limit_seconds' => 25,
            'order' => 7,
        ]);

        Option::create(['question_id' => $q7->id, 'text' => 'belongsTo()', 'is_correct' => false]);
        Option::create(['question_id' => $q7->id, 'text' => 'hasMany()', 'is_correct' => true]);
        Option::create(['question_id' => $q7->id, 'text' => 'hasOne()', 'is_correct' => false]);
        Option::create(['question_id' => $q7->id, 'text' => 'manyToMany()', 'is_correct' => false]);

        // Question 8: Laravel Collections
        $q8 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'Which collection method transforms each item in the collection?',
            ],
            'time_limit_seconds' => 20,
            'order' => 8,
        ]);

        Option::create(['question_id' => $q8->id, 'text' => 'filter()', 'is_correct' => false]);
        Option::create(['question_id' => $q8->id, 'text' => 'map()', 'is_correct' => true]);
        Option::create(['question_id' => $q8->id, 'text' => 'reduce()', 'is_correct' => false]);
        Option::create(['question_id' => $q8->id, 'text' => 'each()', 'is_correct' => false]);

        // Question 9: Service Container
        $q9 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'What is the Laravel service container used for?',
            ],
            'time_limit_seconds' => 30,
            'order' => 9,
        ]);

        Option::create(['question_id' => $q9->id, 'text' => 'Storing session data', 'is_correct' => false]);
        Option::create(['question_id' => $q9->id, 'text' => 'Dependency injection and class resolution', 'is_correct' => true]);
        Option::create(['question_id' => $q9->id, 'text' => 'Managing database connections', 'is_correct' => false]);
        Option::create(['question_id' => $q9->id, 'text' => 'Caching API responses', 'is_correct' => false]);

        // Question 10: Queue Jobs
        $q10 = Question::create([
            'presentation_id' => $presentation->id,
            'content' => [
                'text' => 'Which method dispatches a job to the queue in Laravel?',
            ],
            'time_limit_seconds' => 20,
            'order' => 10,
        ]);

        Option::create(['question_id' => $q10->id, 'text' => 'Job::run()', 'is_correct' => false]);
        Option::create(['question_id' => $q10->id, 'text' => 'dispatch()', 'is_correct' => true]);
        Option::create(['question_id' => $q10->id, 'text' => 'queue()', 'is_correct' => false]);
        Option::create(['question_id' => $q10->id, 'text' => 'execute()', 'is_correct' => false]);
    }
}
