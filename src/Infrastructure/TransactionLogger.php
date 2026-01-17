<?php
/**
 * Interfaccia per il logging delle transazioni.
 *
 * Nel nostro progetto la scelta e' configurabile via .env:
 * - se LOG_TRANSACTIONS=false, useremo un logger "vuoto" che non fa nulla
 * - altrimenti useremo un logger su CSV
 */

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Transaction;

interface TransactionLogger
{
    public function log(Transaction $transaction): void;
}
