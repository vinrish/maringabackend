<!DOCTYPE html>
<html>
<head>
    <title>Payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</title>
</head>
<body>
<h3>Payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</h3>
<p>Dear {{ $payroll->employee->user->first_name }} {{ $payroll->employee->user->last_name }},</p>
<p>Find the attached payslip for {{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}.</p>
<p>Use Your ID number as the password</p>
</body>
</html>
