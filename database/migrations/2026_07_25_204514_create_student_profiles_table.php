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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('major_id')->constrained('majors')->onDelete('cascade');
            $table->string('enrollment_number')->nullable()->unique();
            $table->integer('enrollment_year')->nullable();
            $table->integer('expected_graduation_year')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->timestamp('created_at')->nullable();
            
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
