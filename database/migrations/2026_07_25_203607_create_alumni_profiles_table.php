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
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('major_id')->constrained('majors')->onDelete('cascade');
            $table->string('student_number')->nullable()->unique();
            $table->integer('graduation_year')->nullable();
            $table->string('current_job_title')->nullable();
            $table->string('current_company')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('is_open_to_mentor')->default(false);
            $table->timestamp('created_at')->nullable();
            
            $table->index('status');
            $table->index('graduation_year');
            $table->index('is_open_to_mentor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
    }
};
