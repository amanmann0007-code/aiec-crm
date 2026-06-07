<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('pid', 32)->unique();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->enum('qualification', ['10th', '12th', 'graduated', 'master'])->nullable();
            $table->string('score')->nullable();
            $table->string('visa_type')->nullable();
            $table->string('country')->nullable();
            $table->string('source')->nullable();
            $table->string('reference_name')->nullable();
            $table->foreignId('telecaller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('english_test', ['yes', 'no'])->default('no');
            $table->string('test_type')->nullable();
            $table->decimal('listening', 4, 1)->nullable();
            $table->decimal('reading', 4, 1)->nullable();
            $table->decimal('writing', 4, 1)->nullable();
            $table->decimal('speaking', 4, 1)->nullable();
            $table->decimal('overall', 4, 1)->nullable();
            $table->date('test_expiry')->nullable();
            $table->enum('previous_refusal', ['yes', 'no'])->default('no');
            $table->foreignId('assigned_counselor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'walkin',
                'assigned',
                'interested',
                'wv again',
                'pursuing ielts/pte',
                'arranging docs',
                'in process',
                'not eligible',
                'plan drop',
            ])->default('walkin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['name', 'phone', 'pid'], 'idx_search');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
