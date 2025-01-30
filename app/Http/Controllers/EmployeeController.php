<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employee::query()
            ->with(['user', 'company'])
            ->select('id', 'position', 'department', 'salary', 'employee_status', 'employee_type', 'kra_pin', 'joining_date', 'company_id', 'user_id');

        // Search functionality
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->whereHas('user', function ($subQuery) use ($searchTerm) {
                $subQuery->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%")
                    ->orWhere('email', 'like', "%$searchTerm%");
            });
        }

        // Sorting functionality
        if ($request->has('sortBy') && $request->has('orderBy')) {
            $sortBy = $request->input('sortBy');
            $orderBy = $request->input('orderBy', 'asc');
            $query->orderBy($sortBy, $orderBy);
        }

        // Pagination
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $employees = $query->paginate($itemsPerPage);

        // Structured response
        return response()->json([
            'employees' => $employees->items(),  // Current page of employees
            'total' => $employees->total(),      // Total number of employees
            'message' => 'success'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        return response()->json([
            'employeeStatusList' => EmployeeStatus::list(),
            'employeeTypeList' => EmployeeType::list(),
            'companies' => $companies,
            'message' => 'Companies retrieved successfully',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email',
            'kra_pin' => 'required|string|max:255|unique:employees,kra_pin',
            'id_no' => 'required|string|max:255|unique:employees,id_no',
            'post_address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'company_id' => 'required|integer|exists:companies,id',
            'employee_status' => ['required', 'integer', Rule::in(array_column(EmployeeStatus::cases(), 'value'))],
            'employee_type' => ['required', 'integer', Rule::in(array_column(EmployeeType::cases(), 'value'))],
            'joining_date' => 'date',
            'birth_date' => 'date',
            'salary' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request) {
            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => true,
                'allow_login' => $request->allow_login,
                'role_id' => UserRole::EMPLOYEE->value,
                'password' => Hash::make('employee123')
            ]);

            // Assign role to user
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => UserRole::EMPLOYEE->value,
            ]);

            $joining_date = Carbon::parse($request->joining_date)->format('Y-m-d H:i:s');
            $birth_date = Carbon::parse($request->birth_date)->format('Y-m-d H:i:s');

            // Create employee
            Employee::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'kra_pin' => $request->kra_pin,
                'id_no' => $request->id_no,
                'post_address' => $request->post_address,
                'post_code' => $request->post_code,
                'city' => $request->city,
                'county' => $request->county,
                'country' => $request->country,
                'position' => $request->position,
                'department' => $request->department,
                'company_id' => $request->company_id,
                'employee_status' => EmployeeStatus::from((int)$request->employee_status),
                'employee_type' => EmployeeType::from((int)$request->employee_type),
                'joining_date' => $joining_date,
                'birth_date' => $birth_date,
                'salary' => $request->salary,
            ]);
        }, 2);

        return response()->json([
            'message' => 'Successfully created employee!'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load(['user', 'company']);

        return response()->json([
            'employee' => $employee,
            'message' => 'Employee details retrieved successfully.'
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $companies = Company::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'kra_pin' => $employee->kra_pin,
                'id_no' => $employee->id_no,
                'position' => $employee->position,
                'department' => $employee->department,
                'company_id' => $employee->company_id,
                'employee_status' => $employee->employee_status->value,
                'employee_type' => $employee->employee_type->value,
                'joining_date' => $employee->joining_date,
                'birth_date' => $employee->birth_date,
                'salary' => $employee->salary,
                'post_code' => $employee->post_code,
                'post_address' => $employee->post_address,
                'county' => $employee->county,
                'city' => $employee->city,
                'country' => $employee->country,
                'user' => [
                    'first_name' => $employee->user->first_name,
                    'last_name' => $employee->user->last_name,
                    'middle_name' => $employee->user->middle_name,
                    'email' => $employee->user->email,
                    'phone' => $employee->user->phone,
                ],
            ],
            'companies' => $companies,
            'employeeStatusList' => EmployeeStatus::list(),
            'employeeTypeList' => EmployeeType::list(),
            'message' => 'Employee details ready for editing.'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->user_id,
            'kra_pin' => 'required|string|max:255',
            'id_no' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'company_id' => 'required|integer|exists:companies,id',
            'employee_status' => ['required', 'integer', Rule::in(array_column(EmployeeStatus::cases(), 'value'))],
            'employee_type' => ['required', 'integer', Rule::in(array_column(EmployeeType::cases(), 'value'))],
            'joining_date' => 'date',
            'birth_date' => 'date',
            'salary' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request, $employee) {
            // Update user information
            $employee->user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
            ]);

            // Update employee details
            $joining_date = Carbon::parse($request->joining_date)->format('Y-m-d H:i:s');
            $birth_date = Carbon::parse($request->birth_date)->format('Y-m-d H:i:s');

            $employee->update([
                'kra_pin' => $request->kra_pin,
                'id_no' => $request->id_no,
                'position' => $request->position,
                'department' => $request->department,
                'company_id' => $request->company_id,
                'employee_status' => EmployeeStatus::from((int)$request->employee_status),
                'employee_type' => EmployeeType::from((int)$request->employee_type),
                'joining_date' => $joining_date,
                'birth_date' => $birth_date,
                'salary' => $request->salary,
            ]);
        }, 2);

        return response()->json([
            'message' => 'Employee updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            // Delete the user and related employee record
            $employee->user->delete(); // Delete the associated user first
            $employee->delete(); // Delete the employee
        }, 2);

        return response()->json([
            'message' => 'Employee deleted successfully.'
        ], 200);
    }

    public function getByCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $employees = Employee::where('company_id', $request->company_id)
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->get(['employees.*', 'users.first_name', 'users.last_name', 'users.email']);

        if ($employees->isEmpty()) {
            return response()->json(['message' => 'No employees found for this company'], 404);
        }

        return response()->json([
            'employees' => $employees
        ]);
    }

    /**
     * Update Salary of the specified resource from storage.
     */
    public function updateSalaries(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'salaries' => 'required|array',
            'salaries.*.employee_id' => 'required|exists:employees,id',
            'salaries.*.salary' => 'required|numeric',
            'salaries.*.month' => 'required|integer|min:1|max:12',
            'salaries.*.year' => 'required|integer|min:2000'
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->salaries as $salaryUpdate) {
                    // Update salary in employees table
                    Employee::where('id', $salaryUpdate['employee_id'])
                        ->update(['salary' => $salaryUpdate['salary']]);

                    // Update or create salary record in salaries table
                    Salary::updateOrCreate(
                        [
                            'employee_id' => $salaryUpdate['employee_id'],
                            'month' => $salaryUpdate['month'],
                            'year' => $salaryUpdate['year'],
                        ],
                        [
                            'salary' => $salaryUpdate['salary'],
                        ]
                    );
                }
            });

            return response()->json(['message' => 'Salaries updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
//    public function updateSalaries(Request $request): \Illuminate\Http\JsonResponse
//    {
//        $request->validate([
//            'salaries' => 'required|array',
//            'salaries.*.employee_id' => 'required|exists:employees,id',
//            'salaries.*.salary' => 'required|numeric'
//        ]);
//
//        try {
//            DB::transaction(function () use ($request) {
//                foreach ($request->salaries as $salaryUpdate) {
//                    Employee::where('id', $salaryUpdate['employee_id'])
//                        ->update(['salary' => $salaryUpdate['salary']]);
//                }
//            });
//
//            return response()->json(['message' => 'Salaries updated successfully']);
//        } catch (\Exception $e) {
//            return response()->json(['error' => $e->getMessage()], 500);
//        }
//
////        DB::transaction(function () use ($request) {
////            foreach ($request->salaries as $salaryUpdate) {
////                $updates[$salaryUpdate['employee_id']] = ['salary' => $salaryUpdate['salary']];
////                $employee = Employee::find($salaryUpdate['employee_id']);
////                if ($employee) {
////                    $employee->update(['salary' => $salaryUpdate['salary']]);
////                }
////            }
////        });
////
////        return response()->json(['message' => 'Salaries updated successfully']);
//    }
}
