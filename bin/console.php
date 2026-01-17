<?php
/**
 * Entry point CLI del progetto.
 *
 * Avvio:
 *   php bin/console.php
 *
 * Qui "montiamo" le dipendenze (repository, logger, servizio BankTeller)
 * e gestiamo un menu testuale.
 */

declare(strict_types=1);

use App\Domain\BankTeller;
use App\Domain\Money;
use App\Infrastructure\CsvCustomerRepository;
use App\Infrastructure\CsvTransactionLogger;
use App\Infrastructure\NullTransactionLogger;
use App\Support\Autoloader;
use App\Support\ConsoleIO;
use App\Support\EnvLoader;

// 1) Bootstrap: carico l'autoloader e il .env
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/src/Support/Autoloader.php';
Autoloader::register($projectRoot);

EnvLoader::load($projectRoot . '/.env');

// 2) Configurazione con valori di default
$dataDir = $_ENV['DATA_DIR'] ?? ($projectRoot . '/data');
$currency = $_ENV['CURRENCY'] ?? 'EUR';
$logTransactions = strtolower((string)($_ENV['LOG_TRANSACTIONS'] ?? 'true')) === 'true';

$customersCsv = rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'customers.csv';
$transactionsCsv = rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'transactions.csv';

// 3) Costruzione delle dipendenze
$customerRepo = new CsvCustomerRepository($customersCsv);
$logger = $logTransactions ? new CsvTransactionLogger($transactionsCsv) : new NullTransactionLogger();

$bankTeller = new BankTeller($customerRepo, $logger, $currency);

// 4) Menu principale
ConsoleIO::println('========================================');
ConsoleIO::println(' Sportello Bancario (CLI) - Progetto OOP');
ConsoleIO::println('========================================');

while (true) {
    ConsoleIO::println();
    ConsoleIO::println('Menu:');
    ConsoleIO::println('  1) Elenca clienti');
    ConsoleIO::println('  2) Mostra saldo cliente');
    ConsoleIO::println('  3) Deposita');
    ConsoleIO::println('  4) Preleva');
    ConsoleIO::println('  0) Esci');

    $choice = ConsoleIO::readLine('Scelta: ');

    try {
        switch ($choice) {
            case '1':
                $customers = $bankTeller->listCustomers();
                if (count($customers) === 0) {
                    ConsoleIO::println('Nessun cliente presente. Inseriscine uno nel CSV: data/customers.csv');
                    break;
                }

                ConsoleIO::println('Clienti disponibili:');
                foreach ($customers as $c) {
                    $balance = $bankTeller->formatMoney($c->account()->balance());
                    ConsoleIO::println(sprintf('  - ID %d | %s | Saldo: %s', $c->id(), $c->name(), $balance));
                }
                break;

            case '2':
                $id = ConsoleIO::readNonNegativeInt('Inserisci ID cliente: ');
                $customer = $bankTeller->getCustomer($id);
                if ($customer === null) {
                    ConsoleIO::println('Cliente non trovato.');
                    break;
                }
                ConsoleIO::println('Cliente: ' . $customer->name());
                ConsoleIO::println('Saldo: ' . $bankTeller->formatMoney($customer->account()->balance()));
                break;

            case '3':
                $id = ConsoleIO::readNonNegativeInt('Inserisci ID cliente: ');
                $raw = ConsoleIO::readLine('Importo da depositare (es. 10.50): ');
                $amount = Money::fromUserInput($raw);
                $newBalance = $bankTeller->deposit($id, $amount);
                ConsoleIO::println('Deposito effettuato. Nuovo saldo: ' . $bankTeller->formatMoney($newBalance));
                break;

            case '4':
                $id = ConsoleIO::readNonNegativeInt('Inserisci ID cliente: ');
                $raw = ConsoleIO::readLine('Importo da prelevare (es. 10.50): ');
                $amount = Money::fromUserInput($raw);
                $newBalance = $bankTeller->withdraw($id, $amount);
                ConsoleIO::println('Prelievo effettuato. Nuovo saldo: ' . $bankTeller->formatMoney($newBalance));
                break;

            case '0':
                ConsoleIO::println('Arrivederci!');
                exit(0);

            default:
                ConsoleIO::println('Scelta non valida.');
        }
    } catch (Throwable $e) {
        // Gestione errori semplice per l'esercizio.
        ConsoleIO::println('ERRORE: ' . $e->getMessage());
    }
}
