<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressTracker extends Model
{
    protected $fillable = [
        'done',
        'status',
        'results',
        'completed_batches',
        'total_batches'
    ];

    protected $casts = [
        'done' => 'boolean',
        'status' => 'string',
        'results' => 'array',
        'completed_batches' => 'integer',
        'total_batches' => 'integer'
    ];
}
