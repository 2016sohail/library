<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{

    protected $primaryKey = "b_id";
    
    protected $fillable = ['b_id', 'book_name', 'author', 'cover_image'];
}
