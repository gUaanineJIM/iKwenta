<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Holds all balance, payment, and status arithmetic for customer credit
 * records.
 *
 * A credit record (a `debts` row) is a transaction header with items. Each
 * payment is attributed to one credit record and reduces that record's
 * remaining balance. A record is `paid` only when its recorded payments
 * reach its original total, or when the owner marks it as paid (which first
 * records the exact remaining amount as a payment and flags the record as
 * manually marked). Payments never apply to individual products.
 */
class CustomerAccountService
{
    /**
     * Total value of items given on credit across all of the customer's
     * credit records.
     */
    public function totalCredit(Customer $customer): string
    {
        $total = DebtItem::query()
            ->join('debts', 'debts.debt_id', '=', 'debt_items.debt_id')
            ->where('debts.customer_id', $customer->customer_id)
            ->sum('debt_items.subtotal');

        $total += (float) $customer->debts()->sum('money_amount');

        return $this->normalize($total);
    }

    /**
     * Total amount paid against the customer's account.
     */
    public function totalPaid(Customer $customer): string
    {
        $total = Payment::query()
            ->where('customer_id', $customer->customer_id)
            ->sum('amount_paid');

        return $this->normalize($total);
    }

    /**
     * Total value of items across the customer's active (unpaid or partially
     * paid) credit records only. Archived paid records are excluded.
     */
    public function activeTotalCredit(Customer $customer): string
    {
        $total = DebtItem::query()
            ->join('debts', 'debts.debt_id', '=', 'debt_items.debt_id')
            ->where('debts.customer_id', $customer->customer_id)
            ->where(fn ($query) => $query->where('debts.status', '!=', DebtStatus::Paid->value)->orWhereNull('debts.status'))
            ->sum('debt_items.subtotal');

        $total += (float) $customer->debts()
            ->where(fn ($query) => $query->where('status', '!=', DebtStatus::Paid->value)->orWhereNull('status'))
            ->sum('money_amount');

        return $this->normalize($total);
    }

    /**
     * Total paid against the customer's active credit records only.
     */
    public function activeTotalPaid(Customer $customer): string
    {
        $total = Payment::query()
            ->join('debts', 'debts.debt_id', '=', 'payments.debt_id')
            ->where('payments.customer_id', $customer->customer_id)
            ->where(fn ($query) => $query->where('debts.status', '!=', DebtStatus::Paid->value)->orWhereNull('debts.status'))
            ->sum('payments.amount_paid');

        return $this->normalize($total);
    }

    /**
     * Total value of items in the customer's archived (fully paid) records
     * that are still inside the retention window.
     */
    public function archivedTotalCredit(Customer $customer): string
    {
        $cutoff = now()->subDays(Debt::ARCHIVE_RETENTION_DAYS);

        $total = DebtItem::query()
            ->join('debts', 'debts.debt_id', '=', 'debt_items.debt_id')
            ->where('debts.customer_id', $customer->customer_id)
            ->where('debts.status', DebtStatus::Paid->value)
            ->where(fn ($query) => $query->whereNull('debts.paid_at')->orWhere('debts.paid_at', '>=', $cutoff))
            ->sum('debt_items.subtotal');

        $total += (float) $customer->debts()
            ->where('status', DebtStatus::Paid->value)
            ->where(fn ($query) => $query->whereNull('paid_at')->orWhere('paid_at', '>=', $cutoff))
            ->sum('money_amount');

        return $this->normalize($total);
    }

    /**
     * Total settled against the customer's archived (fully paid) records.
     */
    public function archivedTotalPaid(Customer $customer): string
    {
        $cutoff = now()->subDays(Debt::ARCHIVE_RETENTION_DAYS);

        $total = Payment::query()
            ->join('debts', 'debts.debt_id', '=', 'payments.debt_id')
            ->where('payments.customer_id', $customer->customer_id)
            ->where('debts.status', DebtStatus::Paid->value)
            ->where(fn ($query) => $query->whereNull('debts.paid_at')->orWhere('debts.paid_at', '>=', $cutoff))
            ->sum('payments.amount_paid');

        return $this->normalize($total);
    }

    /**
     * Outstanding account balance: the sum of every credit record's remaining
     * balance (fully paid records contribute zero even when settled manually).
     */
    public function outstandingBalance(Customer $customer): string
    {
        $total = 0.0;

        foreach ($customer->debts()->get() as $debt) {
            $total += (float) $this->remainingBalance($debt);
        }

        return $this->normalize($total);
    }

    /**
     * Total value of the items in a single credit record.
     */
    public function transactionTotal(Debt $debt): string
    {
        return $this->normalize($debt->items->sum('subtotal') + (float) $debt->money_amount);
    }

    /**
     * Total amount of payments attributed to a single credit record.
     */
    public function paymentsTotal(Debt $debt): string
    {
        return $this->normalize($debt->payments()->sum('amount_paid'));
    }

    /**
     * Remaining balance of a credit record.
     *
     * A fully paid record (including one settled manually) always has a
     * zero remaining balance; otherwise it is original total minus recorded
     * payments, floored at zero.
     */
    public function remainingBalance(Debt $debt): string
    {
        if ($this->statusOf($debt)->isFullyPaid()) {
            return '0.00';
        }

        $remaining = $this->sub(
            $this->transactionTotal($debt),
            $this->paymentsTotal($debt)
        );

        return $this->compare($remaining, '0.00') < 0
            ? '0.00'
            : $remaining;
    }

