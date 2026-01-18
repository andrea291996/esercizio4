<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Account;
use App\Domain\Customer;
use App\Domain\Money;
use App\Domain\Transaction;

final class TransactionRepository
{
    private string $csvFile;
    private bool $logTransactions;

    public function __construct(string $csvFile, bool $logTransactions)
    {
        $this->csvFile = $csvFile;
        $this->logTransactions = $logTransactions;
        // Se il file non esiste, lo inizializziamo con un header.
        if (!is_file($this->csvFile)) {
            $dir = dirname($this->csvFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($this->csvFile, "id,customer_id,type,amount_cents,at_iso\n");
        }
    }

    public function getTransactionsByCustomerId($customerId){
        $rows = $this->readRows();
        $transactions = [];
        foreach ($rows as $row) {
            if ((int)$row['customer_id'] === $customerId) {
                $transactions[] = $this->rowToTransactions($row, $customerId);
            }
        }
        return $transactions;
    }

    public function getLastNTransactions($n, $id){
        if (!$this->logTransactions) {
            throw new \LogicException("Logging disabilitato\n");
        }
        $transactions = $this->getTransactionsByCustomerId($id);
        $transactions = array_slice($transactions, -$n);
        $transactions = array_reverse($transactions);
        return $transactions;
    }

    private function readRows(): array
    {
        $handle = fopen($this->csvFile, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $assoc = [];
            foreach ($header as $i => $colName) {
                $assoc[$colName] = $data[$i] ?? '';
            }

            // Scartiamo righe vuote.
            if (trim($assoc['id'] ?? '') === '') {
                continue;
            }

            $rows[] = $assoc;
        }
        fclose($handle);
        return $rows;
    }

    private function rowToTransactions(array $row, $customerId): Transaction
    {
        $type = (string)$row['type'];
        $amount_cents = (int)$row['amount_cents'];
        $amount = Money::fromCents($amount_cents);
        $transactionId = (string)$row['id'];
        $data = new \DateTimeImmutable($row['at_iso']);
        $transazione = new Transaction($transactionId, $customerId, $type, $amount, $data);
        return $transazione;
    }
}
