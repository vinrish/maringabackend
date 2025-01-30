<?php

namespace App\Helpers;

class GeneralPayrollHelper
{
    public static function compute_payroll($payroll_items, $gross_salary)
    {
        $items = [];
        $deductions = [];
        $total_deductions = 0;
        $total_additions = 0;
        $total_net_salary = 0;
        $taxable_income = 0;


        // put all items here, indicate whether deduction, addition, relief etc
        $all_items = [];

        $all_items[] = [
            'slug' => 'basic_salary',
            'name' => 'Basic Salary',
            'amount' => $gross_salary,
            'type' => 'fixed',
            'deduction' => false
        ];

        $housing_levy = $gross_salary * 0.015;

        $all_items[] = [
            'slug' => 'housing_levy_employee',
            'name' => 'Housing Levy Employee',
            'amount' => $housing_levy,
            'type' => 'fixed',
            'deduction' => true
        ];

        $all_items[] = [
            'slug' => 'housing_levy_employer',
            'name' => 'Housing Levy Employer',
            'amount' => $housing_levy,
            'type' => 'fixed',
            'deduction' => true
        ];

        // calculate the net salary, on payroll category item, check type if is deduction or addition
        foreach($payroll_items as $item) {
            if($item->type == 'Deductions') {
                $total_deductions -= $item->amount;

                if($item->rate_type == 'graduated') {
                    // value_json": "[{\"range\": \"0-50000\", \"value\": \"300\"}]"
                    $range = json_decode($item->value_json);
                    $graduated_type = $item->graduated_rate_type;

                    // check if gross salary falls in any of the range and use the rate
                    foreach($range as $r) {
                        $range_arr = explode('-', $r->range);
                        $min = $range_arr[0];
                        $max = $range_arr[1];

                        // If max is above, set a huge number
                        if($max == 'above' || $max == 'Above' || $max == 'ABOVE') {
                            $max = 99999999999;
                        }

                        if($min == 'below' || $min == 'Below' || $min == 'BELOW') {
                            $min = -99999999999;
                        }

                        // check range
                        if($gross_salary >= $min && $gross_salary <= $max) {
                            if ($graduated_type == 'percent') {
                                $total_deductions -= ($gross_salary * $r->value) / 100;
                                $items[] = [
                                    'name' => $item->name,
                                    'amount' => ($gross_salary * $r->value) / 100,
                                    'type' => 'graduated',
                                    'rate_type' => 'graduated_percent',
                                    'deduction' => true
                                ];

                                $all_items[] = [
                                    'slug' => str_replace(' ', '_', strtolower($item->name)),
                                    'name' => $item->name,
                                    'amount' => ($gross_salary * $r->value) / 100,
                                    'type' => 'graduated',
                                    'rate_type' => 'graduated_percent',
                                    'deduction' => true
                                ];

                            } else {
                                $total_deductions -= $r->value;
                                $items[] = [
                                    'name' => $item->name,
                                    'amount' => $r->value,
                                    'type' => 'graduated',
                                    'rate_type' => 'graduated_fixed',
                                    'deduction' => true
                                ];

                                $all_items[] = [
                                    'slug' => str_replace(' ', '_', strtolower($item->name)),
                                    'name' => $item->name,
                                    'amount' => $r->value,
                                    'type' => 'graduated',
                                    'rate_type' => 'graduated_fixed',
                                    'deduction' => true
                                ];
                            }
                        }
                    }
                } elseif($item->rate_type == 'percent') {
                    $total_deductions -= ($gross_salary * $item->value) / 100;
                    $items[] = [
                        'name' => $item->name,
                        'amount' => ($gross_salary * $item->value) / 100,
                        'type' => 'percentage',
                        'deduction' => true
                    ];

                    $all_items[] = [
                        'slug' => str_replace(' ', '_', strtolower($item->name)),
                        'name' => $item->name,
                        'amount' => ($gross_salary * $item->value) / 100,
                        'type' => 'percentage',
                        'deduction' => true
                    ];
                } else {
                    $total_deductions -= $item->value;
                    $items[] = [
                        'name' => $item->name,
                        'amount' => $item->value,
                        'type' => 'fixed',
                        'deduction' => true
                    ];

                    $all_items[] = [
                        'slug' => str_replace(' ', '_', strtolower($item->name)),
                        'name' => $item->name,
                        'amount' => $item->value,
                        'type' => 'fixed',
                        'deduction' => true
                    ];
                }
            } elseif($item->type == 'Earnings') {
                $total_additions += $item->amount;

                if($item->rate_type == 'graduated') {
                    // value_json": "[{\"range\": \"0-50000\", \"value\": \"300\"}]"
                    $range = json_decode($item->value_json);
                    $graduated_type = $item->graduated_rate_type;

                    // check if gross salary falls in any of the range and use the rate
                    foreach($range as $r) {
                        $range_arr = explode('-', $r->range);
                        $min = $range_arr[0];
                        $max = $range_arr[1];

                        // If max is above, set a huge number
                        if($max == 'above' || $max == 'Above' || $max == 'ABOVE') {
                            $max = 99999999999;
                        }

                        if($min == 'below' || $min == 'Below' || $min == 'BELOW') {
                            $min = -99999999999;
                        }

                        if ($graduated_type == 'percent') {
                            $total_additions += ($gross_salary * $r->value) / 100;
                            $items[] = [
                                'name' => $item->name,
                                'amount' => ($gross_salary * $r->value) / 100,
                                'type' => 'graduated',
                                'type_name' => 'graduated_percent',
                                'deduction' => false
                            ];

                            $all_items[] = [
                                'slug' => str_replace(' ', '_', strtolower($item->name)),
                                'name' => $item->name,
                                'amount' => ($gross_salary * $r->value) / 100,
                                'type' => 'graduated',
                                'type_name' => 'graduated_percent',
                                'deduction' => false
                            ];
                        } else {
                            $total_additions += $r->value;
                            $items[] = [
                                'name' => $item->name,
                                'amount' => $r->value,
                                'type' => 'graduated',
                                'type_name' => 'graduated_fixed',
                                'deduction' => false
                            ];

                            $all_items[] = [
                                'slug' => str_replace(' ', '_', strtolower($item->name)),
                                'name' => $item->name,
                                'amount' => $r->value,
                                'type' => 'graduated',
                                'type_name' => 'graduated_fixed',
                                'deduction' => false
                            ];
                        }
                    }
                } elseif($item->rate_type == 'percentage') {
                    $total_additions += ($gross_salary * $item->value) / 100;
                    $items[] = [
                        'name' => $item->name,
                        'amount' => ($gross_salary * $item->value) / 100,
                        'type' => 'percentage',
                        'deduction' => false
                    ];

                    $all_items[] = [
                        'slug' => str_replace(' ', '_', strtolower($item->name)),
                        'name' => $item->name,
                        'amount' => ($gross_salary * $item->value) / 100,
                        'type' => 'percentage',
                        'deduction' => false
                    ];
                } else {
                    $total_additions += $item->value;
                    $items[] = [
                        'name' => $item->name,
                        'amount' => $item->value,
                        'type' => 'fixed',
                        'deduction' => false
                    ];

                    $all_items[] = [
                        'slug' => str_replace(' ', '_', strtolower($item->name)),
                        'name' => $item->name,
                        'amount' => $item->value,
                        'type' => 'fixed',
                        'deduction' => false
                    ];
                }
            }
        }

        foreach($items as $key => $item) {
            if($item['name'] == 'NHIF') {
                $items[] = [
                    'name' => 'NHIF Relief',
                    'amount' => ($item['amount'] * 0.2),
                    'type' => 'percentage',
                    'deduction' => false
                ];

                $total_additions += ($item['amount'] * 0.2);
            }

            if($item['name'] == 'PAYE') {
                if($item['amount'] > 2400) {
                    $items[] = [
                        'name' => 'PAYE',
                        'amount' => $item['amount'] - 2400,
                        'type' => 'fixed',
                        'deduction' => true
                    ];

                    $total_deductions -= 2400;

                    // unset($items[$key]);
                } else {
                    $items[] = [
                        'name' => 'PAYE',
                        'amount' => 0,
                        'type' => 'fixed',
                        'deduction' => true
                    ];

                    // remove the item
                    $total_deductions -= $item['amount'];
                    // unset($items[$key]);
                }
            }
        }

        // calculate the net salary
        $total_net_salary = $gross_salary + $total_additions + $total_deductions;
        $taxable_income = $gross_salary - $total_deductions;

        $new_calculated_payroll = [];
        $new_calculated_payroll['basic_salary'] = $gross_salary;

        // check if we have NSSF in all_items and deduct it from gross salary
        foreach($all_items as $item) {
            if($item['slug'] == 'nssf') {
                $new_calculated_payroll['basic_salary'] -= $item['amount'];
                $taxable_income -= $item['amount'];
                $new_calculated_payroll['taxable_income'] = $taxable_income;
                $new_calculated_payroll['nssf'] = $item['amount'];
                // $new_calculated_payroll['net_salary'] = $taxable_income;
            }

            // if the thers apart from nssf, if they are deductions, deduct from taxable income
            if($item['deduction'] == true && $item['slug'] != 'nssf') {
                $taxable_income -= $item['amount'];
                $new_calculated_payroll[$item['slug']] =  $item['amount'];
                $new_calculated_payroll['net_salary'] = $taxable_income;
            } elseif($item['deduction'] == false && $item['slug'] != 'nssf') {
                $new_calculated_payroll[$item['slug']] =  $item['amount'];
            }
        }


        return [
            'gross_salary' => $gross_salary,
            'total_deductions' => $total_deductions,
            'total_additions' => $total_additions,
            'total_net_salary' => $total_net_salary,
            'taxable_income' => $taxable_income,
            'items' => $items,
            'all_items' => $all_items,
            'new_calculated_payroll' => $new_calculated_payroll
        ];
    }

