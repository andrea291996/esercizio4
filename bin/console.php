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
use App\Domain\Config;
use App\Infrastructure\CsvCustomerRepository;
use App\Infrastructure\CsvTransactionLogger;
use App\Infrastructure\NullTransactionLogger;
use App\Infrastructure\TransactionRepository;
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
$minimoDeposito = $_ENV['MIN_DEPOSIT_CENTS'];
$massimoDeposito = $_ENV['MAX_DEPOSIT_CENTS'];
$limitePrelievoGiornaliero = $_ENV['DAILY_WITHDRAW_LIMIT_CENTS'];
$minimoPrelievo = $_ENV['MIN_WITHDRAW_CENTS'];
$massimoPrelievo = $_ENV['MAX_WITHDRAW_CENTS'];

$config = new Config($currency, $logTransactions, $minimoDeposito, $massimoDeposito, $limitePrelievoGiornaliero, $minimoPrelievo, $massimoPrelievo);

$customersCsv = rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'customers_example.csv';
$transactionsCsv = rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'transactions_example.csv';

// 3) Costruzione delle dipendenze
$customerRepo = new CsvCustomerRepository($customersCsv);
$logger = $logTransactions ? new CsvTransactionLogger($transactionsCsv) : new NullTransactionLogger();
$transactionRepo= new TransactionRepository($transactionsCsv, $logTransactions);
$bankTeller = new BankTeller($customerRepo, $logger, $currency, $config);

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
    ConsoleIO::println('  5) Crea nuovo cliente');
    ConsoleIO::println('  6) Estratto conto (ultime N transazioni)');
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
                try{
                    $amount = Money::fromUserInput($raw);
                    $newBalance = $bankTeller->deposit($id, $amount);
                    ConsoleIO::println('Deposito effettuato. Nuovo saldo: ' . $bankTeller->formatMoney($newBalance));
                }catch(\Exception $e){
                    echo $e->getMessage();
                }
                break;

            case '4':
                $id = ConsoleIO::readNonNegativeInt('Inserisci ID cliente: ');
                $raw = ConsoleIO::readLine('Importo da prelevare (es. 10.50): ');
                try{
                    $amount = Money::fromUserInput($raw);
                    $newBalance = $bankTeller->withdraw($id, $amount, $transactionRepo);
                    ConsoleIO::println('Prelievo effettuato. Nuovo saldo: ' . $bankTeller->formatMoney($newBalance));
                }
                catch(\Exception$e){
                    echo $e->getMessage();
                }
                break;
            
            case '5':
                $nome = ConsoleIO::readLine('Inserisci il tuo nome e cognome (es. Mario Rossi): ');
                $saldoRaw = ConsoleIO::readLine('Inserisci il tuo saldo iniziale: ');
                $saldo = Money::fromUserInput($saldoRaw);
                $nuovoCliente = $customerRepo->create($nome, $saldo);
                ConsoleIO::println('Nuovo account creato. Id: '.$nuovoCliente->id().' Saldo: '.$bankTeller->formatMoney($saldo));
                break;
            
            case '6':
                $id = ConsoleIO::readNonNegativeInt('Inserisci ID cliente: ');
                $n = ConsoleIO::readNonNegativeInt('Quante transazioni vuoi vedere? ');
                try{
                    $transactions = $transactionRepo->getLastNTransactions($n, $id);
                    foreach($transactions as $transaction){
                        $type = $transaction->type();
                        $amount = $transaction->amount();
                        $amount1 = $bankTeller->formatMoney($amount);
                        ConsoleIO::println("Tipo: ".$type." Quantità: ".$amount1);
                    }
                }
                catch(\Exception $e){
                    echo $e->getMessage();
                }
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
