<?php
/**
 * Category.php file.
 */

namespace Fobia\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['name', 'parent_id', ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id');
    }

    public function childrens()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }
}
