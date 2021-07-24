<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BookUser extends Model
{
    const ACTIVE = 1;
    const INACTIVE = 0;
    protected $fillable = ['id', 'u_id', 'b_id', 'is_active', 'start_date', 'end_date'];

    public function bookBelongs()
    {
        return $this->belongsTo('App\Book', 'b_id', 'b_id');
    }

    public function userBelongs()
    {
        return $this->belongsTo('App\User', 'u_id', 'u_id');
    }
    
}
