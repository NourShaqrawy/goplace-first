<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description');

            $table->decimal('fullPrice', 8, 2);
            $table->decimal('book_price', 8, 2);

            $table->string('main_image')->nullable();      // الصورة الرئيسية
            $table->json('other_images')->nullable();      // الصور الإضافية (JSON Array)

            $table->string('city');
            $table->string('location');

            $table->string('time_to_complete');
            $table->json('available_days');
            $table->json('available_hours');

            $table->foreignId('provider_id')->constrained('users');
            $table->foreignId('category_id')->constrained('categories');

            $table->boolean('is_approved')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
