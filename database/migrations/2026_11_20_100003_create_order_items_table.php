<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->string('unit', 10)->default('pcs');
            $table->string('note')->nullable();      // "yangi uzilgan bo'lsin"
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('price')->nullable(); // kuryer to'lagan qator summasi
            $table->string('courier_note')->nullable();      // "o'rniga boshqasi olindi"
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
