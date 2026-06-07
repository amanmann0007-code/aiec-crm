<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_entries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('father_spouse_name')->nullable();
            $table->string('residence_country', 80)->nullable();
            $table->string('qualification', 120)->nullable();
            $table->unsignedSmallInteger('qualification_year')->nullable();
            $table->string('gap_years', 20)->nullable();
            $table->string('score', 50)->nullable();
            $table->string('visa_type', 120);
            $table->string('country', 80);
            $table->enum('english_test', ['yes', 'no'])->default('no');
            $table->string('test_type')->nullable();
            $table->decimal('listening', 4, 1)->nullable();
            $table->decimal('reading', 4, 1)->nullable();
            $table->decimal('writing', 4, 1)->nullable();
            $table->decimal('speaking', 4, 1)->nullable();
            $table->decimal('overall', 4, 1)->nullable();
            $table->date('test_expiry')->nullable();
            $table->enum('previous_refusal', ['yes', 'no'])->default('no');
            $table->json('refusal_countries')->nullable();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index(['converted_at', 'created_at'], 'customer_entries_queue_index');
            $table->index(['name', 'phone'], 'customer_entries_search_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_entries');
    }
}
