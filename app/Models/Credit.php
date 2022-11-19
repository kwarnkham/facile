<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Credit extends Model
{
    use HasFactory;

    public function orderPayment()
    {
        return DB::table('order_payment')->where('id', $this->order_payment_id)->get();
    }
}
