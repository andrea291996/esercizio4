<?php
/**
 * Autoloader molto semplice. Serve a importare dipendenze locali in maniera
 * sintatticamente semplice e coerente.
 *
 * In un progetto reale useremmo Composer (PSR-4). Qui evitiamo dipendenze esterne
 * per mantenere l'esercizio accessibile.
 *
 * L'autoloader trasforma un nome di classe con namespace in un percorso file.
 * Esempio: App\Domain\Customer -> src/Domain/Customer.php
 */

declare(strict_types=1);

namespace App\Support;

final class Autoloader
{
    /**
     * Registra l'autoloader con PHP.
     *
     * @param string $projectRoot Percorso alla root del progetto (dove c'e' la cartella src/)
     */
    public static function register(string $projectRoot): void
    {
        spl_autoload_register(function (string $className) use ($projectRoot): void {
            // Supportiamo solo classi nel namespace App\...
            $prefix = 'App\\';
            if (!str_starts_with($className, $prefix)) {
                return;
            }

            // Rimuovo il prefisso e converto i separatori di namespace in separatori di cartelle
            $relative = substr($className, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative);

            $file = rtrim($projectRoot, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'src'
                . DIRECTORY_SEPARATOR
                . $relativePath
                . '.php';

            if (is_file($file)) {
                require_once $file;
            }
        });
    }
}
