<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'ip_address',
        'referrer',
        'device_type',
        'browser',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}