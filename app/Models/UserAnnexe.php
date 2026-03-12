<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnnexe extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_annexes';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'annexe_id',
        'role_id',
        'is_principal',
        'assigned_by',
        'assigned_at',
        'end_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_principal' => 'boolean',
            'assigned_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * User associated with this user_annexe
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Annexe associated with this user_annexe
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class, 'annexe_id');
    }

    /**
     * Role associated with this user_annexe
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
