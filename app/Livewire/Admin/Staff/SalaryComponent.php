<?php

namespace App\Livewire\Admin\Staff;

use App\Models\Salary;
use App\Models\Admin as User;
use App\Traits\HandlesBankPayments;
use Livewire\Component;
use App\Traits\ManagesExpenseTransactions;
use App\Traits\DailyBookEntryTrait;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // <-- Import the PDF Facade
use Illuminate\Support\Facades\DB; // <-- IMPORT THE DB FACADE
use Illuminate\Support\Facades\Log; // <-- Optional: For logging errors

class SalaryComponent extends Component
{
    use WithPagination;
    use HandlesBankPayments;
    use DailyBookEntryTrait;

    protected $paginationTheme = 'bootstrap';

    public User $user;
    public $perPage = 10;

    // Modal state properties
    public $showModal = false;
    public $modalMode = 'create'; // can be 'create', 'view', 'edit'
    public ?Salary $editingSalary = null;

    // Form properties
    public $pay_period_start;
    public $base_salary;
    public $notes;
    public $allowances = [];
    public $deductions = [];


    public $showPaymentModal = false;
    public ?Salary $payingSalary = null;
    public $payment_date;
    public $payment_notes;

    protected function rules()
    {
        return [
            'pay_period_start' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'allowances.*.name' => 'required_with:allowances.*.amount|string|max:50',
            'allowances.*.amount' => 'required_with:allowances.*.name|numeric|min:0',
            'deductions.*.name' => 'required_with:deductions.*.amount|string|max:50',
            'deductions.*.amount' => 'required_with:deductions.*.name|numeric|min:0',
        ];
    }

    public function mount(User $user)
    {
        $this->user = $user;
    }

    // --- Modal Control ---
    public function openCreateModal()
    {
        $this->resetForm();
        $this->modalMode = 'create';
        $this->pay_period_start = now()->startOfMonth()->format('Y-m-d');
        $this->base_salary = $this->user->base_salary_amount;
        $this->showModal = true;
    }

    public function openViewModal($salaryId)
    {
        $this->setEditingSalary($salaryId);
        $this->modalMode = 'view';
        $this->showModal = true;
    }

    public function openEditModal($salaryId)
    {
        $salary = Salary::findOrFail($salaryId);
        if ($salary->status === 'paid') {
            session()->flash('error', 'Cannot edit a payslip that has already been paid.');
            return;
        }
        $this->setEditingSalary($salaryId);
        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // --- Data Handling ---
    public function savePayslip()
    {
        $this->validate();
        $this->modalMode === 'create' ? $this->createPayslip() : $this->updatePayslip();
    }

    public function createPayslip()
    {
        $payPeriodStart = Carbon::parse($this->pay_period_start);
        $existing = Salary::where('staff_id', $this->user->id)->where('pay_period_start', $payPeriodStart)->first();

        if ($existing) {
            session()->flash('error', 'A payslip for this user and pay period already exists.');
            return;
        }

        $grossSalary = $this->base_salary + collect($this->allowances)->where('name', '!=', '')->pluck('amount')->sum();
        $netSalary = $grossSalary - collect($this->deductions)->where('name', '!=', '')->pluck('amount')->sum();

        Salary::create([
            'staff_id' => $this->user->id,
            'pay_period_start' => $payPeriodStart,
            'pay_period_end' => $payPeriodStart->copy()->endOfMonth(),
            'base_salary' => $this->base_salary,
            'allowances' => $this->formatForDatabase($this->allowances),
            'deductions' => $this->formatForDatabase($this->deductions),
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
            'notes' => $this->notes,
        ]);

        $this->closeModal();
        session()->flash('message', 'Payslip generated successfully.');
    }

    public function updatePayslip()
    {
        $grossSalary = $this->base_salary + collect($this->allowances)->where('name', '!=', '')->pluck('amount')->sum();
        $netSalary = $grossSalary - collect($this->deductions)->where('name', '!=', '')->pluck('amount')->sum();

        $this->editingSalary->update([
            'pay_period_start' => Carbon::parse($this->pay_period_start)->startOfMonth(),
            'pay_period_end' => Carbon::parse($this->pay_period_start)->endOfMonth(),
            'base_salary' => $this->base_salary,
            'allowances' => $this->formatForDatabase($this->allowances),
            'deductions' => $this->formatForDatabase($this->deductions),
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
            'notes' => $this->notes,
        ]);

        $this->closeModal();
        session()->flash('message', 'Payslip updated successfully.');
    }
    public function addAllowance()
    {
        $this->allowances[] = ['name' => '', 'amount' => ''];
    }

    /**
     * Removes an allowance item from the allowances array at a specific index.
     *
     * @param int $index
     */
    public function removeAllowance($index)
    {
        unset($this->allowances[$index]);
        $this->allowances = array_values($this->allowances); // Re-index the array
    }

    /**
     * Adds a new, empty deduction item to the deductions array for the form.
     */
    public function addDeduction()
    {
        $this->deductions[] = ['name' => '', 'amount' => ''];
    }

    /**
     * Removes a deduction item from the deductions array at a specific index.
     *
     * @param int $index
     */
    public function removeDeduction($index)
    {
        unset($this->deductions[$index]);
        $this->deductions = array_values($this->deductions); // Re-index the array
    }

    // --- PDF Generation ---
    public function downloadPayslip($salaryId)
    {
        // Find the salary record ensuring it belongs to the current user for security
        $salary = $this->user->salaries()->findOrFail($salaryId);

        // Generate PDF in memory
        $pdf = Pdf::loadView('pdf.staff.payslip', ['pageTitle' => 'Payslip ' . $salary->user->name . '-' . $salary->pay_period_start->format('M-Y'), 'salary' => $salary]);

        // Construct a dynamic filename
        $fileName = 'payslip-' . str_replace(' ', '-', $salary->user->name) . '-' . $salary->pay_period_start->format('M-Y') . '.pdf';

        // Return the download stream
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName
        );
    }
    // --- Payment Confirmation Flow ---
    public function confirmPayment($salaryId)
    {
        $this->payingSalary = $this->user->salaries()->findOrFail($salaryId);
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_notes = 'Salary payment for ' . $this->payingSalary->pay_period_start->format('F, Y');
        $this->showPaymentModal = true;
    }

