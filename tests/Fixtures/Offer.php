<?php

namespace Fobia\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Offer
 */
class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = ['product_id', 'company_id', ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
