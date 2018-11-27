<?php

namespace Fobia\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 */
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'category_id', ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}
