<?php

namespace App\Console\Commands;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('debts:prune-archived')]
#[Description('Delete paid debts whose 15-day archive retention has expired, with their payments and items.')]
class PruneArchivedDebts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(Debt::ARCHIVE_RETENTION_DAYS);

        $expiredIds = Debt::query()
            ->where('status', DebtStatus::Paid->value)
            ->whereNotNull('paid_at')
            ->where('paid_at', '<', $cutoff)
            ->pluck('debt_id');

        $count = 0;

        foreach ($expiredIds->chunk(200) as $chunk) {
            DB::transaction(function () use ($chunk) {
                Payment::whereIn('debt_id', $chunk)->delete();
                Debt::whereIn('debt_id', $chunk)->delete();
            });

            $count += $chunk->count();
        }

        $this->info("Pruned {$count} archived debt record(s).");

        return self::SUCCESS;
    }
}
