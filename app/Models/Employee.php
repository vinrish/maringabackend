<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;
//        FilterByCompany;

    protected $fillable = [
        'uuid',
        'user_id',
        'kra_pin',
        'id_no',
        'post_address',
        'post_code',
        'city',
        'county',
        'country',
        'position',
        'department',
        'company_id',
        'employee_status',
        'employee_type',
        'joining_date',
        'birth_date',
        'salary',
//        'recurring',
    ];

    protected $casts = [
        'employee_status' => EmployeeStatus::class,
        'employee_type' => EmployeeType::class,
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'employee_task');
    }

    public function obligations()
    {
        return $this->belongsToMany(Obligation::class, 'employee_obligation');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}
