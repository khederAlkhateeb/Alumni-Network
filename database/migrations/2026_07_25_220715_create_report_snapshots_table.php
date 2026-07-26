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
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->onDelete('cascade');
            $table->string('report_type');
            $table->json('snapshot_data')->nullable();
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at')->nullable();
            
            $table->index(['university_id', 'report_type']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
    }
};
