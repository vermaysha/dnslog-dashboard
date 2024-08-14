<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Rpz extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bind9_rpz';
}
