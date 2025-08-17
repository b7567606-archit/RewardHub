<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSpinData extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'spin_id',
        'amount',
    ];
}
