<?php

use NovaPhp\Database\Migration;
use NovaPhp\Database\Schema\Schema;

class testTbl extends Migration
{
    public function up(): void
    {
        Schema::create('denemeTbl', function ($table) {
            $table->uuid('id');
            $table->string('Adi', 36);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denemeTbl');
    }
}