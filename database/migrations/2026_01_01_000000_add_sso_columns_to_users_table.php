<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom SSO ke tabel users.
     *
     * Kolom ini dibutuhkan agar data dari SSO Server (role, sains_data, avatar)
     * bisa tersimpan di database client app.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('email');
            }
            if (! Schema::hasColumn('users', 'sains_data')) {
                $table->json('sains_data')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('sains_data');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['username', 'role', 'sains_data', 'avatar', 'last_login_at'];
            $dropColumns = [];

            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $dropColumns[] = $col;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
