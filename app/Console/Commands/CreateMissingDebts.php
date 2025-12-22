<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Debt;

class CreateMissingDebts extends Command
{
    protected $signature = 'debts:create-missing {--update : Update existing debts with USD values}';
    protected $description = 'Create missing debt records for sales with remaining debt';

    public function handle()
    {
        // Option to update existing debts
        if ($this->option('update')) {
            $this->updateExistingDebts();
            return 0;
        }

        // Tìm tất cả sale đã completed và còn nợ
        $sales = Sale::where('sale_status', 'completed')
            ->whereDoesntHave('debt')
            ->get();

        $this->info("Found {$sales->count()} completed sales without debt records");

        $created = 0;
        foreach ($sales as $sale) {
            // Tính nợ còn lại
            $debtUsd = $sale->total_usd - ($sale->paid_usd ?? 0);
            $debtVnd = $sale->total_vnd - ($sale->paid_vnd ?? 0);
            
            // Nếu còn nợ thì tạo debt record
            if ($debtUsd > 0.01 || $debtVnd > 1000) {
                Debt::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'total_usd' => $sale->total_usd ?? 0,
                    'paid_usd' => $sale->paid_usd ?? 0,
                    'debt_usd' => max(0, $debtUsd),
                    'exchange_rate' => $sale->exchange_rate ?? 0,
                    'total_amount' => $sale->total_vnd ?? 0,
                    'paid_amount' => $sale->paid_vnd ?? 0,
                    'debt_amount' => max(0, $debtVnd),
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                ]);
                $this->info("Created debt for sale {$sale->invoice_code} - Debt USD: {$debtUsd}, VND: {$debtVnd}");
                $created++;
            }
        }

        $this->info("Created {$created} debt records. Done!");
        return 0;
    }

    private function updateExistingDebts()
    {
        $debts = Debt::with('sale')->get();
        $updated = 0;

        foreach ($debts as $debt) {
            $sale = $debt->sale;
            if (!$sale) continue;

            $debtUsd = $sale->total_usd - ($sale->paid_usd ?? 0);
            $debtVnd = $sale->total_vnd - ($sale->paid_vnd ?? 0);

            $debt->update([
                'total_usd' => $sale->total_usd ?? 0,
                'paid_usd' => $sale->paid_usd ?? 0,
                'debt_usd' => max(0, $debtUsd),
                'exchange_rate' => $sale->exchange_rate ?? 0,
                'total_amount' => $sale->total_vnd ?? 0,
                'paid_amount' => $sale->paid_vnd ?? 0,
                'debt_amount' => max(0, $debtVnd),
            ]);

            $this->info("Updated debt for sale {$sale->invoice_code}");
            $updated++;
        }

        $this->info("Updated {$updated} debt records. Done!");
    }
}
