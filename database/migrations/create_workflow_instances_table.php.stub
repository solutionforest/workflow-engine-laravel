<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('definition_name');
            $table->string('definition_version')->default('1.0');
            $table->longText('definition_data');
            $table->string('state');
            $table->longText('data')->nullable();
            $table->string('current_step_id')->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('failed_steps')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['state']);
            $table->index(['definition_name']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_instances');
    }
};
