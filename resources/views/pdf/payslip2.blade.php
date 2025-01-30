{{--<!DOCTYPE html>--}}
{{--<html>--}}
{{--<head>--}}
{{--    <title>Payslip</title>--}}
{{--    <style>--}}
{{--        /* Add your CSS styles here */--}}
{{--        body {--}}
{{--            font-family: Arial, sans-serif;--}}
{{--            margin: 0;--}}
{{--            padding: 20px;--}}
{{--        }--}}
{{--        .header {--}}
{{--            text-align: center;--}}
{{--            margin-bottom: 20px;--}}
{{--        }--}}
{{--        .content {--}}
{{--            width: 100%;--}}
{{--            border-collapse: collapse;--}}
{{--        }--}}
{{--        .content, .content th, .content td {--}}
{{--            border: 1px solid #000;--}}
{{--        }--}}
{{--        .content th, .content td {--}}
{{--            padding: 10px;--}}
{{--            text-align: left;--}}
{{--        }--}}
{{--    </style>--}}
{{--</head>--}}
{{--<body>--}}
{{--<div class="header">--}}
{{--    <h1>Payslip</h1>--}}
{{--    @if($company)--}}
{{--        <p>{{ $company->name }}</p>--}}
{{--        <p>{{ $company->address }}</p>--}}
{{--        <p>{{ $company->phone }}</p>--}}
{{--    @else--}}
{{--        <p>Company information not available.</p>--}}
{{--    @endif--}}
{{--</div>--}}
{{--<table class="content">--}}
{{--    <tr>--}}
{{--        <th>Employee Name</th>--}}
{{--        <td>@if($employee) {{ $employee->first_name }} {{ $employee->last_name }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>ID</th>--}}
{{--        <td>@if($employee) {{ $employee->id_no }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>KRA PIN</th>--}}
{{--        <td>@if($employee) {{ $employee->kra_pin }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>Department</th>--}}
{{--        <td>@if($employee) {{ $employee->department }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>Position</th>--}}
{{--        <td>@if($employee) {{ $employee->position }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>Gross Salary</th>--}}
{{--        <td>@if($payroll) {{ $payroll->gross_salary }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <th>Net Salary</th>--}}
{{--        <td>@if($payroll) {{ $payroll->net_salary }} @else N/A @endif</td>--}}
{{--    </tr>--}}
{{--    <!-- Add more fields as needed -->--}}
{{--</table>--}}
{{--</body>--}}
{{--</html>--}}

    <!DOCTYPE html>
<html>
<head>
    <title>Payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }

        .content {
            width: 80mm;
            height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-sizing: border-box;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .col {
            flex: 1;
        }

        .text-center {
            text-align: center;
        }

        .detail-item, .deduction-item, .pay-item {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .label {
            font-weight: bold;
            text-align: left;
        }

        .value {
            margin-left: auto;
            text-align: right;
            white-space: nowrap;
        }

        .detail-item:last-child, .deduction-item:last-child, .pay-item:last-child {
            border-bottom: none;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        hr {
            border: none;
            border-top: 0.5px solid black;
            margin: 0.5rem 0;
        }

        .net-pay-section {
            font-size: 12px;
            font-weight: bold;
        }

        @media print {
            .content {
                padding: 10mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>
<div class="content">
    <!-- Company Info -->
    <div class="row text-center">
        <div class="col">
            <h2>{{ $payroll->employee->company->name }}</h2>
            <h2>{{ $payroll->employee->company->kra_pin }}</h2>
        </div>
    </div>

    <hr />

    <!-- Payslip Title -->
    <div class="row text-center">
        <div class="col">
            <h2>Payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</h2>
        </div>
    </div>

    <hr />

    <!-- Employee Details -->
    <div class="details-section">
        <div class="row detail-item">
            <div class="col label">Name:</div>
            <div class="col value">{{ $payroll->employee->user->first_name }} {{ $payroll->employee->user->last_name }}</div>
        </div>
        <div class="row detail-item">
            <div class="col label">ID:</div>
            <div class="col value">{{ $payroll->employee->id_no }}</div>
        </div>
        <div class="row detail-item">
            <div class="col label">KRA PIN:</div>
            <div class="col value">{{ $payroll->employee->kra_pin }}</div>
        </div>
    </div>

    <hr />

    <!-- Payments Section -->
    <div class="details-section">
        <div class="row section-title">PAYMENTS</div>
        <div class="row detail-item">
            <div class="col label">Gross Salary:</div>
            <div class="col value">{{ number_format($payroll->employee->salary, 2) }}</div>
        </div>
        <div class="row detail-item">
            <div class="col label">Other Pay:</div>
            <div class="col value">{{ number_format($payroll->other_pay, 2) }}</div>
        </div>
    </div>

    <hr />

    <!-- Deductions Section -->
    <div class="deductions-section">
        <div class="row section-title">DEDUCTIONS</div>
        <div class="row deduction-item">
            <div class="col label">NSSF:</div>
            <div class="col value">{{ number_format($payroll->nssf_employee_contribution, 2) }}</div>
        </div>
        <!-- Add other deductions here -->
        <div class="row deduction-item">
            <div class="col label">Total Deductions:</div>
            <div class="col value">{{ number_format($payroll->total_deductions, 2) }}</div>
        </div>
    </div>

    <hr />

    <!-- Net Pay Section -->
    <div class="net-pay-section">
        <div class="row pay-item">
            <div class="col label">NET PAY:</div>
            <div class="col value">{{ number_format($payroll->net_salary, 2) }}</div>
        </div>
    </div>
</div>
</body>
</html>
