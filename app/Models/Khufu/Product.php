<?php

namespace App\Models\Khufu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'image1',
        'image2',
        'image3',
        'image4',
        'status',
        'price',
        'customfields'
    ];
}
