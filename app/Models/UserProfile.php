<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    // exp and level are changed only by LevelService (forceFill), never by mass assignment.
    protected $fillable = [
        'user_id', 'gender', 'country_code', 'city', 'birthdate', 'bio',
        'gender_visibility', 'country_visibility', 'city_visibility', 'birthdate_visibility',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function age(): ?int
    {
        return $this->birthdate ? Carbon::parse($this->birthdate)->age : null;
    }
}
