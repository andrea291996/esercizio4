<?php

declare(strict_types=1);

namespace App\Domain;

class Config {
    private $currency;
    private $logTransactions;
    private $minimoDeposito;
    private $massimoDeposito;
    private $limitePrelievoGiornaliero;
    private $minimoPrelievo;
    private $massimoPrelievo;

    public function __construct($currency, $logTransactions, $minimoDeposito, $massimoDeposito, $limitePrelievoGiornaliero, $minimoPrelievo, $massimoPrelievo){
        $this->currency = $currency;
        $this->logTransactions = $logTransactions;
        $this->minimoDeposito = $minimoDeposito;
        $this->massimoDeposito = $massimoDeposito;
        $this->limitePrelievoGiornaliero = $limitePrelievoGiornaliero;
        $this->minimoPrelievo = $minimoPrelievo;
        $this->massimoPrelievo = $massimoPrelievo;
    }

    public function getLimitePrelievoGiornaliero(){
        return $this->limitePrelievoGiornaliero;
    }

    public function getLogTransactions(){
        return $this->logTransactions;
    }
}