<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_policy_accepted_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('newsletter_subscription')->default(false);
            $table->boolean('data_processing_consent')->default(false);
            $table->timestamp('data_processing_consent_at')->nullable();
            $table->timestamp('data_erasure_requested_at')->nullable();
            $table->timestamp('data_anonymized_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_accepted_at',
                'terms_accepted_at',
                'marketing_consent',
                'newsletter_subscription',
                'data_processing_consent',
                'data_processing_consent_at',
                'data_erasure_requested_at',
                'data_anonymized_at',
                'ip_address',
                'user_agent',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
