<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerProcessStepsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_process_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('visa_type', 120)->nullable();
            $table->string('step_key', 120);
            $table->string('step_label');
            $table->unsignedSmallInteger('step_order')->default(0);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['customer_id', 'step_key'], 'customer_process_steps_unique');
            $table->index(['visa_type', 'step_key'], 'customer_process_steps_visa_step_index');
            $table->index(['completed_at', 'completed_by'], 'customer_process_steps_report_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_process_steps');
    }
}
