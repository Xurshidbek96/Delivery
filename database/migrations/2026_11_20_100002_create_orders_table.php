<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();
            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->index();

            // Manzil — buyurtma vaqtidagi nusxa (mijoz manzilini keyin o'zgartirsa ham tarix buzilmaydi)
            $table->string('address');
            $table->string('entrance', 20)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('apartment', 20)->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 20);

            $table->text('note')->nullable();          // mijoz izohi
            $table->text('staff_note')->nullable();    // moderator izohi (ichki)

            // Pul — butun so'mda. items_total kuryer xariddan keyin kiritadi.
            $table->unsignedBigInteger('items_total')->default(0);
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->unsignedBigInteger('service_fee')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('payment_method', 20)->default('cash');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('receipt_path')->nullable(); // chek rasmi

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('shopping_started_at')->nullable();
            $table->timestamp('delivering_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by_role', 20)->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['courier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
