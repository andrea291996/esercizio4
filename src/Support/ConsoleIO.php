<?php
/**
 * Piccola classe di utilità per input/output da console, con validazione input.
 *
 * Obiettivi didattici:
 * - usare metodi statici per funzioni "di servizio"
 * - validare input utente
 * - separare logica di I/O dalla logica di dominio
 */

declare(strict_types=1);

namespace App\Support;

final class ConsoleIO
{
    /**
     * Stampa una riga su stdout.
     */
    public static function println(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Legge una riga da stdin.
     */
    public static function readLine(string $prompt): string
    {
        echo $prompt;
        $line = fgets(STDIN);
        if ($line === false) {
            return '';
        }
        return trim($line);
    }

    /**
     * Chiede un intero positivo (>= 0).
     */
    public static function readNonNegativeInt(string $prompt): int
    {
        while (true) {
            $raw = self::readLine($prompt);
            if ($raw === '') {
                self::println('Valore non valido. Riprova.');
                continue;
            }

            // ctype_digit verifica che siano solo cifre (no segno, no spazi)
            if (!ctype_digit($raw)) {
                self::println('Devi inserire un numero intero >= 0.');
                continue;
            }

            return (int)$raw;
        }
    }
}
