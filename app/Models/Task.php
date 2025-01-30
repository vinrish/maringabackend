<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'due_date',
        'status',
        'obligation_id',
    ];

    public function obligation()
    {
        return $this->belongsTo(Obligation::class, 'obligation_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_task');
    }
}
