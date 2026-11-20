<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 1000)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0); // kichigi oldin
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 1000)->nullable();
            $table->string('unit', 10)->default('pcs');
            $table->unsignedBigInteger('price')->nullable(); // taxminiy narx (1 birlik uchun); haqiqiysini kuryer kiritadi
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shop_id', 'is_active', 'sort_order']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Katalogdan tanlangan bo'lsa. Mahsulot o'chirilsa ham buyurtmada nom va do'kon nomi nusxasi qoladi.
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->string('shop_name', 120)->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('shop_name');
        });
        Schema::dropIfExists('products');
        Schema::dropIfExists('shops');
    }
};
