<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('email');
            $table->enum('gender', ['male', 'female'])->nullable()->after('dob');
            $table->enum('marital_status', ['single', 'married', 'divorced'])->nullable()->after('gender');
            $table->string('father_spouse_name')->nullable()->after('marital_status');
            $table->string('residence_country')->nullable()->after('father_spouse_name');
            $table->unsignedSmallInteger('qualification_year')->nullable()->after('qualification');
            $table->string('gap_years', 20)->nullable()->after('qualification_year');
            $table->string('english_exam', 50)->nullable()->after('english_test');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'dob',
                'gender',
                'marital_status',
                'father_spouse_name',
                'residence_country',
                'qualification_year',
                'gap_years',
                'english_exam',
            ]);
        });
    }
}
