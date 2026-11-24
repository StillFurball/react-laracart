<?php

namespace App\Actions\Settings;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Removes a user account. An account with orders cannot be deleted outright (the store keeps its
 * order records, which carry their own address snapshot), so it is anonymised instead: everything
 * that identifies or authenticates the person is erased and the account can never sign in again.
 *
 * Log the user out before calling this; logging out a deleted user would save it again.
 */
class DeleteAccount
{
    public function handle(User $user): void
    {
        if ($user->orders()->doesntExist()) {
            $user->delete();

            return;
        }

        DB::transaction(function () use ($user): void {
            $user->address()->delete();
            $user->cart()->delete();

            $user->forceFill([
                'name' => 'Akun dihapus',
                'email' => "deleted-{$user->id}-".Str::lower(Str::random(12)).'@deleted.invalid',
                'phone' => null,
                'password' => Str::password(64),
                'email_verified_at' => null,
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        });
    }
}
