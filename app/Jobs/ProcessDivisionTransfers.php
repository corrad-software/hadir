<?php

namespace App\Jobs;

use App\Models\DivisionTransfer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessDivisionTransfers implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        $transfers = DivisionTransfer::where('processed', false)
            ->where('effective_date', '<=', $today)
            ->get();

        if ($transfers->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($transfers) {
            foreach ($transfers as $transfer) {
                DB::table('users')
                    ->where('id', $transfer->user_id)
                    ->update(['division_id' => $transfer->to_division_id]);

                $transfer->update(['processed' => true]);
            }
        });

        logger()->info("Processed {$transfers->count()} division transfer(s).");
    }
}
