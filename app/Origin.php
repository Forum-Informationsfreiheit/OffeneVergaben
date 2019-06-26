<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Origin extends Model
{
    protected $casts = [
        'scrape' => 'boolean'
    ];

    public function scopeActive($query) {
        return $query->where('scrape',1);
    }
}
