<?php
/**
 * Implementazione del repository clienti usando un file CSV.
 *
 * Formato CSV (data/customers.csv):
 * id,name,balance_cents
 * 1,Mario Rossi,12500
 * 2,Giulia Bianchi,0
 *
 * Obiettivi didattici:
 * - lettura/scrittura di CSV con fgetcsv/fputcsv
 * - trasformazione CSV <-> oggetti (Customer, Account, Money)
 * - aggiornamento in-place riscrivendo l'intero file
 *   (computazionalmente irragioneveole ma semplice ed efficace
 *   per l'esercizio)
 */

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Account;
use App\Domain\Customer;
use App\Domain\Money;

final class CsvCustomerRepository implements CustomerRepository
{
    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;

        // Se il file non esiste, lo inizializziamo con un header.
        if (!is_file($this->csvFile)) {
            $dir = dirname($this->csvFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($this->csvFile, "id,name,balance_cents\n");
        }
    }

    /** @return Customer[] */
    public function findAll(): array
    {
        $rows = $this->readRows();
        $customers = [];

        foreach ($rows as $row) {
            $customers[] = $this->rowToCustomer($row);
        }

        return $customers;
    }

    public function findById(int $customerId): ?Customer
    {
        $rows = $this->readRows();
        foreach ($rows as $row) {
            if ((int)$row['id'] === $customerId) {
                return $this->rowToCustomer($row);
            }
        }
        return null;
    }

    public function save(Customer $customer): void
    {
        $rows = $this->readRows();
        $updated = false;

        foreach ($rows as &$row) {
            if ((int)$row['id'] === $customer->id()) {
                $row['name'] = $customer->name();
                $row['balance_cents'] = (string)$customer->account()->balance()->cents();
                $updated = true;
                break;
            }
        }
        unset($row);

        if (!$updated) {
            // Se non esiste, lo aggiungiamo.
            $rows[] = [
                'id' => (string)$customer->id(),
                'name' => $customer->name(),
                'balance_cents' => (string)$customer->account()->balance()->cents(),
            ];
        }

        $this->writeRows($rows);
    }

    /**
     * Legge tutte le righe dati (escluso l'header) e le restituisce come array associativo.
     *
     * @return array<int, array{id:string, name:string, balance_cents:string}>
     */
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

    /**
     * Riscrive l'intero CSV.
     */
    private function writeRows(array $rows): void
    {
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Impossibile scrivere il CSV dei clienti.');
        }

        // Manteniamo sempre lo stesso ordine colonne.
        fputcsv($handle, ['id', 'name', 'balance_cents']);
        foreach ($rows as $row) {
            fputcsv($handle, [$row['id'], $row['name'], $row['balance_cents']]);
        }

        fclose($handle);
    }

    private function rowToCustomer(array $row): Customer
    {
        $id = (int)$row['id'];
        $name = (string)$row['name'];
        $balanceCents = (int)$row['balance_cents'];

        $account = new Account('ACC-' . $id, Money::fromCents($balanceCents));
        return new Customer($id, $name, $account);
    }
}
