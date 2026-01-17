<?php
/**
 * Caricatore minimale di file .env.
 *
 * Lo scopo didattico è mostrare:
 * - lettura da file
 * - parsing di righe chiave=valore
 * - gestione di commenti e righe vuote
 *
 * NOTA: non è un parser completo come quello di librerie dedicate.
 */

declare(strict_types=1);

namespace App\Support;

final class EnvLoader
{
    /**
     * Carica le variabili da un file .env nel superglobale $_ENV.
     *
     * @param string $envFile Path del file .env
     */
    public static function load(string $envFile): void
    {
        if (!is_file($envFile)) {
            // In un progetto reale potremmo lanciare un'eccezione.
            // Qui usiamo una scelta piu' permissiva per facilitare l'esercizio.
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Salta righe vuote o commenti (# ...)
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Cerchiamo il separatore '='
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Gestione molto semplice delle virgolette
            $value = self::stripOptionalQuotes($value);

            if ($key !== '') {
                $_ENV[$key] = $value;
            }
        }
    }

    private static function stripOptionalQuotes(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
