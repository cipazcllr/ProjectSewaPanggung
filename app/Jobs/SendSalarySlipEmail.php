<?php

namespace App\Jobs;

use App\Models\SalarySlip;
use App\Services\SalarySlipPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSalarySlipEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $salarySlipId)
    {
    }

    public function handle(SalarySlipPdf $pdf): void
    {
        $salarySlip = SalarySlip::find($this->salarySlipId);

        if (! $salarySlip || ! $salarySlip->employee_email || $salarySlip->email_sent_at) {
            return;
        }

        $fileName = 'salary-slip-'.$salarySlip->period.'-'.$salarySlip->id.'.pdf';
        $message = implode("\n", [
            'Halo '.$salarySlip->employee_name.',',
            '',
            'Terlampir slip gaji untuk periode '.$salarySlip->period.'.',
            '',
            'Terima kasih.',
        ]);

        Mail::raw($message, function ($mail) use ($salarySlip, $pdf, $fileName) {
            $mail->to($salarySlip->employee_email, $salarySlip->employee_name)
                ->subject('Slip Gaji Periode '.$salarySlip->period)
                ->attachData($pdf->make($salarySlip), $fileName, [
                    'mime' => 'application/pdf',
                ]);
        });

        $salarySlip->forceFill(['email_sent_at' => now()])->save();
    }
}
