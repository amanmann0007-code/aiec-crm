<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateProcessTimelineStepsTable extends Migration
{
    public function up()
    {
        Schema::create('process_timeline_steps', function (Blueprint $table) {
            $table->id();
            $table->string('visa_type', 120);
            $table->string('step_key', 120);
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['visa_type', 'step_key'], 'process_timeline_steps_unique');
            $table->index(['visa_type', 'sort_order'], 'process_timeline_steps_order_index');
        });

        foreach (config('crm.process_timelines', []) as $visaType => $steps) {
            foreach (array_values($steps) as $index => $step) {
                $label = is_string($step) ? $step : $step['label'];
                $key = is_string($step) ? Str::slug($label) : ($step['key'] ?? Str::slug($label));

                DB::table('process_timeline_steps')->insert([
                    'visa_type' => $visaType,
                    'step_key' => $key,
                    'label' => $label,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('process_timeline_steps');
    }
}
