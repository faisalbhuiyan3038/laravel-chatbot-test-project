<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('faq or doc_section');
            $table->string('language')->default('en')->comment('en or bn');
            $table->text('title');
            $table->text('content');
            $table->binary('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->float('embedding_norm')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
