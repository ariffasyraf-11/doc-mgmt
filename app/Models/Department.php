<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function users() { return $this->hasMany(User::class); }
    public function documents() { return $this->hasMany(Document::class); }
}
