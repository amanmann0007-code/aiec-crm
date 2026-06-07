<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQualificationsTable extends Migration
{
    public function up()
    {
        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (['10th', '12th', 'graduated', 'master'] as $qualification) {
            DB::table('qualifications')->insert([
                'name' => $qualification,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('ALTER TABLE customers MODIFY qualification VARCHAR(120) NULL');
    }

    public function down()
    {
        DB::statement("ALTER TABLE customers MODIFY qualification ENUM('10th', '12th', 'graduated', 'master') NULL");
        Schema::dropIfExists('qualifications');
    }
}
