<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerFeesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('purpose', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'created_at'], 'customer_fees_customer_date_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_fees');
    }
}
