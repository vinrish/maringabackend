<!DOCTYPE html>
<html>
<head>
    <title>Payroll Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .totals-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .signature-box {
            flex: 1;
            border: 1px solid #000;
            padding: 20px;
            text-align: center;
        }
        .signature-box h4 {
            margin-bottom: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin: 40px 0;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .date {
            text-align: left;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<h2>Payroll Report for {{ $company->name }}</h2>
<p>Month: {{ $month }} | Year: {{ $year }}</p>
<table>
    <thead>
    <tr>
        <th>Employee Name</th>
        <th>Gross Salary</th>
        <th>Net Salary</th>
        <th>Taxable Income</th>
        <th>Total Deductions</th>
        <th>Month</th>
        <th>Year</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($payrolls as $payroll)
        <tr>
            <td>{{ $payroll['Employee Name'] }}</td>
            <td>{{ $payroll['Gross Salary'] }}</td>
            <td>{{ $payroll['Net Salary'] }}</td>
            <td>{{ $payroll['Taxable Income'] }}</td>
            <td>{{ $payroll['Total Deductions'] }}</td>
            <td>{{ $payroll['Month'] }}</td>
            <td>{{ $payroll['Year'] }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="totals-row">
        <td>Totals</td>
        <td>{{ $payrolls->sum('Gross Salary') }}</td>
        <td>{{ $payrolls->sum('Net Salary') }}</td>
        <td>{{ $payrolls->sum('Taxable Income') }}</td>
        <td>{{ $payrolls->sum('Total Deductions') }}</td>
        <td colspan="2"></td>
    </tr>
    </tfoot>
</table>

<div class="signature-section">
    <div class="signature-box">
        <h4>Prepared By</h4>
        <div class="signature-line"></div>
        <p class="date">Date: __________________</p>
    </div>
    <div class="signature-box">
        <h4>Approved By</h4>
        <div class="signature-line"></div>
        <p class="date">Date: __________________</p>
    </div>
</div>
</body>
</html>
