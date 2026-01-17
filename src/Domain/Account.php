<?php
/**
 * Entita' Account (conto).
 *
 * Responsabilita':
 * - conservare lo stato (saldo)
 * - applicare regole base di dominio per deposito/ritiro
 */

declare(strict_types=1);

namespace App\Domain;

final class Account
{
    private string $accountId;
    private Money $balance;

    public function __construct(string $accountId, Money $initialBalance)
    {
        $this->accountId = $accountId;
        $this->balance = $initialBalance;
    }

    public function id(): string
    {
        return $this->accountId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    /**
     * Deposita un importo sul conto.
     *
     * Regola base: non accettiamo importi negativi.
     */
    public function deposit(Money $amount): void
    {
        if ($amount->isNegative()) {
            throw new \InvalidArgumentException('Non puoi depositare un importo negativo.');
        }

        $this->balance = $this->balance->add($amount);
    }

    /**
     * Ritiro dal conto.
     *
     * Regola base: non puoi andare sotto zero.
     */
    public function withdraw(Money $amount): void
    {
        if ($amount->isNegative()) {
            throw new \InvalidArgumentException('Non puoi prelevare un importo negativo.');
        }

        if ($amount->greaterThan($this->balance)) {
            throw new \RuntimeException('Fondi insufficienti.');
        }

        $this->balance = $this->balance->subtract($amount);
    }
}
