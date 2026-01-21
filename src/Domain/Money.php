<?php
/**
 * Value Object Money.
 *
 * Per evitare problemi tipici dei float con i soldi, rappresentiamo gli importi
 * in centesimi (int).
 *
 * Esempio:
 * - 10,50 EUR -> 1050 centesimi
 */

declare(strict_types=1);

namespace App\Domain;

final class Money
{
    private int $cents;

    private function __construct(int $cents)
    {
        $this->cents = $cents;
    }

    /**
     * Crea un importo da centesimi.
     */
    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Crea un importo da una stringa decimale inserita dall'utente.
     *
     * Accettiamo formati semplici come "10" oppure "10.50".
     */
    public static function fromUserInput(string $amount): self
    {
        $amount = trim($amount);
        if ($amount === '') {
            return new self(0);
        }

        // Normalizziamo la virgola in punto.
        $amount = str_replace(',', '.', $amount);

        // Regex per validazione dell'importo: numeri+virgola+numeri
        $regex = "/^[0-9]+(\.[0-9]{0,2})?$/";
        if (!preg_match($regex, $amount)) {
            throw new \InvalidArgumentException("L'importo deve essere un numero maggiore o uguale a 0.01 con un al massimo due cifre decimali\n");
            return new self(0);
        }

        // Split sulla parte decimale.
        $parts = explode('.', $amount, 2);
        $eurosPart = $parts[0];
        $centsPart = $parts[1] ?? '0';

        /*
        // Validazione base: solo cifre.
        if ($eurosPart === '' || !ctype_digit(ltrim($eurosPart, '0')) && $eurosPart !== '0') {
            // In caso di input strano, torniamo 0 per non rompere l'esercizio.
            return new self(0);
        }
        if ($centsPart !== '' && !ctype_digit($centsPart)) {
            return new self(0);
        }
        */

        $euros = (int)$eurosPart;
        $centsPart = substr(str_pad($centsPart, 2, '0'), 0, 2);
        $cents = (int)$centsPart;

        return new self($euros * 100 + $cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function greaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function lesserThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }
    
    public function format(string $currency = 'EUR'): string
    {
        $euros = intdiv(abs($this->cents), 100);
        $cents = abs($this->cents) % 100;
        $sign = $this->cents < 0 ? '-' : '';

        return sprintf('%s%d.%02d %s', $sign, $euros, $cents, $currency);
    }
}
