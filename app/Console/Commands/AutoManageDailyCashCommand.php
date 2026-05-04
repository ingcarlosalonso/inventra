<?php

namespace App\Console\Commands;

use App\Models\DailyCash;
use App\Models\DailyCash\Scopes\ByPointOfSale as DailyCashByPointOfSale;
use App\Models\DailyCash\Scopes\Open;
use App\Models\DailyCash\Scopes\OpenedToday;
use App\Models\PointOfSale;
use App\Models\PointOfSale\Scopes\WithAutoCloseTime;
use App\Models\PointOfSale\Scopes\WithAutoOpenTime;
use App\Models\Scopes\Active;
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
        PointOfSale::query()
            ->withScopes([new Active, new WithAutoOpenTime])
            ->get()
            ->each(function (PointOfSale $pos) use ($now) {
                if (substr($pos->auto_open_time, 0, 5) !== $now) {
                    return;
                }

                $alreadyOpen = DailyCash::query()
                    ->withScopes([new DailyCashByPointOfSale($pos->id), new Open, new OpenedToday])
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
        PointOfSale::query()
            ->withScopes([new Active, new WithAutoCloseTime])
            ->get()
            ->each(function (PointOfSale $pos) use ($now) {
                if (substr($pos->auto_close_time, 0, 5) !== $now) {
                    return;
                }

                $dailyCash = DailyCash::query()
                    ->withScopes([new DailyCashByPointOfSale($pos->id), new Open, new OpenedToday])
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
