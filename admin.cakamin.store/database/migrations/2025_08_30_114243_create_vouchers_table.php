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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('provider_id');
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('amount', 10, 2);
            $table->integer('maximal_fee');
            $table->integer('minimal_amount');
            $table->integer('limit');
            $table->integer('use_limit');
            $table->dateTime('expired_at');
            $table->enum('status', ['active', 'inactive']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
