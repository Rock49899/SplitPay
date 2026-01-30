<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'annexe_id',
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'scope',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Annexe principale de l'utilisateur
     */
    public function annexe(): BelongsTo
    {
        return $this->belongsTo(Annexe::class, 'annexe_id');
    }

    /**
     * Toutes les annexes accessibles par l'utilisateur (via user_annexes)
     */
    public function annexes(): BelongsToMany
    {
        return $this->belongsToMany(Annexe::class, 'user_annexes')
            ->withPivot(['role_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at']);
    }

    /**
     * Tous les rôles de l'utilisateur (via user_annexes)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_annexes')
            ->withPivot(['annexe_id', 'is_principal', 'assigned_by', 'assigned_at', 'end_at'])
            ->withTimestamps();
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    /**
     * Vérifier si l'utilisateur est super admin institution
     */
    public function isSuperAdminInstitution(): bool
    {
        return $this->scope === 'institution' && $this->hasRole('super_admin_institution');
    }

    /**
     * Vérifier si l'utilisateur est super admin annexe
     */
    public function isSuperAdminAnnexe(): bool
    {
        return $this->scope === 'annexe' && $this->hasRole('super_admin_annexe');
    }

   
    /**
     * Assigner l'utilisateur à une annexe 
     */
    public function assignToAnnexe(string $annexeId, string $roleId, bool $isPrincipal = false): void
    {
        $this->annexes()->attach($annexeId, [
            'role_id' => $roleId,
            'is_principal' => $isPrincipal,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        // Si c'est l'annexe principale, mettre à jour users.annexe_id
        if ($isPrincipal) {
            $this->update(['annexe_id' => $annexeId]);
        }
    }

    /**
     * Retirer l'accès d'un utilisateur à une annexe 
     */
    public function removeFromAnnexe(string $annexeId): void
    {
        $this->annexes()->detach($annexeId);

        // Si c'était l'annexe principale, la retirer
        if ($this->annexe_id === $annexeId) {
            $this->update(['annexe_id' => null]);
        }
    }

    // Changer l'annexe principale de l'utilisateur 
     
    public function switchPrincipalAnnexe(string $annexeId): void
    {
        // Vérifier que l'utilisateur a accès à cette annexe
        if (!$this->canAccessAnnexe($annexeId)) {
            throw new \Exception('L\'utilisateur n\'a pas accès à cette annexe');
        }

        $this->annexes()->updateExistingPivot(
            $this->annexes->pluck('id')->toArray(),
            ['is_principal' => false]
        );

        // Définir la nouvelle annexe principale
        $this->annexes()->updateExistingPivot($annexeId, ['is_principal' => true]);
        $this->update(['annexe_id' => $annexeId]);
    }

    /**
     * Vérifier si l'utilisateur a accès à une annexe spécifique
     */
    public function canAccessAnnexe(string $annexeId): bool
    {
        // Super Admin Institution a accès à toutes les annexes
        if ($this->isSuperAdminInstitution()) {
            return true;
        }

        // Vérifier si l'utilisateur est assigné à cette annexe
        return $this->annexes()->where('annexes.id', $annexeId)->exists();
    }

    /**
     * Récupérer les IDs de toutes les annexes accessibles
     * Utilisé pour filtrer les requêtes
     */
    public function getAccessibleAnnexeIds(): array
    {
        // Super Admin Institution voit toutes les annexes de son institution
        if ($this->isSuperAdminInstitution()) {
            return $this->annexe?->institution->annexes->pluck('id')->toArray() ?? [];
        }

        // Autres utilisateurs : uniquement leurs annexes assignées
        return $this->annexes->pluck('id')->toArray();
    }

    /**
     * Préserve l'historique des transactions mais supprime l'identité
     */
    public function anonymize(): void
    {
        $this->update([
            'name' => 'Utilisateur supprimé',
            'email' => 'deleted_' . $this->id . '@anonymized.com',
            'phone' => null,
            'is_active' => false,
        ]);

        // Détacher toutes les relations annexes
        $this->annexes()->detach();
    }

    /**
     * Vérifier si le compte est actif ésactivée
     */
    public function isAccountActive(): bool
    {
        // Vérifier si le user est actif
        if (!$this->is_active) {
            return false;
        }

        // Vérifier si son annexe principale est active
        if ($this->annexe && !$this->annexe->is_active) {
            return false;
        }

        return true;
    }
}
