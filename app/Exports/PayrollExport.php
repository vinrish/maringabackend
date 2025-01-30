<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class PayrollExport implements FromCollection, WithCustomStartCell
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    /**
     * Start cell for the data
     */
    public function startCell(): string
    {
        return 'A1'; // Data starts at row 4, after the title and metadata
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Define headings for the export file.
     */
    public function headings(): array
    {
        return [
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
            'Total NSSF'
        ];
    }
}
