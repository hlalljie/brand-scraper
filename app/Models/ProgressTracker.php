<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressTracker extends Model
{
    protected $fillable = [
        'done',
        'status',
        'results'
    ];

    protected $casts = [
        'done' => 'boolean',
        'status' => 'string',
        'results' => 'array'
    ];
}
