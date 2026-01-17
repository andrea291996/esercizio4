<?php
/**
 * BankTeller (sportellista) = servizio applicativo.
 *
 * Qui teniamo insieme:
 * - recupero del cliente (repository)
 * - esecuzione dell'operazione sul conto
 * - salvataggio del nuovo saldo
 * - eventuale logging della transazione
 *
 * Questo e' un esempio semplice di "Application Service":
 * la logica di dominio resta in Account, qui c'e' coordinamento.
 */

declare(strict_types=1);

namespace App\Domain;

use App\Infrastructure\CustomerRepository;
use App\Infrastructure\TransactionLogger;

final class BankTeller
{
    private CustomerRepository $customers;
    private TransactionLogger $logger;
    private string $currency;

    public function __construct(CustomerRepository $customers, TransactionLogger $logger, string $currency)
    {
        $this->customers = $customers;
        $this->logger = $logger;
        $this->currency = $currency;
    }

    public function listCustomers(): array
    {
        return $this->customers->findAll();
    }

    public function getCustomer(int $customerId): ?Customer
    {
        return $this->customers->findById($customerId);
    }

    /**
     * Deposito.
     */
    public function deposit(int $customerId, Money $amount): Money
    {
        $customer = $this->requireCustomer($customerId);
        $customer->account()->deposit($amount);

        // Persistiamo il nuovo saldo sul CSV.
        $this->customers->save($customer);

        // Registriamo la transazione (se la config lo prevede).
        $this->logger->log(new Transaction(
            $this->newTransactionId(),
            $customerId,
            Transaction::TYPE_DEPOSIT,
            $amount,
            new \DateTimeImmutable('now')
        ));

        return $customer->account()->balance();
    }

    /**
     * Ritiro.
     */
    public function withdraw(int $customerId, Money $amount): Money
    {
        $customer = $this->requireCustomer($customerId);
        $customer->account()->withdraw($amount);

        $this->customers->save($customer);

        $this->logger->log(new Transaction(
            $this->newTransactionId(),
            $customerId,
            Transaction::TYPE_WITHDRAW,
            $amount,
            new \DateTimeImmutable('now')
        ));

        return $customer->account()->balance();
    }

    public function formatMoney(Money $money): string
    {
        return $money->format($this->currency);
    }

    private function requireCustomer(int $customerId): Customer
    {
        $customer = $this->customers->findById($customerId);
        if ($customer === null) {
            throw new \RuntimeException('Cliente non trovato. ID: ' . $customerId);
        }
        return $customer;
    }

    /**
     * Generatore "povero" di ID.
     *
     * In un sistema reale useremmo UUID (ramsey/uuid) o un ID dal database.
     */
    private function newTransactionId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
