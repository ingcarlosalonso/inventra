<?php

namespace App\Console\Commands;

use App\Models\DailyCash;
use App\Models\PointOfSale;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoManageDailyCashCommand extends Command
{
    protected $signature = 'daily-cash:auto-manage';

    protected $description = 'Auto open or close daily cashes based on each point of sale schedule';

    public function handle(): void
    {
        $now = Carbon::now()->format('H:i');

        Tenant::all()->each(function (Tenant $tenant) use ($now) {
            $tenant->run(function () use ($now) {
                $this->processOpenings($now);
                $this->processClosings($now);
            });
        });
    }

    private function processOpenings(string $now): void
    {
        PointOfSale::where('is_active', true)
            ->whereNotNull('auto_open_time')
            ->get()
            ->each(function (PointOfSale $pos) use ($now) {
                if (substr($pos->auto_open_time, 0, 5) !== $now) {
                    return;
                }

                $alreadyOpen = DailyCash::where('point_of_sale_id', $pos->id)
                    ->where('is_closed', false)
                    ->whereDate('opened_at', today())
                    ->exists();

                if ($alreadyOpen) {
                    return;
                }

                DailyCash::create([
                    'point_of_sale_id' => $pos->id,
                    'user_id' => null,
                    'opening_balance' => 0,
                    'opened_at' => now(),
                    'is_closed' => false,
                ]);

                $this->line("Opened daily cash for POS: {$pos->name}");
            });
    }

    private function processClosings(string $now): void
    {
        PointOfSale::where('is_active', true)
            ->whereNotNull('auto_close_time')
            ->get()
            ->each(function (PointOfSale $pos) use ($now) {
                if (substr($pos->auto_close_time, 0, 5) !== $now) {
                    return;
                }

                $dailyCash = DailyCash::where('point_of_sale_id', $pos->id)
                    ->where('is_closed', false)
                    ->whereDate('opened_at', today())
                    ->first();

                if (! $dailyCash) {
                    return;
                }

                $closingBalance = $dailyCash->opening_balance + $dailyCash->cashMovements()
                    ->join('cash_movement_types', 'cash_movements.cash_movement_type_id', '=', 'cash_movement_types.id')
                    ->selectRaw('SUM(CASE WHEN cash_movement_types.is_income = 1 THEN cash_movements.amount ELSE -cash_movements.amount END) as total')
                    ->value('total') ?? 0;

                $dailyCash->update([
                    'closing_balance' => $closingBalance,
                    'closed_at' => now(),
                    'is_closed' => true,
                ]);

                $this->line("Closed daily cash for POS: {$pos->name}");
            });
    }
}
