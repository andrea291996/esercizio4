# Sportello Bancario (PHP OOP) — Progetto didattico

Piccola applicazione **da riga di comando** (CLI) scritta in PHP che simula uno **sportello bancario**.

Obiettivo: esercitarsi con le basi della **Programmazione Orientata agli Oggetti** in PHP (classi, metodi, eccezioni, namespace).

## Funzionalità

- Elenco clienti
- Visualizzazione saldo
- Deposito di una somma
- Prelievo di una somma (con controllo fondi)
- Persistenza su file CSV (nessun database)
- Configurazione tramite file `.env`
- Logging opzionale delle transazioni su CSV

## Requisiti

- PHP 8.1+ (consigliato 8.2+)

Verifica versione:

```bash
php -v
```

## Avvio rapido

1) Clona il repository e posizionati nella cartella del progetto.

2) Crea il file `.env`

```bash
cp .env.example .env
```

2) Crea i file dati

```bash
cp ./data/transactions_example.csv ./data/transactions.csv
cp ./data/customers_example.csv ./data/customers.csv
```

3) Avvia l'applicazione:

```bash
php bin/console.php
```

## Dati su CSV

I clienti sono salvati in `data/customers.csv`.

Formato:

- `id` (intero)
- `name` (stringa)
- `balance_cents` (intero: saldo in centesimi)

Esempio:

```csv
id,name,balance_cents
1,Mario Rossi,12500
```

Le transazioni (se abilitate) sono salvate in `data/transactions.csv`.

Formato:

- `id` (stringa: id generato)
- `customer_id` (intero)
- `type` (`DEPOSIT` oppure `WITHDRAW`)
- `amount_cents` (intero)
- `at_iso` (timestamp ISO 8601)

## Configurazione (.env)

Chiavi supportate:

- `DATA_DIR` — cartella dove si trovano/salvano i CSV (default: `./data`)
- `CURRENCY` — valuta usata in output (default: `EUR`)
- `LOG_TRANSACTIONS` — `true/false` (default: `true`)

Esempio:

```env
DATA_DIR="./data"
CURRENCY="EUR"
LOG_TRANSACTIONS=true
```

## Struttura del progetto

```
php-sportello-bancario/
├── .gitignore
├── .env_example
├── README.md
├── bin/
│   └── console.php
├── src/
│   ├── Domain/
│   │   ├── Account.php
│   │   ├── BankTeller.php
│   │   ├── Customer.php
│   │   ├── Money.php
│   │   └── Transaction.php
│   ├── Infrastructure/
│   │   ├── CsvCustomerRepository.php
│   │   ├── CsvTransactionLogger.php
│   │   ├── CustomerRepository.php
│   │   ├── NullTransactionLogger.php
│   │   └── TransactionLogger.php
│   └── Support/
│       ├── Autoloader.php
│       ├── ConsoleIO.php
│       └── EnvLoader.php
├── data/
│   ├── customers_example.csv
│   └── transactions_example.csv
```

## Note didattiche

- **Money** è un *Value Object* e usa i **centesimi (int)** per evitare errori dei float.
- **Account** applica le regole di dominio (no importi negativi, no saldo sotto zero).
- **BankTeller** coordina repository, dominio e logging (esempio semplice di "Application Service").
- Il repository CSV riscrive il file intero per semplicità.
