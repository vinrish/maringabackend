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

        .text-center {
            text-align: center;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            padding: 0.3rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .row:last-child {
            border-bottom: none;
        }

        .col-label {
            flex: 1;
            font-weight: bold;
            text-align: left;
        }

        .col-value {
            flex: 1;
            text-align: right;
            white-space: nowrap;
        }

        .section {
            margin-bottom: 0.5rem;
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
            body {
                margin: 0;
                padding: 0;
            }

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
    <div class="text-center">
        <h2>{{ $payroll->employee->company->name }}</h2>
        <h2>{{ $payroll->employee->company->kra_pin }}</h2>
    </div>

    <hr />

    <!-- Payslip Title -->
    <div class="text-center">
        <h2>Payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</h2>
    </div>

    <hr />

    <!-- Employee Details -->
    <div class="section">
        <div class="row">
            <span class="col-label">Name:</span>
            <span class="col-value">{{ $payroll->employee->user->first_name }} {{ $payroll->employee->user->last_name }}</span>
        </div>
        <div class="row">
            <span class="col-label">ID:</span>
            <span class="col-value">{{ $payroll->employee->id_no }}</span>
        </div>
        <div class="row">
            <span class="col-label">KRA PIN:</span>
            <span class="col-value">{{ $payroll->employee->kra_pin }}</span>
        </div>
    </div>

    <hr />

    <!-- Payments Section -->
    <div class="section">
        <div class="section-title">PAYMENTS</div>
        <div class="row">
            <span class="col-label">Gross Salary:</span>
            <span class="col-value">{{ number_format($payroll->employee->salary, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Other Pay:</span>
            <span class="col-value">{{ number_format($payroll->other_pay, 2) }}</span>
        </div>
    </div>

    <hr />

    <!-- Deductions Section -->
    <div class="section">
        <div class="section-title">DEDUCTIONS</div>
        <div class="row">
            <span class="col-label">NSSF:</span>
            <span class="col-value">{{ number_format($payroll->nssf_employee_contribution, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Taxable Income:</span>
            <span class="col-value">{{ number_format($payroll->taxable_income, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Income Tax:</span>
            <span class="col-value">{{ number_format($payroll->paye, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Personal Relief:</span>
            <span class="col-value">-{{ number_format($payroll->paye_relief, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Insurance Relief:</span>
            <span class="col-value">-{{ number_format($payroll->nhif_employee_contribution, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">AHL Relief:</span>
            <span class="col-value">-{{ number_format($payroll->housing_levy_relief, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">P.A.Y.E:</span>
            <span class="col-value">{{ number_format($payroll->paye_net, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">SHIF:</span>
            <span class="col-value">{{ number_format($payroll->nhif_employee_contribution, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Housing Levy:</span>
            <span class="col-value">{{ number_format($payroll->housing_levy, 2) }}</span>
        </div>
        <div class="row">
            <span class="col-label">Total Deductions:</span>
            <span class="col-value">{{ number_format($payroll->total_deductions, 2) }}</span>
        </div>
    </div>

    <hr />

    <!-- Net Pay Section -->
    <div class="net-pay-section">
        <div class="row">
            <span class="col-label">NET PAY:</span>
            <span class="col-value">{{ number_format($payroll->net_salary, 2) }}</span>
        </div>
    </div>
</div>
</body>
</html>
