<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();       // CL, SL, EL, ML, PL
            $table->string('name');                       // Casual Leave, etc.
            $table->string('color', 10)->default('#6366f1');
            $table->unsignedInteger('annual_limit');      // days per year
            $table->unsignedInteger('carry_forward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
