<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalarySlip;
use App\Services\SalarySlipPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalarySlipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'period' => ['nullable', 'date_format:Y-m'],
        ]);

        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $query = SalarySlip::query();

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('employee_name', 'like', '%'.$search.'%')
                    ->orWhere('employee_email', 'like', '%'.$search.'%')
                    ->orWhere('position', 'like', '%'.$search.'%');
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['period'])) {
            $query->where('period', $validated['period']);
        }

        $total = (clone $query)->count();
        $data = $query
            ->latest('period')
            ->latest('id')
            ->forPage($page, $limit)
            ->get();

        return response()->json(compact('data', 'total', 'page'));
    }

    public function show(SalarySlip $salarySlip): JsonResponse
    {
        return response()->json($salarySlip);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedSalarySlip($request);
        $salarySlip = SalarySlip::create($this->withNetSalary($data));

        return response()->json($salarySlip, 201);
    }

    public function update(Request $request, SalarySlip $salarySlip): JsonResponse
    {
        $data = $this->validatedSalarySlip($request, partial: true);
        $salarySlip->update($this->withNetSalary($data, $salarySlip));

        return response()->json($salarySlip->refresh());
    }

    public function destroy(SalarySlip $salarySlip): JsonResponse
    {
        $salarySlip->delete();

        return response()->json(['success' => true]);
    }

    public function exportPdf(Request $request, SalarySlipPdf $pdf): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:salary_slips,id'],
        ]);

        $salarySlip = SalarySlip::findOrFail($validated['id']);

        return response($pdf->make($salarySlip), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="salary-slip-'.$salarySlip->id.'.pdf"',
        ]);
    }

    private function validatedSalarySlip(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'employee_name' => [$required, 'string', 'max:255'],
            'employee_email' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'period' => [$required, 'date_format:Y-m'],
            'basic_salary' => [$required, 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function withNetSalary(array $data, ?SalarySlip $salarySlip = null): array
    {
        $basicSalary = (float) ($data['basic_salary'] ?? $salarySlip?->basic_salary ?? 0);
        $allowance = (float) ($data['allowance'] ?? $salarySlip?->allowance ?? 0);
        $deduction = (float) ($data['deduction'] ?? $salarySlip?->deduction ?? 0);

        $data['allowance'] = $allowance;
        $data['deduction'] = $deduction;
        $data['net_salary'] = $basicSalary + $allowance - $deduction;

        return $data;
    }
}
