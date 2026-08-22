<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('avatar')->nullable()->after('password');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'username' => 'user'.$user->id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar']);
        });
    }
};