    /**
     * Record a payment against one credit record.
     *
     * The payment is capped at the record's current remaining balance and the
     * record's status is advanced (unpaid -> partially_paid -> paid) based on
     * the recorded total.
     */
    public function recordPayment(
        Debt $debt,
        string $amount,
        string $receivedById,
        ?Carbon $date = null,
    ): Payment {
        $amount = $this->normalize($amount);

        if ($this->compare($amount, '0.00') <= 0) {
            throw new InvalidArgumentException('A payment amount must be greater than zero.');
        }

        if ($this->statusOf($debt)->isFullyPaid()) {
            throw new InvalidArgumentException('This credit record is already paid.');
        }

        $remaining = $this->remainingBalance($debt);

        if ($this->compare($amount, $remaining) > 0) {
            throw new InvalidArgumentException(
                'The payment exceeds the remaining balance of this credit record.'
            );
        }

        $payment = new Payment([
            'customer_id' => $debt->customer_id,
            'debt_id' => $debt->debt_id,
            'amount_paid' => $amount,
            'payment_date' => $date?->toDateTimeString() ?? now()->toDateTimeString(),
            'received_by' => $receivedById,
        ]);

        $payment->save();

        $this->refreshStatus($debt);

        ActivityLog::create([
            'user_id' => $receivedById,
            'action' => 'payment.create',
            'table_name' => 'payments',
            'record_id' => $payment->payment_id,
            'old_values' => null,
            'new_values' => [
                'customer_name' => $debt->customer?->full_name,
                'debt_id' => $debt->debt_id,
                'amount_paid' => $amount,
                'payment_date' => $payment->payment_date?->toDateTimeString(),
            ],
            'created_at' => now(),
        ]);

        return $payment;
    }

    /**
     * Manually mark a credit record as paid (owner action).
     *
     * Performed only by the owner. The exact remaining balance IS recorded as
     * a payment so the payment history reflects the full settlement, and the
     * record is flagged as manually marked with the owner and date/time
     * stored for accountability.
     */
    public function markAsPaid(Debt $debt, string $userId, ?Carbon $date = null): Debt
    {
        if ($this->statusOf($debt)->isFullyPaid()) {
            throw new InvalidArgumentException('This credit record is already paid.');
        }

        $before = $this->statusOf($debt)->value;
        $remaining = $this->remainingBalance($debt);
        $paymentId = null;

        if ($this->compare($remaining, '0.00') > 0) {
            $payment = new Payment([
                'customer_id' => $debt->customer_id,
                'debt_id' => $debt->debt_id,
                'amount_paid' => $remaining,
                'payment_date' => $date?->toDateTimeString() ?? now()->toDateTimeString(),
                'received_by' => $userId,
            ]);

            $payment->save();

            $paymentId = $payment->payment_id;
        }

        $debt->status = DebtStatus::Paid;
        $debt->paid_manually = true;
        $debt->paid_manually_by = $userId;
        $debt->paid_manually_at = now();
        $debt->paid_at ??= now();
        $debt->save();

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'debt.mark_paid',
            'table_name' => 'debts',
            'record_id' => $debt->debt_id,
            'old_values' => ['status' => $before],
            'new_values' => [
                'status' => DebtStatus::Paid->value,
                'paid_manually' => true,
                'paid_manually_by' => $userId,
                'paid_manually_at' => $debt->paid_manually_at->toDateTimeString(),
                'paid_at' => $debt->paid_at->toDateTimeString(),
                'payment_id' => $paymentId,
            ],
            'created_at' => now(),
        ]);

        return $debt;
    }

    /**
     * Recompute a credit record's status from its attributed payments.
     */
    public function refreshStatus(Debt $debt): Debt
    {
        $total = $this->transactionTotal($debt);
        $paid = $this->paymentsTotal($debt);

        $status = DebtStatus::Unpaid;

        if ($this->compare($paid, '0.00') > 0 && $this->compare($paid, $total) < 0) {
            $status = DebtStatus::PartiallyPaid;
        } elseif ($this->compare($paid, '0.00') > 0 && $this->compare($paid, $total) >= 0) {
            $status = DebtStatus::Paid;
        }

        if ($this->statusOf($debt) !== $status) {
            $debt->status = $status;

            if ($status->isFullyPaid() && ! $debt->paid_at) {
                $debt->paid_at = now();
            }

            $debt->save();
        }

        return $debt;
    }

    private function statusOf(Debt $debt): DebtStatus
    {
        if ($debt->status instanceof DebtStatus) {
            return $debt->status;
        }

        return DebtStatus::from($debt->status ?: 'unpaid');
    }

    /**
     * Normalize a value to a fixed two-decimal string.
     */
    private function normalize(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function sub(string $a, string $b): string
    {
        if (function_exists('bcsub')) {
            return bcsub($a, $b, 2);
        }

        return number_format((float) $a - (float) $b, 2, '.', '');
    }

    private function compare(string $a, string $b): int
    {
        if (function_exists('bccomp')) {
            return bccomp($a, $b, 2);
        }

        return (float) $a <=> (float) $b;
    }
}
