<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Exports\PayrollExport;
use App\Jobs\EmailPayroll;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Salary;
use App\Services\BulkPayrollService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class PayrollController extends Controller
{
    protected $payrollService, $bulkPayrollService;

    public function __construct(PayrollService $payrollService, BulkPayrollService $bulkPayrollService)
    {
        $this->payrollService = $payrollService;
        $this->bulkPayrollService = $bulkPayrollService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => [
                'required',
                'integer',
            ],
            'month' => [
                'required',
                'integer',
                'between:1,12',
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
            ],
            'q' => [
                'nullable',
                'string',
            ],
            'sortBy' => [
                'nullable',
                'string',
            ],
            'orderBy' => [
                'nullable',
                'in:asc,desc',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated parameters
        $company_id = $request->input('company_id');
        $month = $request->input('month');
        $year = $request->input('year');
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'first_name');
        $orderBy = $request->input('orderBy', 'asc');

        $company = Company::find($company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Build the employee query
        $query = Employee::where('company_id', $company_id)
            ->where('employee_status', EmployeeStatus::ACTIVE->value)
            ->with('user')
            ->join('users', 'employees.user_id', '=', 'users.id');

        if ($searchTerm) {
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('users.first_name', 'like', "%$searchTerm%")
                    ->orWhere('users.last_name', 'like', "%$searchTerm%")
                    ->orWhere('users.email', 'like', "%$searchTerm%");
            });
        }

        $query->orderBy('users.' . $sortBy, $orderBy);

        $employees = $query->get(['employees.*']);

        // Ensure salaries table has records for each employee
        $employees->each(function ($employee) use ($month, $year) {
            $existingSalary = Salary::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if (!$existingSalary) {
                // Add salary record for the employee if not exists
                Salary::create([
                    'employee_id' => $employee->id,
                    'salary' => $employee->salary,
                    'month' => $month,
                    'year' => $year,
                ]);
            }
        });

        // Fetch payroll data
        $payrollRecords = Payroll::whereIn('employee_id', $employees->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        // Attach payroll data to employees
        $employees->each(function ($employee) use ($payrollRecords) {
            $employee->payroll = $payrollRecords->get($employee->id);
        });

        return response()->json([
            'employees' => $employees,
            'total' => $employees->count(),
            'message' => 'success',
        ]);
    }
//    public function index(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'company_id' => [
//                'required',
//                'integer',
//            ],
//            'month' => [
//                'required',
//                'integer',
//                'between:1,12',
//            ],
//            'year' => [
//                'required',
//                'integer',
//                'min:2000',
//            ],
//            'q' => [
//                'nullable',
//                'string',
//            ],
//            'sortBy' => [
//                'nullable',
//                'string',
//            ],
//            'orderBy' => [
//                'nullable',
//                'in:asc,desc',
//            ],
//            'itemsPerPage' => [
//                'nullable',
//                'integer',
//                'min:1',
//            ],
//            'page' => [
//                'nullable',
//                'integer',
//                'min:1',
//            ],
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()->first()], 400);
//        }
//
//        // Retrieve validated parameters
//        $company_id = $request->input('company_id');
//        $month = $request->input('month');
//        $year = $request->input('year');
//        $searchTerm = $request->input('q');
//        $sortBy = $request->input('sortBy', 'first_name');
//        $orderBy = $request->input('orderBy', 'asc');
//        $itemsPerPage = $request->input('itemsPerPage', 100);
//        $page = $request->input('page', 1);
//
//        $company = Company::find($company_id);
//        if (!$company) {
//            return response()->json(['error' => 'Company not found'], 404);
//        }
//
//        // Build the employee query
//        $query = Employee::where('company_id', $company_id)
//            ->where('employee_status', EmployeeStatus::ACTIVE->value)
//            ->with('user');
//
//        $query->join('users', 'employees.user_id', '=', 'users.id');
//
//        if ($searchTerm) {
//            $query->where(function ($subQuery) use ($searchTerm) {
//                $subQuery->where('users.first_name', 'like', "%$searchTerm%")
//                    ->orWhere('users.last_name', 'like', "%$searchTerm%")
//                    ->orWhere('users.email', 'like', "%$searchTerm%");
//            });
//        }
//
//        $query->orderBy('users.' . $sortBy, $orderBy);
//
//        $employees = $query->paginate($itemsPerPage, ['employees.*'], 'page', $page);
//
//        // Ensure salaries table has records for each employee
//        $employees->getCollection()->each(function ($employee) use ($month, $year) {
//            $existingSalary = Salary::where('employee_id', $employee->id)
//                ->where('month', $month)
//                ->where('year', $year)
//                ->first();
//
//            if (!$existingSalary) {
//                // Add salary record for the employee if not exists
//                Salary::create([
//                    'employee_id' => $employee->id,
//                    'salary' => $employee->salary,
//                    'month' => $month,
//                    'year' => $year,
//                ]);
//            }
//        });
//
//        // Fetch payroll data
//        $payrollRecords = Payroll::whereNull('deleted_at')
//            ->whereIn('employee_id', $employees->pluck('id'))
//            ->where('month', $month)
//            ->where('year', $year)
//            ->get()
//            ->keyBy('employee_id');
//
//        // Attach payroll data to employees
//        $employees->getCollection()->each(function ($employee) use ($payrollRecords) {
//            $employee->payroll = $payrollRecords->get($employee->id);
//        });
//
//        return response()->json([
//            'employees' => $employees->items(),
//            'total' => $employees->total(),
//            'message' => 'success',
//        ]);
//    }
//    public function index(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'company_id' => [
//                'required',
//                'integer',
//            ],
//            'month' => [
//                'required',
//                'integer',
//                'between:1,12',
//            ],
//            'year' => [
//                'required',
//                'integer',
//                'min:2000',
//            ],
//            'q' => [
//                'nullable',
//                'string',
//            ],
//            'sortBy' => [
//                'nullable',
//                'string',
//            ],
//            'orderBy' => [
//                'nullable',
//                'in:asc,desc',
//            ],
//            'itemsPerPage' => [
//                'nullable',
//                'integer',
//                'min:1',
//            ],
//            'page' => [
//                'nullable',
//                'integer',
//                'min:1',
//            ],
//        ]);
//
//        // Handle validation errors
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()->first()], 400);
//        }
//
//        // Retrieve validated parameters
//        $company_id = $request->input('company_id');
//        $month = $request->input('month');
//        $year = $request->input('year');
//        $searchTerm = $request->input('q');
//        $sortBy = $request->input('sortBy', 'first_name'); // Default sorting by first name
//        $orderBy = $request->input('orderBy', 'asc');
//        $itemsPerPage = $request->input('itemsPerPage', 10);
//        $page = $request->input('page', 1);
//
//        // Fetch the company
//        $company = Company::find($company_id);
//
//        // Check if company exists
//        if (!$company) {
//            return response()->json(['error' => 'Company not found'], 404);
//        }
//
//        // Build the query for employees
//        $query = Employee::where('company_id', $company_id)
//            ->where('employee_status', true)
//            ->with('user');
//
//        // Join the users table to sort by user's first_name
//        $query->join('users', 'employees.user_id', '=', 'users.id');
//
//        // Search functionality
//        if ($searchTerm) {
//            $query->where(function ($subQuery) use ($searchTerm) {
//                $subQuery->where('users.first_name', 'like', "%$searchTerm%")
//                    ->orWhere('users.last_name', 'like', "%$searchTerm%")
//                    ->orWhere('users.email', 'like', "%$searchTerm%");
//            });
//        }
//
//        // Sorting functionality
//        $query->orderBy('users.' . $sortBy, $orderBy); // Here it should be correct
//
//        // Pagination
//        $employees = $query->paginate($itemsPerPage, ['employees.*'], 'page', $page);
//
//        // Retrieve payroll records for the given month and year
//        $payrollRecords = Payroll::whereNull('deleted_at')
//            ->whereIn('employee_id', $employees->pluck('id'))
//            ->where('month', $month)
//            ->where('year', $year)
//            ->get()
//            ->keyBy('employee_id'); // Key by employee_id for easy access
//
//        // Attach payroll information to employees
//        $employees->getCollection()->each(function ($employee) use ($payrollRecords) {
//            $employee->payroll = $payrollRecords->get($employee->id); // Attach payroll data
//        });
//
//        // Structured response
//        return response()->json([
//            'employees' => $employees->items(),  // Current page employees
//            'total' => $employees->total(),      // Total number of employees
//            'message' => 'success',
//        ]);
//    }

    public function generatePayrollEmployees(Request $request): \Illuminate\Http\JsonResponse
    {
        $employeeIds = $request->input('employee_ids');
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $payrolls = $this->bulkPayrollService->generatePayrollEmployees($employeeIds, $month, $year);

        foreach ($payrolls as $payroll) {
            if ($payroll instanceof Payroll) {
                $payroll->payroll_status = 'draft';
                $payroll->save();
            }
        }

        return response()->json(['payrolls' => $payrolls], 201);
    }

    /**
     * Export payroll to Excel, PDF, or CSV
     */
