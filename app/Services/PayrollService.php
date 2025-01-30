<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Salary;

class PayrollService
{
//
    public function generatePayrollEmployees(array $employeeIds, int $month, int $year): array
    {
        // Get all employee IDs with payrolls for the specified month and year
        $existingPayrollEmployeeIds = Payroll::where('month', $month)
            ->where('year', $year)
            ->pluck('employee_id')
            ->toArray();

        // Identify payrolls to delete (deselected employees)
        $deselectedEmployeeIds = array_diff($existingPayrollEmployeeIds, $employeeIds);

        // Delete payrolls for deselected employees
        Payroll::whereIn('employee_id', $deselectedEmployeeIds)
            ->where('month', $month)
            ->where('year', $year)
            ->delete();

        // Process payrolls for the selected employees
        $payrolls = [];
        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);

            if ($employee) {
                $salaryRecord = Salary::where('employee_id', $employeeId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                $salary = $salaryRecord?->salary ?? $employee->salary;

                try {
                    $payroll = $this->createOrUpdatePayroll($employee, $month, $year, $salary);
                    $payrolls[] = $payroll;
                } catch (\Exception $e) {
                    $payrolls[] = ['error' => $e->getMessage()];
                }
            }
        }

        return $payrolls;
    }

    private function createOrUpdatePayroll(Employee $employee, int $month, int $year, float $salary): Payroll
    {
        $existingPayroll = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existingPayroll) {
            if ($existingPayroll->payroll_status === 'final') {
                throw new \Exception("Payroll for employee {$employee->id} for {$month}/{$year} is final and cannot be updated.");
            }

            return $this->updatePayroll($existingPayroll, $salary, $month, $year);
        }

        return $this->createPayroll($employee, $salary, $month, $year);
    }

    private function createPayroll(Employee $employee, float $salary, int $month, int $year): Payroll
    {
        return $this->calculatePayrollData(new Payroll(), $employee, $salary, $month, $year);
    }

    private function updatePayroll(Payroll $payroll, float $salary, int $month, int $year): Payroll
    {
        return $this->calculatePayrollData($payroll, $payroll->employee, $salary, $month, $year);
    }

    private function calculatePayrollData(Payroll $payroll, Employee $employee, float $basicSalary, int $month, int $year): Payroll
    {
        $nssf = $this->calculateNSSF($basicSalary, $month, $year);
        $nhif = $this->calculateNHIF($basicSalary, $month, $year);
        $housingLevy = $this->calculateHousingLevy($basicSalary);
        $taxableIncome = $basicSalary - $nssf;
        $tax = $this->calculateTax($taxableIncome);

        $payeRelief = 2400;
        $nhifRelief = $nhif * 0.15;
        $housingLevyRelief = $housingLevy * 0.15;

        $payeNet = max(0, $tax - $payeRelief - $nhifRelief - $housingLevyRelief);
        $totalDeductions = $payeNet + $nhif + $nssf + $housingLevy;
        $netPay = $taxableIncome - $totalDeductions;

        $payroll->employee_id = $employee->id;
        $payroll->month = $month;
        $payroll->year = $year;
        $payroll->gross_salary = $basicSalary;
        $payroll->taxable_income = $taxableIncome;
        $payroll->net_salary = $netPay;
        $payroll->total_deductions = $totalDeductions;
        $payroll->nssf_employee_contribution = $nssf;
        $payroll->nssf_employer_contribution = $nssf;
        $payroll->paye = $tax;
        $payroll->paye_net = $payeNet;
        $payroll->paye_relief = $payeRelief;
        $payroll->nhif_employee_contribution = $nhif;
        $payroll->nhif_employer_contribution = $nhif;
        $payroll->nhif_relief = $nhifRelief;
        $payroll->nita = 0;
        $payroll->housing_levy = $housingLevy;
        $payroll->net_housing_levy = $housingLevy - $housingLevyRelief;
        $payroll->housing_levy_relief = $housingLevyRelief;
        $payroll->payroll_date = now()->toDateString();
        $payroll->payroll_status = 'draft';

        $payroll->save();

        return $payroll;
    }

    private function calculateTax(float $taxableIncome): float
    {
        $tax = 0;
        $brackets = [
            ['limit' => 24000, 'rate' => 0.1],
            ['limit' => 8333, 'rate' => 0.25],
            ['limit' => 467667, 'rate' => 0.3],
            ['limit' => 300000, 'rate' => 0.325],
        ];

        foreach ($brackets as $bracket) {
            if ($taxableIncome > 0) {
                $tax += min($taxableIncome, $bracket['limit']) * $bracket['rate'];
                $taxableIncome -= $bracket['limit'];
            }
        }

        if ($taxableIncome > 0) {
            $tax += $taxableIncome * 0.35;
        }

        return $tax;
    }

    private function calculateNSSF(float $basicSalary, int $month, int $year): float
    {
        if ($year > 2023 || ($year === 2024 && $month >= 2)) {
            $tier1 = min($basicSalary, 7000) * 0.06;
            $tier2 = max(0, min($basicSalary - 7000, 29000)) * 0.06;
            return min($tier1 + $tier2, 2160);
        }

        return min($basicSalary * 0.06, 1080);
    }

    private function calculateNHIF(float $basicSalary, int $month, int $year): float
    {
        $nhifRates = [
            ["minSalary" => 0, "maxSalary" => 5999, "employeeContribution" => 150],
            ["minSalary" => 6000, "maxSalary" => 7999, "employeeContribution" => 300],
            ["minSalary" => 8000, "maxSalary" => 11999, "employeeContribution" => 400],
            ["minSalary" => 12000, "maxSalary" => 14999, "employeeContribution" => 500],
            ["minSalary" => 15000, "maxSalary" => 19999, "employeeContribution" => 600],
            ["minSalary" => 20000, "maxSalary" => 24999, "employeeContribution" => 750],
            ["minSalary" => 25000, "maxSalary" => 29999, "employeeContribution" => 850],
            ["minSalary" => 30000, "maxSalary" => 34999, "employeeContribution" => 900],
            ["minSalary" => 35000, "maxSalary" => 39000, "employeeContribution" => 950],
            ["minSalary" => 40000, "maxSalary" => 44999, "employeeContribution" => 1000],
            ["minSalary" => 45000, "maxSalary" => 49000, "employeeContribution" => 1100],
            ["minSalary" => 50000, "maxSalary" => 59999, "employeeContribution" => 1200],
            ["minSalary" => 60000, "maxSalary" => 69999, "employeeContribution" => 1300],
            ["minSalary" => 70000, "maxSalary" => 79999, "employeeContribution" => 1400],
            ["minSalary" => 80000, "maxSalary" => 89999, "employeeContribution" => 1500],
            ["minSalary" => 90000, "maxSalary" => 99999, "employeeContribution" => 1600],
            ["minSalary" => 100000, "maxSalary" => PHP_FLOAT_MAX, "employeeContribution" => 1700],
        ];

        if ($year < 2024 || ($year === 2024 && $month < 10)) {
            return $this->calculateOldNHIF($basicSalary, $nhifRates);
        } else {
            return $this->calculateSHIF($basicSalary);
        }
    }

    private function calculateOldNHIF(float $basicSalary, array $nhifRates): float
    {
        foreach ($nhifRates as $rate) {
            if ($basicSalary >= $rate["minSalary"] && $basicSalary <= $rate["maxSalary"]) {
                return $rate["employeeContribution"];
            }
        }
        return 0;
    }

    private function calculateSHIF(float $basicSalary): float
    {
        return max($basicSalary * 0.0275, 300);
    }

//    private function calculateHousingLevy(float $basicSalary): float
//    {
//        return $basicSalary * 0.03;
//    }
    private function calculateHousingLevy(float $basicSalary, int $month, int $year): float
    {
        // Apply housing levy only if the month is October 2023 or later
        if ($year > 2023 || ($year === 2023 && $month >= 10)) {
            return $basicSalary * 0.015;
        }
        return 0;
    }
}
