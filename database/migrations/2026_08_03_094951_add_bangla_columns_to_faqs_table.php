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
            $table->text('question_bn')->nullable()->after('answer');
            $table->text('answer_bn')->nullable()->after('question_bn');

            $table->binary('embedding_bn')->nullable()->after('embedded_at');
            $table->string('embedding_bn_model')->nullable()->after('embedding_bn');
            $table->float('embedding_bn_norm')->nullable()->after('embedding_bn_model');
            $table->timestamp('embedded_bn_at')->nullable()->after('embedding_bn_norm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn([
                'question_bn', 'answer_bn',
                'embedding_bn', 'embedding_bn_model', 'embedding_bn_norm', 'embedded_bn_at',
            ]);
        });
    }
};
