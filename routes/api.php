<?php

use App\Http\Controllers\AbilityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\ClientFolderController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FeeNoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ObligationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/process-schedule', function () {
    Artisan::call('schedule:run --stop-when-empty');
    return response()->json(['status' => 'Schedule processed successfully']);
});

Route::get('/process-queue', function () {
    Artisan::call('queue:work --stop-when-empty');
    return response()->json(['status' => 'Queue processed successfully']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('abilities', [AbilityController::class, 'getAbilities']);

    //------------------------- DASHBOARDS -----------------------------//
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('client-dashboard', [ClientDashboardController::class, 'index']);

    //------------------------- ROLES -----------------------------------//
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role:name}', [RoleController::class, 'update']);
//    Route::resource('roles', RoleController::class);

    //------------------------ CLIENTS ---------------------------------//
    Route::apiResource('clients', ClientController::class);

    //------------------------ COMPANIES ---------------------------------//
    Route::get('recycle-bin/companies', [CompanyController::class, 'recycleBin']);
    Route::resource('companies', CompanyController::class);

    //------------------------ BUSINESSES ---------------------------------//
    Route::resource('businesses', BusinessController::class);

    //------------------------ DIRECTORS ---------------------------------//
    Route::resource('directors', DirectorController::class);

    //------------------------ SERVICES ---------------------------------//
    Route::resource('services', ServiceController::class);

    //------------------------ HR & PAYROLL ---------------------------------//
    Route::post('/employees/by-company', [EmployeeController::class, 'getByCompany']);
    Route::patch('/employees/update-salaries', [EmployeeController::class, 'updateSalaries']);
    Route::resource('employees', EmployeeController::class);

    Route::post('payroll', [PayrollController::class, 'index']);
    Route::get('payroll', [PayrollController::class, 'create']);
    Route::get('payroll/generate/{id}', [PayrollController::class, 'generatePayrollEmployee']);
    Route::post('payroll/payslips', [PayrollController::class, 'getEmployeePayslips']);
    Route::post('payroll/generate', [PayrollController::class, 'generatePayrollEmployees']);
    Route::get('payroll_print/{id}', [PayrollController::class, 'Print_Payroll']);
    Route::post('payroll/company', [PayrollController::class, 'fetchPayrollByCompanyMonthYear']);
    Route::post('payroll/update-status/{id}', [PayrollController::class, 'approvePayroll']);
    Route::post('payroll/approve', [PayrollController::class, 'approvePayrolls']);
    Route::post('/payroll/export/{format}', [PayrollController::class, 'exportPayroll']);

    //------------------------ OBLIGATIONS ---------------------------------//
    Route::post('obligations/{id}/restore', [ObligationController::class, 'restore']);
    Route::resource('obligations', ObligationController::class);

    //------------------------ TASKS ---------------------------------//
    Route::patch('/tasks/{task}/reassign', [TaskController::class, 'reassign']);
    Route::post('/tasks/complete', [TaskController::class, 'complete']);
    Route::resource('tasks', TaskController::class);

    //------------------------ FEE NOTES ---------------------------------//
    Route::get('summary-clients-fee', [FeeNoteController::class, 'summary_client']);
    Route::get('summary-companies-fee', [FeeNoteController::class, 'summary_company']);
    Route::get('/summary-client/{clientId}', [FeeNoteController::class, 'show_summary_client']);
    Route::get('/summary-company/{companyId}', [FeeNoteController::class, 'show_summary_company']);
    Route::resource('fee_notes', FeeNoteController::class);

    //------------------------ PAYMENTS ---------------------------------//
    Route::resource('payments', PaymentController::class);

    //------------------------ FILE MANAGER ---------------------------------//
    Route::get('client-folders', [ClientFolderController::class, 'index']);
    Route::get('files/{client_folder_id}', [ClientFolderController::class, 'listFiles']);
    Route::post('client-folders/{client_id}/folders/{parent_folder_id}/subfolder', [ClientFolderController::class, 'createSubFolder']);
    Route::get('client-folders/{folder_id}/content', [ClientFolderController::class, 'viewFolderContent']);
    Route::post('client-folders/{client_id}/folder', [ClientFolderController::class, 'createFolder']);
    Route::post('client-folders/{client_id}/upload', [ClientFolderController::class, 'store']);

    //------------------------ INVOICE MANAGER ---------------------------------//
    Route::post('/invoices/generate', [InvoiceController::class, 'generateInvoice']);
    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'makePayment']);
    Route::resource('invoices', InvoiceController::class);
});

