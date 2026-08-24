<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $fillable =[
        'user_id',
        'original_url',
        'short_code',
        'last_clicked_at',
    ];

    protected $casts = [
        'last_clicked_at' =>'datetime',
    ];
}
