<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Content AI: one row per job sent in (photos + note), with the draft article and social captions it produced. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_ai_jobs')) {
            return;
        }
        Schema::create('content_ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_details')->nullOnDelete();
            $table->foreignId('blog_id')->nullable()->constrained('blog_details')->nullOnDelete();
            $table->string('area', 100)->nullable();
            $table->text('note');
            $table->json('photos');                   // public paths of the photos kept for the article
            $table->string('status', 20)->default('done'); // done | failed
            $table->json('social')->nullable();       // facebook, instagram, tiktok captions
            $table->json('checks')->nullable();       // privacy warnings and open questions from the AI
            $table->text('error')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Kept: dropping the table would lose every job and its captions.
    }
};
