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
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description');

            // تغيير price إلى fullPrice
            $table->decimal('fullPrice', 8, 2);

            // تعديل الحقول الخاصة بالصور
            $table->string('mainImage')->nullable();     // الصورة الرئيسية
            $table->json('otherImages')->nullable();     // مجموعة صور إضافية

            // الحقول الجديدة
            $table->text('city');
            $table->text('location');

            $table->string('time_to_complete');
            $table->json('available_days');
            $table->json('available_hours');

            $table->decimal('book_price', 8, 2);

            $table->foreignId('provider_id')->constrained('users');
            $table->foreignId('category_id')->constrained('categories');

            $table->boolean('is_approved')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
