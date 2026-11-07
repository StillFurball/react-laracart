<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $recipient_name
 * @property string $phone
 * @property string $address_line
 * @property string $city
 * @property string $province
 * @property string $postal_code
 */
#[Fillable(['recipient_name', 'phone', 'address_line', 'city', 'province', 'postal_code'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{recipient_name: string, phone: string, address_line: string, city: string, province: string, postal_code: string}
     */
    public function toSnapshot(): array
    {
        return $this->only(['recipient_name', 'phone', 'address_line', 'city', 'province', 'postal_code']);
    }
}
