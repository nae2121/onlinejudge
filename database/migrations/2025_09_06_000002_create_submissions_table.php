# database/migrations/2025_09_06_000002_create_submissions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 32);
            $table->longText('code');
            $table->string('status', 16)->default('QUEUED'); // QUEUED/RUNNING/AC/WA/TLE/RE/CE/PARTIAL
            $table->integer('points')->default(0);
            $table->integer('time_ms')->nullable();
            $table->json('detail')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('submissions');
    }
};
