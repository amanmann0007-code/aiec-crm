<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAgentCollaborationsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_agent_collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'agent_id']);
            $table->index(['agent_id', 'customer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_agent_collaborations');
    }
}