    public static function paye_calculator($salary)
    {
        // Paye Bands
        $taxBands = array(
            array("min" => 0, "max" => 24000, "rate" => 0.10),
            array("min" => 24001, "max" => 32333, "rate" => 0.25),
            array("min" => 32334, "max" => PHP_FLOAT_MAX, "rate" => 0.30)
        );

        // Apply the fixed relief amount
        $reliefAmount = 2400;
        // $salary -= $reliefAmount;

        // Initialize total tax
        $totalTax = 0;

        // Calculate tax for each band
        foreach ($taxBands as $band) {
            $min = $band["min"];
            $max = $band["max"];
            $rate = $band["rate"];

            // Check if taxable income falls within the current band
            if ($salary > $min) {
                $taxableAmount = min($max, $salary) - $min;
                $totalTax += $taxableAmount * $rate;
            }
        }

        // if relief is more than the tax, then net paye is 0
        if($reliefAmount >= $totalTax) {
            $net_paye = 0;
        } else {
            $net_paye = $totalTax - $reliefAmount;
        }

        return [
            'paye' => $totalTax,
            'net_paye' => $net_paye,
            'relief' => 2400,
        ];
    }


    public static function nhif_calculator($salary, $month, $year)
    {
        if ($year > 2024 || ($year == 2024 && $month >= 10)) {
            $employeeContribution = $salary * 0.0275;
            $employerContribution = $employeeContribution;

            return [
                'employeeContribution' => round($employeeContribution, 2),
                'employerContribution' => round($employerContribution, 2),
                'relief' => 0
            ];
        }

        $nhifRates = array(
            array(
                "minSalary" => 0,
                "maxSalary" => 5999,
                "employeeContribution" => 150,
                "employerContribution" => 150
            ),
            array(
                "minSalary" => 6000,
                "maxSalary" => 7999,
                "employeeContribution" => 300,
                "employerContribution" => 300
            ),
            array(
                "minSalary" => 8000,
                "maxSalary" => 11999,
                "employeeContribution" => 400,
                "employerContribution" => 400
            ),
            array(
                "minSalary" => 12000,
                "maxSalary" => 14999,
                "employeeContribution" => 500,
                "employerContribution" => 500
            ),
            array(
                "minSalary" => 15000,
                "maxSalary" => 19999,
                "employeeContribution" => 600,
                "employerContribution" => 600
            ),
            array(
                "minSalary" => 20000,
                "maxSalary" => 24999,
                "employeeContribution" => 750,
                "employerContribution" => 750
            ),
            array(
                "minSalary" => 25000,
                "maxSalary" => 29999,
                "employeeContribution" => 850,
                "employerContribution" => 850
            ),
            array(
                "minSalary" => 30000,
                "maxSalary" => 34999,
                "employeeContribution" => 900,
                "employerContribution" => 900
            ),
            array(
                "minSalary" => 35000,
                "maxSalary" => 39000,
                "employeeContribution" => 950,
                "employerContribution" => 950
            ),
            array(
                "minSalary" => 40000,
                "maxSalary" => 44999,
                "employeeContribution" => 1000,
                "employerContribution" => 1000
            ),
            array(
                "minSalary" => 45000,
                "maxSalary" => 49000,
                "employeeContribution" => 1100,
                "employerContribution" => 1100
            ),
            array(
                "minSalary" => 50000,
                "maxSalary" => 59999,
                "employeeContribution" => 1200,
                "employerContribution" => 1200
            ),
            array(
                "minSalary" => 60000,
                "maxSalary" => 69999,
                "employeeContribution" => 1300,
                "employerContribution" => 1300
            ),
            array(
                "minSalary" => 70000,
                "maxSalary" => 79999,
                "employeeContribution" => 1400,
                "employerContribution" => 1400
            ),
            array(
                "minSalary" => 80000,
                "maxSalary" => 89999,
                "employeeContribution" => 1500,
                "employerContribution" => 1500
            ),
            array(
                "minSalary" => 90000,
                "maxSalary" => 99999,
                "employeeContribution" => 1600,
                "employerContribution" => 1600
            ),
            array(
                "minSalary" => 100000,
                "maxSalary" => PHP_FLOAT_MAX, // Representing infinity in PHP
                "employeeContribution" => 1700,
                "employerContribution" => 1700
            )
        );

        $nhif = [];

        foreach($nhifRates as $rate) {
            if($salary >= $rate['minSalary'] && $salary <= $rate['maxSalary']) {
                $nhif['employeeContribution'] = $rate['employeeContribution'];
                $nhif['employerContribution'] = $rate['employerContribution'];
            }
        }

        // nhif relief is15% of the employee contribution
        $nhif['relief'] = ($nhif['employeeContribution'] * 15) / 100;

        return $nhif;
    }

