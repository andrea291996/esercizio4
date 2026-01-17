<?php
/**
 * Rappresenta una singola operazione effettuata allo sportello.
 *
 * Non la salviamo in un DB: la persistiamo in un CSV di log, se abilitato.
 */

declare(strict_types=1);

namespace App\Domain;

final class Transaction
{
    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAW = 'WITHDRAW';

    private string $id;
    private int $customerId;
    private string $type;
    private Money $amount;
    private \DateTimeImmutable $at;

    public function __construct(
        string $id,
        int $customerId,
        string $type,
        Money $amount,
        \DateTimeImmutable $at
    ) {
        if (!in_array($type, [self::TYPE_DEPOSIT, self::TYPE_WITHDRAW], true)) {
            throw new \InvalidArgumentException('Tipo transazione non supportato.');
        }

        $this->id = $id;
        $this->customerId = $customerId;
        $this->type = $type;
        $this->amount = $amount;
        $this->at = $at;
    }

    public function id(): string { return $this->id; }
    public function customerId(): int { return $this->customerId; }
    public function type(): string { return $this->type; }
    public function amount(): Money { return $this->amount; }
    public function at(): \DateTimeImmutable { return $this->at; }
}
