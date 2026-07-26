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
        Schema::create('mentorship_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('mentorship_programs')->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('users')->onDelete('cascade');
            $table->text('intro_message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'complete'])->default('pending');
            $table->timestamp('created_at')->nullable();
            
            $table->unique(['program_id', 'mentor_id', 'mentee_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorship_requests');
    }
};
