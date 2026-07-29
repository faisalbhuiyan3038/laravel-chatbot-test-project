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
        Schema::table('faqs', function (Blueprint $table) {
            $table->binary('embedding')->nullable()->after('answer');
            $table->string('embedding_model')->nullable()->after('embedding');
            $table->float('embedding_norm')->nullable()->after('embedding_model');
            $table->timestamp('embedded_at')->nullable()->after('embedding_norm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'embedding_model', 'embedding_norm', 'embedded_at']);
        });
    }
};
