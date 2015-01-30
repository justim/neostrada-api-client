# Nestrada API client

> API client for DNS/hosting provider Neostrada

## Requirements

* `PHP >= 5.4`

## Installation

* Add `justim/neostrada-api-client` to you `composer.json`
* `composer install justim/neostrada-api-client`

## Usage

```php
$neostrada = new Neostrada($apiKey, $secret, 'example.com');
$neostrada->a('www', '127.0.0.1');
$neostrada->save();

$mxs = $neostrada->mx(); // lists all MX-records

foreach ($mxs as $mx)
{
	$mx->content = 'mail.google.com';
}

$mxs->save();
```
