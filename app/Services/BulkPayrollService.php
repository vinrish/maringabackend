<?php

namespace App\Services;

use App\Helpers\GeneralPayrollHelper;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Salary;

class BulkPayrollService {
    public function generatePayrollEmployees(array $employeeIds, int $month, int $year): array
    {
        if (empty($employeeIds)) {
            return [
                'error' => 'No employee IDs selected. Please select employees to generate payroll.'
            ];
        }

        $payrolls = [];

        // Delete payroll records for employees not in the provided list
//        $this->deletePayrollRecordsNotInList($employeeIds, $month, $year);

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);

            if ($employee) {
                $salaryRecord = Salary::where('employee_id', $employeeId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                // Use salary from the salaries table or fall back to the employee's default salary
                $salary = $salaryRecord?->salary ?? $employee->salary;

                $payroll = $this->createPayrollRecord($employee, $month, $year, $salary);
                $payrolls[] = $payroll;
            }
        }

        return $payrolls;
    }

//    private function deletePayrollRecordsNotInList(array $employeeIds, int $month, int $year): void
//    {
//        Payroll::where('month', $month)
//            ->where('year', $year)
//            ->whereNotIn('employee_id', $employeeIds)
//            ->delete();
//    }

//    private function createPayrollRecord(Employee $employee, int $month, int $year, float $salary): Payroll
//    {
//        $existingPayroll = Payroll::where('employee_id', $employee->id)
//            ->where('month', $month)
//            ->where('year', $year)
//            ->first();
//
//        if ($existingPayroll) {
//            if ($existingPayroll->payroll_status === 'final') {
//                // Abort the process if the payroll status is final
//                throw new \Exception("Payroll for employee {$employee->id} for {$month}/{$year} is final and cannot be updated.");
//            }
//
//            // Update the existing draft payroll record
//            return $this->updatePayrollRecord($existingPayroll, $employee, $month, $year, $salary);
//        }
//
//        return $this->createNewPayrollRecord($employee, $month, $year, $salary);
//    }

    private function createPayrollRecord(Employee $employee, int $month, int $year, float $salary): Payroll
    {
        $existingPayroll = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existingPayroll) {
            // Restore soft-deleted record if it exists
//            if ($existingPayroll->trashed()) {
//                $existingPayroll->restore();
//            }

            if ($existingPayroll->payroll_status === 'final') {
                throw new \Exception("Payroll for employee {$employee->id} for {$month}/{$year} is final and cannot be updated.");
            }

            return $this->updatePayrollRecord($existingPayroll, $employee, $month, $year, $salary);
        }

        return $this->createNewPayrollRecord($employee, $month, $year, $salary);
    }
    private function createNewPayrollRecord(Employee $employee, int $month, int $year, float $salary): Payroll
    {
        // Logic for creating a new payroll record
        $basicSalary = $salary;
        $nssf = $this->calculateNSSF($basicSalary, $month, $year);
        $nhif = $this->calculateNHIF($basicSalary, $month, $year);
        $housingLevy = $this->calculateHousingLevy($basicSalary, $month, $year);
        $taxable_income = $basicSalary - $nssf;
        $tax = $this->calculateTax($taxable_income);

        // Calculate PAYE relief, NHIF relief, and Housing Levy relief
        $payeRelief = 2400;  // Fixed PAYE relief
        $nhifRelief = $nhif * 0.15;  // 15% of NHIF employee contribution
        $housingLevyRelief = $housingLevy * 0.15;  // 15% of Housing Levy

        $payeNet = ($tax < $payeRelief) ? 0 : $tax - $payeRelief - $nhifRelief - $housingLevyRelief;
        $totalDeductions = $payeNet + $nhif + $nssf + $housingLevy;

        $pay_after_tax = $taxable_income - $payeNet;
        $netPay = $pay_after_tax - $nhif - $housingLevy;

        $payroll = new Payroll();
        $payroll->employee_id = $employee->id;
        $payroll->month = $month;
        $payroll->year = $year;
        $payroll->gross_salary = $basicSalary;
        $payroll->taxable_income = $basicSalary - $nssf;
        $payroll->net_salary = $netPay;
        $payroll->total_deductions = $totalDeductions;
        $payroll->nssf_employee_contribution = $nssf;
        $payroll->nssf_employer_contribution = $nssf; // Adjust as necessary
        $payroll->paye = $tax;  // Use calculated tax as PAYE
        $payroll->paye_net = $payeNet; // Net PAYE after relief
        $payroll->paye_relief = $payeRelief;
        $payroll->nhif_employee_contribution = $nhif;
        $payroll->nhif_employer_contribution = $nhif; // Adjust as necessary
        $payroll->nhif_relief = $nhifRelief;
        $payroll->nita = 50;  // Add your NITA calculation logic if needed
        $payroll->housing_levy = $housingLevy;
        $payroll->net_housing_levy = $housingLevy - $housingLevyRelief;  // Net Housing Levy after relief
        $payroll->housing_levy_relief = $housingLevyRelief;
        $payroll->payroll_date = date('Y-m-d');
        $payroll->payroll_status = 'draft';  // Default status as draft

        $payroll->save();
        return $payroll;
    }

    private function updatePayrollRecord(Payroll $payroll, Employee $employee, int $month, int $year, float $salary): Payroll
    {
        $basicSalary = $salary;
        $nssf = $this->calculateNSSF($basicSalary, $month, $year);
        $nhif = $this->calculateNHIF($basicSalary, $month, $year);
        $housingLevy = $this->calculateHousingLevy($basicSalary, $month, $year);
        $taxable_income = $basicSalary - $nssf;
        $tax = $this->calculateTax($taxable_income);

        // Calculate PAYE relief, NHIF relief, and Housing Levy relief
        $payeRelief = 2400;  // Fixed PAYE relief
        $nhifRelief = $nhif * 0.15;  // 15% of NHIF employee contribution
        $housingLevyRelief = $housingLevy * 0.15;  // 15% of Housing Levy

        $payeNet = ($tax < $payeRelief) ? 0 : $tax - $payeRelief - $nhifRelief - $housingLevyRelief;
        $totalDeductions = $payeNet + $nhif + $nssf + $housingLevy;

        $pay_after_tax = $taxable_income - $payeNet;
        $netPay = $pay_after_tax - $nhif - $housingLevy;

        // Update the existing payroll record
        $payroll->gross_salary = $basicSalary;
        $payroll->taxable_income = $basicSalary - $nssf;
        $payroll->net_salary = $netPay;
        $payroll->total_deductions = $totalDeductions;
        $payroll->nssf_employee_contribution = $nssf;
        $payroll->nssf_employer_contribution = $nssf; // Adjust as necessary
        $payroll->paye = $tax;  // Use calculated tax as PAYE
        $payroll->paye_net = $payeNet; // Net PAYE after relief
        $payroll->paye_relief = $payeRelief;
        $payroll->nhif_employee_contribution = $nhif;
        $payroll->nhif_employer_contribution = $nhif; // Adjust as necessary
        $payroll->nhif_relief = $nhifRelief;
        $payroll->nita = 50;  // Add your NITA calculation logic if needed
        $payroll->housing_levy = $housingLevy;
        $payroll->net_housing_levy = $housingLevy - $housingLevyRelief;  // Net Housing Levy after relief
        $payroll->housing_levy_relief = $housingLevyRelief;
        $payroll->payroll_date = date('Y-m-d');

        $payroll->save(); // Save the updated payroll record
        return $payroll;
    }

    private function calculateTax(float $taxable_income): float
    {
        $tax = 0;

        // First Ksh 24,000 at 10%
        if ($taxable_income > 0) {
            $tax += min($taxable_income, 24000) * 0.1;
            $taxable_income -= 24000;
        }

        // Next Ksh 8,333 at 25%
        if ($taxable_income > 0) {
            $tax += min($taxable_income, 8333) * 0.25;
            $taxable_income -= 8333;
        }

        // Next Ksh 467,667 at 30%
        if ($taxable_income > 0) {
            $tax += min($taxable_income, 467667) * 0.3;
            $taxable_income -= 467667;
        }

        // Next Ksh 300,000 at 32.5%
        if ($taxable_income > 0) {
            $tax += min($taxable_income, 300000) * 0.325;
            $taxable_income -= 300000;
        }

        // Amounts above Ksh 800,000 at 35%
        if ($taxable_income > 0) {
            $tax += $taxable_income * 0.35;
        }

        return $tax;
    }

    private function calculateNSSF(float $basicSalary, int $month, int $year): float
    {
        // From February 2024 onwards, NSSF is calculated based on the new tiered system
        if ($year > 2023 || ($year === 2024 && $month >= 2)) {
            $nssfContribution = 0;

            // Tier 1: 6% on the first KES 7,000
            if ($basicSalary <= 7000) {
                $nssfContribution = $basicSalary * 0.06;
            } else {
                // For salaries above KES 7,000, we calculate Tier 1 (up to KES 7,000) and Tier 2
                $tier1Contribution = 7000 * 0.06;
                $remainingSalary = $basicSalary - 7000;

                // Tier 2: 6% on salary above KES 7,000, but capped at KES 36,000
                if ($remainingSalary <= 29000) {
                    $tier2Contribution = $remainingSalary * 0.06;
                } else {
                    // If salary exceeds KES 36,000, cap the Tier 2 contribution
                    $tier2Contribution = 29000 * 0.06;
                }

                $nssfContribution = $tier1Contribution + $tier2Contribution;
            }

            // Cap the total NSSF contribution at KES 2,160
            return min($nssfContribution, 2160);
        }

        // For earlier months (before February 2024), use the previous calculation logic
        return min($basicSalary * 0.06, 1080); // Previous ceiling rate before 2024
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

    private function calculateHousingLevy(float $basicSalary, int $month, int $year): float
    {
        // Apply housing levy only if the month is October 2023 or later
        if ($year > 2023 || ($year === 2023 && $month >= 10)) {
            return $basicSalary * 0.015;
        }
        return 0;
    }
}
