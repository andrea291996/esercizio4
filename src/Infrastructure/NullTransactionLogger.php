<?php
/**
 * Logger "vuoto": implementa l'interfaccia ma non fa nulla.
 *
 * Pattern utile per evitare if sparsi nel codice (Null Object).
 */

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Transaction;

final class NullTransactionLogger implements TransactionLogger
{
    public function log(Transaction $transaction): void
    {
        // Nessuna azione.
    }
}
