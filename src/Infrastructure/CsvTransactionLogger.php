<?php
/**
 * Logger su CSV delle transazioni.
 *
 * Formato CSV (data/transactions.csv):
 * id,customer_id,type,amount_cents,at_iso
 */

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Transaction;

final class CsvTransactionLogger implements TransactionLogger
{
    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        if (!is_file($this->csvFile)) {
            $dir = dirname($this->csvFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($this->csvFile, "id,customer_id,type,amount_cents,at_iso\n");
        }
    }

    public function log(Transaction $transaction): void
    {
        $handle = fopen($this->csvFile, 'a');
        if ($handle === false) {
            throw new \RuntimeException('Impossibile scrivere il CSV delle transazioni.');
        }

        fputcsv($handle, [
            $transaction->id(),
            (string)$transaction->customerId(),
            $transaction->type(),
            (string)$transaction->amount()->cents(),
            $transaction->at()->format(\DateTimeInterface::ATOM),
        ]);

        fclose($handle);
    }
}
