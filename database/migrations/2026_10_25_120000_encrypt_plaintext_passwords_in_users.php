<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * users.xpass held every password in plain text. The User model now casts it as
 * "encrypted", so encrypt the existing values in place (and widen the column, since
 * encrypted values are longer than 255 characters for long passwords).
 *
 * Needs the same APP_KEY the app runs with; values already encrypted are left alone.
 */
class EncryptPlaintextPasswordsInUsers extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY xpass TEXT NULL');

        DB::table('users')->whereNotNull('xpass')->where('xpass', '!=', '')->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    if ($this->isEncrypted($user->xpass)) {
                        continue;
                    }
                    DB::table('users')->where('id', $user->id)
                        ->update(['xpass' => Crypt::encryptString($user->xpass)]);
                }
            });
    }

    public function down()
    {
        DB::table('users')->whereNotNull('xpass')->where('xpass', '!=', '')->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    if ($this->isEncrypted($user->xpass)) {
                        DB::table('users')->where('id', $user->id)
                            ->update(['xpass' => Crypt::decryptString($user->xpass)]);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}
