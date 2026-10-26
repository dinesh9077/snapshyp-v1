<?php

namespace App\Support;

use App\Models\UserOtp;

/**
 * Registration email OTP, kept server-side in user_otps.
 *
 * The OTP is never sent back to the client. verify() marks the email as confirmed,
 * and consumeVerified() lets one registration for that email go through.
 */
class EmailOtp
{
    private const VERIFIED = 'verified';

    public static function issue(string $email, $otp, int $minutes = 10): void
    {
        UserOtp::updateOrCreate(
            ['email' => $email],
            ['otp' => (string) $otp, 'expires_at' => now()->addMinutes($minutes)]
        );
    }

    public static function verify(string $email, $otp): bool
    {
        $record = UserOtp::where('email', $email)->first();

        if (
            !$record
            || $record->otp === self::VERIFIED
            || $record->expires_at < now()
            || !hash_equals((string) $record->otp, (string) $otp)
        ) {
            return false;
        }

        // Registration must finish within 30 minutes of verifying
        $record->update(['otp' => self::VERIFIED, 'expires_at' => now()->addMinutes(30)]);
        return true;
    }

    // Call inside the registration transaction so a failed signup keeps the email verified
    public static function consumeVerified(string $email): bool
    {
        return UserOtp::where('email', $email)
            ->where('otp', self::VERIFIED)
            ->where('expires_at', '>=', now())
            ->delete() > 0;
    }
}
