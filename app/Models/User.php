<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'department_id', 'role', 'is_active'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function department() { return $this->belongsTo(Department::class); }
    public function ownedDocuments() { return $this->hasMany(Document::class, 'owner_id'); }
    public function documentAccessGrants() { return $this->hasMany(DocumentAccess::class); }
    public function assignedReviews() { return $this->hasMany(DocumentVersionReview::class, 'reviewer_id'); }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isAdministrator(): bool { return $this->role === UserRole::Administrator; }
    public function isDocumentManager(): bool { return $this->role === UserRole::DocumentManager; }
    public function isReviewer(): bool { return $this->role === UserRole::Reviewer; }
}
