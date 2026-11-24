<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Solo se guarda el resultado interno. Nunca número, titular, caducidad ni CVC.
class DemoPayment extends Model
{
    protected $fillable = ['checkout_id', 'booking_id', 'reference', 'status', 'amount_cents', 'processed_at', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'processed_at' => 'datetime', 'refunded_at' => 'datetime'];
    }
}
