<?php

namespace App\Models;

use App\Traits\FilterByCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory, FilterByCompany;

    protected $fillable = [
        'employee_id',
        'gross_salary',
        'taxable_income',
        'net_salary',
        'total_deductions',
        'nssf_employee_contribution',
        'nssf_employer_contribution',
        'paye_total',
        'paye_net',
        'paye',
        'paye_relief',
        'nhif_employee_contribution',
        'nhif_employer_contribution',
        'nhif_relief',
        'nita',
        'month',
        'year',
        'housing_levy',
        'housing_levy_relief',
        'net_housing_levy',
        'payroll_date',
        'status',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static array $statuses = ['draft', 'final'];
}
