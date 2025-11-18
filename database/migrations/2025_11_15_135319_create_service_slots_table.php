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
       Schema::create('service_slots', function (Blueprint $table) {
    $table->id();
   
    $table->foreignId('service_id')->constrained(); 
    
    $table->date('slot_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->integer('available_capacity'); 

   
    $table->unique(['service_id', 'slot_date', 'start_time']); 
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_slots');
    }
};
