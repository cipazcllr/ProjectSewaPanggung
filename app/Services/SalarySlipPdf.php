<?php

namespace App\Services;

use App\Models\SalarySlip;

class SalarySlipPdf
{
    public function make(SalarySlip $salarySlip): string
    {
        return $this->makeSimplePdf([
            'Salary Slip #'.$salarySlip->id,
            'Employee: '.$salarySlip->employee_name,
            'Email: '.($salarySlip->employee_email ?? '-'),
            'Position: '.($salarySlip->position ?? '-'),
            'Period: '.$salarySlip->period,
            'Status: '.$salarySlip->status,
            'Paid At: '.($salarySlip->paid_at?->format('Y-m-d') ?? '-'),
            '',
            'Basic Salary: '.$salarySlip->basic_salary,
            'Allowance: '.$salarySlip->allowance,
            'Deduction: '.$salarySlip->deduction,
            'Net Salary: '.$salarySlip->net_salary,
            '',
            'Notes: '.($salarySlip->notes ?? '-'),
        ]);
    }

    private function makeSimplePdf(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n50 780 Td\n";

        foreach ($lines as $index => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= ($index === 0 ? '' : "0 -18 Td\n").'('.$escaped.") Tj\n";
        }

        $content .= 'ET';
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
}