    public static function nssf_calculator($salary, $month, $year)
    {
        // Define NSSF rates and ceiling limits
        $nssfRates = [
            "oldEmployeeContributionRate" => 5, // Old rate before Feb 2024
            "newEmployeeContributionRate" => 6, // New rate from Feb 2024 onwards
            "ceilingSalary" => 18000,
            "ceilingEmployeeContribution" => 1080,
            "ceilingEmployerContribution" => 1080
        ];

        // Initialize the NSSF array
        $nssf = [];

        // Check if the date is before February 2024
        if ($year < 2024 || ($year == 2024 && $month < 2)) {
            // Use the previous calculation logic for dates before February 2024
            if ($salary > $nssfRates['ceilingSalary']) {
                $nssf['employeeContribution'] = $nssfRates['ceilingEmployeeContribution'];
                $nssf['employerContribution'] = $nssfRates['ceilingEmployerContribution'];
            } else {
                $nssf['employeeContribution'] = $nssfRates['ceilingEmployeeContribution'];
                $nssf['employerContribution'] = $nssfRates['ceilingEmployerContribution'];
            }
//            // Apply ceiling limits
        } else {
            // For February 2024 and onwards, use the fixed rate of 6%
            $nssf['employeeContribution'] = ($salary * $nssfRates['newEmployeeContributionRate']) / 100;
            $nssf['employerContribution'] = ($salary * $nssfRates['newEmployeeContributionRate']) / 100;

            // Apply ceiling limits
//            if ($nssf['employeeContribution'] > $nssfRates['ceilingEmployeeContribution']) {
//                $nssf['employeeContribution'] = $nssfRates['ceilingEmployeeContribution'];
//            }
//            if ($nssf['employerContribution'] > $nssfRates['ceilingEmployerContribution']) {
//                $nssf['employerContribution'] = $nssfRates['ceilingEmployerContribution'];
//            }
        }

        return $nssf;
    }


//    public static function nssf_calculator($salary)
//    {
//        $nssfRates = array(
//            "employeeContributionRate" => 6,
//            "employerContributionRate" => 6,
//            "ceilingLimit" => 2160,
//            "ceilingSalary" => 18000,
//            "ceilingEmployeeContribution" => 1080,
//            "ceilingEmployerContribution" => 1080
//        );
//
//        $nssf = [];
//
//        if($salary > $nssfRates['ceilingSalary']) {
//            $nssf['employeeContribution'] = $nssfRates['ceilingEmployeeContribution'];
//            $nssf['employerContribution'] = $nssfRates['ceilingEmployerContribution'];
//        } else {
//            $nssf['employeeContribution'] = ($salary * $nssfRates['employeeContributionRate']) / 100;
//            $nssf['employerContribution'] = ($salary * $nssfRates['employerContributionRate']) / 100;
//        }
//
//        // if the employee contribution is more than the ceiling limit, then use the ceiling limit
//        if($nssf['employeeContribution'] > $nssfRates['ceilingEmployeeContribution']) {
//            $nssf['employeeContribution'] = $nssfRates['ceilingEmployeeContribution'];
//        }
//
//        // if the employer contribution is more than the ceiling limit, then use the ceiling limit
//        if($nssf['employerContribution'] > $nssfRates['ceilingEmployerContribution']) {
//            $nssf['employerContribution'] = $nssfRates['ceilingEmployerContribution'];
//        }
//
//        return $nssf;
//    }


