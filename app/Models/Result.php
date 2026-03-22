<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'total_score',
        'category_scores',
        'recommended_career',
        'skill_gaps',
        'roadmap',
    ];

    protected $casts = [
        'category_scores' => 'array',
        'skill_gaps' => 'array',
        'roadmap' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
