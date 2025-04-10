<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';

    // Indica que el ID no es autoincremental
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'alternative_names',
        'description',
        'developers',
        'background_image',
        'released',
        'genres',
        'tags',
        'platforms'
    ];

    public function userFavorites()
    {
        return $this->belongsToMany(User::class, 'favorites', 'id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
