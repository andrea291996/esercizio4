<?php
/**
 * Interfaccia del repository dei clienti.
 *
 * Serve per disaccoppiare il dominio (BankTeller) dai dettagli di persistenza.
 * Qui useremo un'implementazione basata su CSV.
 */

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Customer;

interface CustomerRepository
{
    /** @return Customer[] */
    public function findAll(): array;

    public function findById(int $customerId): ?Customer;

    /**
     * Salva l'entita' aggiornando il saldo nel CSV.
     */
    public function save(Customer $customer): void;
}
