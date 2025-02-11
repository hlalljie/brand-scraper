<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressTracker extends Model
{
    protected $fillable = ['progress'];

    protected $casts = [
        'progress' => 'array'
    ];
}
