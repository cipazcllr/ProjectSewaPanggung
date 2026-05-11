<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_name',
        'employee_email',
        'position',
        'period',
        'basic_salary',
        'allowance',
        'deduction',
        'net_salary',
        'status',
        'paid_at',
        'email_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'date:Y-m-d',
            'email_sent_at' => 'datetime',
        ];
    }
}
