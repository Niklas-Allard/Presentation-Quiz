<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding database...');
        $this->command->newLine();

        // Seed in order: Users → Presentations → Questions → Participants
        $this->call([
            UserSeeder::class,
            PresentationSeeder::class,
            QuestionSeeder::class,
            ParticipantSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📝 Quick Reference:');
        $this->command->info('   - Admin login: admin@example.com / password');
        $this->command->info('   - Test presentation: "Laravel Knowledge Quiz" (10 questions)');
        $this->command->info('   - Test participants: 12 participants with scores in Redis');
    }
}
