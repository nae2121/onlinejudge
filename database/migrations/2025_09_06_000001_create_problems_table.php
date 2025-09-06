# database/migrations/2025_09_06_000001_create_problems_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->integer('time_limit_ms')->default(2000);
            $table->integer('memory_limit_mb')->default(256);
            $table->json('allowed_langs')->nullable();
            $table->json('scoring')->nullable(); // {"type":"sum_subtasks","groups":{"sample":0,"small":200}}
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('problems');
    }
};
