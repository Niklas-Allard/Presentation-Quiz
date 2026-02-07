<?php

namespace Database\Seeders;

use App\Models\Presentation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PresentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample presentation in "waiting" status
        $presentation = Presentation::create([
            'title' => 'Laravel Knowledge Quiz',
            'admin_code' => Str::random(6),
            'status' => 'waiting',
        ]);

        $this->command->info("Created presentation: {$presentation->title} (ID: {$presentation->id})");
        $this->command->info("Admin code: {$presentation->admin_code}");

        // Create another presentation in "draft" status
        $draftPresentation = Presentation::create([
            'title' => 'PHP Fundamentals Test',
            'admin_code' => Str::random(6),
            'status' => 'draft',
        ]);

        $this->command->info("Created presentation: {$draftPresentation->title} (ID: {$draftPresentation->id})");
        $this->command->info("Admin code: {$draftPresentation->admin_code}");

        // Create a finished presentation for testing
        $finishedPresentation = Presentation::create([
            'title' => 'JavaScript Basics',
            'admin_code' => Str::random(6),
            'status' => 'finished',
        ]);

        $this->command->info("Created presentation: {$finishedPresentation->title} (ID: {$finishedPresentation->id})");
        $this->command->info("Admin code: {$finishedPresentation->admin_code}");
    }
}
