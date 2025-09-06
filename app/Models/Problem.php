# app/Models/Problem.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug','title','time_limit_ms','memory_limit_mb','allowed_langs','scoring'
    ];

    protected $casts = [
        'allowed_langs' => 'array',
        'scoring'       => 'array',
    ];

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