    public function processPayment()
    {
        $this->validate([
            'payment_date' => 'required|date',
        ]);

        try {
            // Start a database transaction.
            DB::transaction(function () {
                $salary = $this->payingSalary;
                $amount_cash = $salary->net_salary;
                $amount_bank = 0; // Salary is always paid from cash as per the requirement.

                // 1. Update the Salary record
                $salary->update([
                    'status' => 'paid',
                    'payment_date' => $this->payment_date,
                ]);

                // 2. Handle the generic transaction log
                $this->handlePaymentTransaction(
                    'cash',
                    $amount_cash,
                    $amount_bank,
                    null, // bankId is null for cash payments
                    $salary->id,
                    'Salary',
                    'credit' // Credit from our perspective (money out)
                );

                // 3. Handle the daily book financial ledger
                $this->handleDailyBookEntries(
                    $amount_cash,
                    $amount_bank,
                    'credit',
                    'cash',
                    'Salary',
                    $salary->id
                );
            });

            // If the transaction is successful, provide feedback and reset state.
            session()->flash('message', 'Payment processed successfully and recorded in the daily book.');
            $this->closePaymentModal();
        } catch (\Exception $e) {
            dd($e);
            // If any error occurred inside the transaction, it's rolled back.
            Log::error('Salary payment processing failed: ' . $e->getMessage());
            session()->flash('error', 'Could not process payment. An error occurred. Please check the logs or contact support.');
            // Optionally, you can keep the modal open for the user to retry.
            $this->closePaymentModal();
        }
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->reset(['payingSalary', 'payment_date', 'payment_notes']);
    }


    // --- Helper Methods ---
    private function setEditingSalary($salaryId)
    {
        $this->editingSalary = Salary::findOrFail($salaryId);
        $this->pay_period_start = $this->editingSalary->pay_period_start->format('Y-m-d');
        $this->base_salary = $this->editingSalary->base_salary;
        $this->notes = $this->editingSalary->notes;
        $this->allowances = $this->formatForForm($this->editingSalary->allowances);
        $this->deductions = $this->formatForForm($this->editingSalary->deductions);
    }

    private function resetForm()
    {
        $this->reset(['editingSalary', 'pay_period_start', 'base_salary', 'notes', 'allowances', 'deductions', 'modalMode']);
        $this->resetErrorBag();
    }

    private function formatForForm($data)
    {
        if (is_null($data)) return [];
        return collect($data)->map(fn($amount, $name) => ['name' => $name, 'amount' => $amount])->values()->all();
    }

    private function formatForDatabase($data)
    {
        $collection = collect($data)->where('name', '!=', '');
        return $collection->count() ? $collection->pluck('amount', 'name') : null;
    }

    public function render()
    {
        $salaries = $this->user->salaries()->latest('pay_period_start')->paginate($this->perPage);
        return view('livewire.admin.staff.salary-component', [
            'salaries' => $salaries
        ]);
    }
}
