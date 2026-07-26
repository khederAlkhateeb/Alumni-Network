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
        Schema::create('mentorship_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->onDelete('cascade');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('mentor_per_mentees_max')->default(5);
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            
            $table->index(['university_id', 'status']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorship_programs');
    }
};
