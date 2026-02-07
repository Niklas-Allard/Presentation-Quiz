<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add description and user_id to presentations table
        Schema::table('presentations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->index('user_id');
        });

        // Add group_name to questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('presentation_id');
            $table->index(['presentation_id', 'group_name', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['description', 'user_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['presentation_id', 'group_name', 'order']);
            $table->dropColumn('group_name');
        });
    }
};
