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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->onDelete('cascade');
            $table->foreignId('posted_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('company');
            $table->string('location')->nullable();
            $table->enum('type', ['full_time', 'part_time', 'internship', 'remote'])->default('full_time');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('salary_range')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'closed', 'expired'])->default('active');
            $table->timestamps();
            
            $table->index(['university_id', 'status']);
            $table->index('type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
