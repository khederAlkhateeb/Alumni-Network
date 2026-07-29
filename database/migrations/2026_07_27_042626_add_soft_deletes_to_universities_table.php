<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            // $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes()->after('updated_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('logo');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['updated_at', 'deleted_at', 'created_by', 'updated_by']);
        });
    }
};
