# Nestrada API client

> API client for DNS/hosting provider Neostrada

## Requirements

* `PHP >= 5.4`

## Installation

* Add `justim/neostrada-api-client` to you `composer.json`
* `composer install justim/neostrada-api-client`

## Usage

```php
$neostrada = new Neostrada($apiKey, $secret);

$domain = $neostrada->domain('example.com');
$domain->a('www', '127.0.0.1');
$domain->save();

$mxRecords = $domain->mx(); // lists all MX-records

foreach ($mxRecords as $mx)
{
	$mx->content = 'mail.google.com';
}

$mxRecords->save();
```
