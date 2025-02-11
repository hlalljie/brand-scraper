<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressTracker extends Model
{
    protected $fillable = [
        'done',
        'results'
    ];

    protected $casts = [
        'done' => 'boolean',
        'results' => 'array'
    ];
}
