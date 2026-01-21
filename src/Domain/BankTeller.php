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
use App\Infrastructure\TransactionRepository;

final class BankTeller
{
    private CustomerRepository $customers;
    private TransactionLogger $logger;
    private string $currency;
    private Config $config;

    public function __construct(CustomerRepository $customers, TransactionLogger $logger, string $currency, Config $config)
    {
        $this->customers = $customers;
        $this->logger = $logger;
        $this->currency = $currency;
        $this->config = $config;
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
        //SISTEMA: DOVRESTI USARE LA CLASSE MONEY E LA SUA FUNZIONE GREATER THAN MAGARIC CREA LESSER THAN
        $customer = $this->requireCustomer($customerId);
        $minimoDeposito = $this->config->getMinimoDeposito();
        $massimoDeposito = $this->config->getMassimoDeposito();
        $amountCents = $amount->cents();
        if($amount->greaterThan($massimoDeposito)){
            throw new \RuntimeException("Hai superato il limite massimo di deposito!");
        }
        if($amountCents < $minimoDeposito){
            throw new \RuntimeException("Non hai superato il limite minimo di deposito!");
        }
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
    public function withdraw(int $customerId, Money $amount, $transactionRepo): Money
    {
        $customer = $this->requireCustomer($customerId);
        $transazioniDiCustomer = $transactionRepo->getTransactionsByCustomerId($customerId);
        $transazioniDiOggi = [];
        $oggi = new \DateTimeImmutable('now');
        foreach($transazioniDiCustomer as $transazione){
            if(($transazione->at()->format('Y-m-d') === $oggi->format('Y-m-d')) && $transazione->type() === "WITHDRAW"){
                $transazioniDiOggi[] = $transazione->amount()->cents();
            }
        }
        $amountCents = $amount->cents();
        $totalePreleviOggi = array_sum($transazioniDiOggi);
        $totale = $totalePreleviOggi + $amountCents;
        //echo "limite:" .$limitePrelievoGiornaliero;
        //echo "\nprelievo: ".$totale;
        $limitePrelievoGiornaliero = $this->config->getLimitePrelievoGiornaliero();
        $logTransactions = $this->config->getLogTransactions();
        $minimoPrelievo = $this->config->getMinimoPrelievo();
        $massimoPrelievo = $this->config->getMassimoPrelievo();

        if($amountCents > $massimoPrelievo){
            throw new \RuntimeException("Hai superato il limite massimo di prelievo!");
        }

        if($amountCents > $limitePrelievoGiornaliero){
            throw new \RuntimeException("Hai superato il limite giornaliero di prevlievo.");
        }

        if($amountCents < $minimoPrelievo){
            throw new \RuntimeException("Non hai superato il limite minimo di prelievo!");
        }

        if($logTransactions){
            $commissioneInt = $this->config->getCommissione();
            $commissione = Money::fromCents($commissioneInt);
            $amount = $amount->add($commissione);
            $customer->account()->withdraw($amount);
            $this->customers->save($customer);
            $this->logger->log(new Transaction(
            $this->newTransactionId(),
            $customerId,
            Transaction::TYPE_WITHDRAW,
            $amount,
            new \DateTimeImmutable('now')
        ));

        return $customer->account()->balance();}
        
    }

    public function transfer(int $idMittente, int $idDestinatario, Money $importo){
        $mittente = $this->requireCustomer($idMittente);
        $destinatario = $this->requireCustomer($idDestinatario);
        if ($mittente === null) {
            throw new \RuntimeException('Mittente non trovato. ID: ' . $idMittente);
        }
        if ($destinatario === null) {
            throw new \RuntimeException('Mittente non trovato. ID: ' . $idDestinatario);
        }

        if ($destinatario == $mittente) {
            throw new \RuntimeException('Mittente e Destinatario non possono coincidere');
        }

        $commissioneInt = $this->config->getCommissione();
        $commissione = Money::fromCents($commissioneInt);
        $importoConCommissione = $importo->add($commissione);


        //ho aggiunto la commissione solo su chi invia denaro cosi per divertimento
        $mittente->account()->withdraw($importoConCommissione);
        $destinatario->account()->deposit($importo);
        $transactionId = $this->newTransactionId();

        $this->customers->save($mittente);
            $this->logger->log(new Transaction(
            $transactionId,
            $idMittente,
            Transaction::TYPE_WITHDRAW,
            $importo,
            new \DateTimeImmutable('now')
        ));
        $this->customers->save($destinatario);
            $this->logger->log(new Transaction(
            $transactionId,
            $idDestinatario,
            Transaction::TYPE_DEPOSIT,
            $importo,
            new \DateTimeImmutable('now')
        ));
        return "L'operazione è andata a buon fine.";
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
