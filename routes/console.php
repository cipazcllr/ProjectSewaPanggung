<?php

use App\Jobs\SendSalarySlipEmail;
use App\Models\SalarySlip;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('salary-slips:send-emails {--date= : Tanggal paid_at format YYYY-MM-DD, default hari ini} {--force : Kirim ulang walaupun email_sent_at sudah terisi}', function () {
    $date = $this->option('date')
        ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->toDateString()
        : now()->toDateString();

    $query = SalarySlip::query()
        ->whereDate('paid_at', $date)
        ->whereNotNull('employee_email');

    if (! $this->option('force')) {
        $query->whereNull('email_sent_at');
    }

    $salarySlips = $query->get(['id', 'employee_name', 'employee_email']);

    foreach ($salarySlips as $salarySlip) {
        SendSalarySlipEmail::dispatch($salarySlip->id);
    }

    $this->info('Dispatched '.$salarySlips->count().' salary slip email job(s) for paid_at '.$date.'.');
})->purpose('Dispatch queued email jobs for salary slips by paid_at date');
