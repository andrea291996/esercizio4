<?php

declare(strict_types=1);

namespace App\Domain;
use App\Domain\Money;

//METTI A POSTO USANDO MONEY 

class Config {
    private $currency;
    private $logTransactions;
    private $minimoDeposito;
    private Money $massimoDeposito;
    private $limitePrelievoGiornaliero;
    private $minimoPrelievo;
    private $massimoPrelievo;
    private $commissione;

    public function __construct($currency, $logTransactions, $minimoDeposito, $massimoDeposito, $limitePrelievoGiornaliero, $minimoPrelievo, $massimoPrelievo, $commissione){
        $this->currency = $currency;
        $this->logTransactions = $logTransactions;
        $this->minimoDeposito = $minimoDeposito;
        $this->massimoDeposito = Money::fromCents((int)$massimoDeposito);
        $this->limitePrelievoGiornaliero = $limitePrelievoGiornaliero;
        $this->minimoPrelievo = $minimoPrelievo;
        $this->massimoPrelievo = $massimoPrelievo;
        $this->commissione = $commissione;
    }

    public function getLimitePrelievoGiornaliero(){
        $limitePrelievoGiornaliero = $this->limitePrelievoGiornaliero;
        return (int)$limitePrelievoGiornaliero;
    }

    public function getLogTransactions(){
        return $this->logTransactions;
    }

    public function getMinimoDeposito(){
        $minimoDeposito = $this->minimoDeposito;
        return (int)$minimoDeposito;
    }

    public function getMassimoDeposito():Money{
        return $this->massimoDeposito;
       
    }

    public function getMassimoPrelievo(){
        $massimoPrelievo = $this->massimoPrelievo;
        return (int)$massimoPrelievo;
    }

    public function getMinimoPrelievo(){
        $minimoPrelievo = $this->minimoPrelievo;
        return (int)$minimoPrelievo;
    }

    public function getCommissione(){
        $commissione = $this->commissione;
        return (int)$commissione;
    }
}