    public static function nita_calculator() {
        return [
            'nita' => 50
        ];
    }

    public static function housing_levy_calculator($salary) {
        $housingLevy = $salary * 0.015;
        $housingLevyRelief = $housingLevy * 0.15;
        $net_housing_levy = $housingLevy - $housingLevyRelief;
        return [
            'housing_levy' => $housingLevy,
            'housing_levy_relief' => $housingLevyRelief,
            'net_housing_levy' => $net_housing_levy
        ];
    }

    public static function generate_payslip($gross_salary, $month, $year) {

        // deduct nssf from gross salary
        $nssf = self::nssf_calculator($gross_salary, $month, $year);

        // taxable income is gross salary - nssf
        $taxable_income = $gross_salary - $nssf['employeeContribution'];

        // calculate paye
        $paye = self::paye_calculator($taxable_income);
        $nhif = self::nhif_calculator($gross_salary, $month, $year);
        $nita = self::nita_calculator();
        $housing_levy = self::housing_levy_calculator($gross_salary);

        $paye_net = $paye['paye'] - ($paye['relief'] + $nhif['relief'] + $housing_levy['housing_levy_relief']);

        $total_deductions = $nssf['employeeContribution'] + $paye_net + $nhif['employeeContribution'] + $housing_levy['net_housing_levy'];

        // calculate net salary
        $net_salary = $gross_salary - $total_deductions;

        return [
            'gross_salary' => $gross_salary,
            'nssf' => $nssf,
            'taxable_income' => $taxable_income,
//            'paye' => $paye,
            'paye' => [
                'paye' => $paye['paye'],
                'net_paye' => $paye_net,
                'relief' => $paye['relief']
            ],
            'nhif' => $nhif,
            'nita' => $nita,
            'housing_levy' => $housing_levy,
            'total_deductions' => $total_deductions,
            'net_salary' => $net_salary
        ];
    }
}
