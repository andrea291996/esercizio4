<?php
/**
 * Entita' Customer (cliente).
 *
 * Per semplicita' ogni cliente possiede un solo conto.
 */

declare(strict_types=1);

namespace App\Domain;

final class Customer
{
    private int $id;
    private string $name;
    private Account $account;

    public function __construct(int $id, string $name, Account $account)
    {
        $this->id = $id;
        $this->name = $name;
        $this->account = $account;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function account(): Account
    {
        return $this->account;
    }
}