//
    public function exportPayroll(Request $request, string $format)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $company_id = $request->input('company_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $company = Company::find($company_id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $payrolls = Payroll::whereHas('employee', function ($query) use ($company_id) {
            $query->where('company_id', $company_id);
        })
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee.user')
            ->get();

        if ($payrolls->isEmpty()) {
            return response()->json(['error' => 'No payroll records found for the specified parameters'], 404);
        }

        // Calculate totals
        $totals = [
            'Employee Name' => 'Totals',
            'Gross Salary' => $payrolls->sum('gross_salary'),
            'Other Taxable Pay' => $payrolls->sum('other_taxable_pay'),
            'NSSF Employee Contribution' => $payrolls->sum('nssf_employee_contribution'),
            'Taxable Pay' => $payrolls->sum('taxable_income'),
            'PAYE' => $payrolls->sum('paye'),
            'PAYE Relief' => $payrolls->sum('paye_relief'),
            'Insurance Relief' => $payrolls->sum('nhif_relief'),
            'AHL Relief' => $payrolls->sum('housing_levy_relief'),
            'Net PAYE' => $payrolls->sum('paye_net'),
            'SHIF' => $payrolls->sum('nhif_employee_contribution'),
            'Housing Levy' => $payrolls->sum('net_housing_levy'),
            'Nita' => $payrolls->sum('nita'),
            'Total Deductions' => $payrolls->sum('total_deductions'),
            'Net Salary' => $payrolls->sum('net_salary'),
            'NSSF Employer Contribution' => $payrolls->sum('nssf_employer_contribution'),
            'Total NSSF' => $payrolls->sum('nssf_employee_contribution') + $payrolls->sum('nssf_employer_contribution'),
        ];

        // Format payroll data
        $formattedData = $payrolls->map(function ($payroll) {
            return [
                'Employee Name' => $payroll->employee->user->first_name . ' ' . $payroll->employee->user->last_name . ' ' . $payroll->employee->user->middle_name,
                'Gross Salary' => $payroll->gross_salary,
                'Other Taxable Pay' => $payroll->other_taxable_pay,
                'NSSF Employee Contribution' => $payroll->nssf_employee_contribution,
                'Taxable Pay' => $payroll->taxable_income,
                'PAYE' => $payroll->paye,
                'PAYE Relief' => $payroll->paye_relief,
                'Insurance Relief' => $payroll->nhif_relief,
                'AHL Relief' => $payroll->housing_levy_relief,
                'Net PAYE' => $payroll->paye_net,
                'SHIF' => $payroll->nhif_employee_contribution,
                'Housing Levy' => $payroll->net_housing_levy,
                'Nita' => $payroll->nita,
                'Total Deductions' => $payroll->total_deductions,
                'Net Salary' => $payroll->net_salary,
                'NSSF Employer Contribution' => $payroll->nssf_employer_contribution,
                'Total NSSF' => ($payroll->nssf_employee_contribution + $payroll->nssf_employer_contribution)
            ];
        });

        // Prepend title and metadata
        $monthName = \Carbon\Carbon::create()->month($month)->format('F');
        $title = "Payroll for {$company->name} {$monthName} {$year}";

        $dataForExport = collect([
            [$title],
            [],
            [
                'Employee Name',
                'Gross Salary',
                'Other Taxable Pay',
                'NSSF Employee Contribution',
                'Taxable Pay',
                'PAYE',
                'PAYE Relief',
                'Insurance Relief',
                'AHL Relief',
                'Net PAYE',
                'SHIF',
                'Housing Levy',
                'Nita',
                'Total Deductions',
                'Net Salary',
                'NSSF Employer Contribution',
                'Total NSSF',
            ],
        ])->merge($formattedData)
            ->push($totals);

        switch (strtolower($format)) {
            case 'xlsx':
                return Excel::download(new PayrollExport($dataForExport), 'payroll.xlsx');
            case 'csv':
                return Excel::download(new PayrollExport($dataForExport), 'payroll.csv');
            case 'pdf':
                $pdf = PDF::loadView('pdf.payroll', [
                    'payrolls' => $formattedData,
                    'company' => $company,
                    'month' => $month,
                    'year' => $year,
                ]);
                return $pdf->download('payroll.pdf');
            default:
                return response()->json(['error' => 'Invalid export format'], 400);
        }
    }

    public function approvePayrolls(Request $request): \Illuminate\Http\JsonResponse
    {
        $company_id = $request->company_id;
        $month = $request->month;
        $year = $request->year;

        // First, retrieve the payrolls that need to be approved
        $payrolls = Payroll::whereHas('employee', function($query) use ($company_id) {
            $query->where('company_id', $company_id);
        })
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        // Update payroll status to 'final'
        Payroll::whereHas('employee', function($query) use ($company_id) {
            $query->where('company_id', $company_id);
        })
            ->where('month', $month)
            ->where('year', $year)
            ->update(['payroll_status' => 'final']);

        // Send payslip email to each employee
        foreach ($payrolls as $payroll) {
            if ($payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
//                Mail::to($payroll->employee->user->email)->send(new PayslipMail($payroll));
//                Mail::to($payroll->employee->user->email)->queue(new PayslipMail($payroll));
                EmailPayroll::dispatch($payroll);
            }
        }

        return response()->json(['message' => 'Payrolls approved successfully and payslips sent to employees.'], 200);
    }

    public function Print_Payroll($id): \Illuminate\Http\JsonResponse
    {
        try {
            Log::info($id);

            // Fetch the payroll data along with employee and user details
            $payroll = Payroll::with(['employee.user', 'employee.company'])->findOrFail($id);

            // Return the payroll data as JSON
            return response()->json([
                'success' => true,
                'payroll' => $payroll,
            ], 200);
        } catch (\Exception $e) {
            // Return the error as a JSON response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payroll: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function fetchPayrollByCompanyMonthYear(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'company_id' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated parameters
        $company_id = $request->input('company_id');
        $month = $request->input('month');
        $year = $request->input('year');

        // Fetch the company
        $company = Company::find($company_id);

        // Check if company exists
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $employeeIds = Employee::where('company_id', $company_id)
            ->pluck('id');

        // Retrieve payroll records for the given company_id, month, and year
        $payrolls = Payroll::whereIn('employee_id', $employeeIds)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['employee.user', 'employee.company'])
            ->get();
//        $payrolls = Payroll::where('employee_id', function ($query) use ($company_id) {
//            $query->select('id')
//                ->from('employees')
//                ->where('company_id', $company_id);
////            $query->where('company_id', $company_id);
//        })
//            ->whereMonth('payroll_date', $month)
//            ->whereYear('payroll_date', $year)
////            ->with(['employee.user', 'employee.company'])
//            ->get();

        // Return JSON response with payroll records
        return response()->json([
            'payrolls' => $payrolls,
        ]);
    }

    public function getEmployeePayslips(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        try {
            // Retrieve validated parameters
            $company_id = $request->input('company_id');
            $month = $request->input('month');
            $year = $request->input('year');

            // Fetch employee IDs belonging to the selected company
            $employeeIds = Employee::where('company_id', $company_id)->pluck('id');

            // Fetch payslip data for the selected employees, month, and year
            $payslips = Payroll::whereIn('employee_id', $employeeIds)
                ->where('month', $month)
                ->where('year', $year)
                ->with(['employee.user', 'employee.company'])
                ->get();

            // Check if payslips exist
            if ($payslips->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payslips found for the selected criteria.',
                ], 404);
            }

            // Return the fetched payslip data
            return response()->json([
                'success' => true,
                'payslips' => $payslips,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payslips: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::query()
            ->whereNull('deleted_at')
            ->get();

        return response()->json([
            'companies' => $companies,
            'message' => 'Companies retrieved successfully',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payroll $payroll)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payroll $payroll)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll)
    {
        //
    }
}
