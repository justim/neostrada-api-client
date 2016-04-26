# Nestrada API client

> API client for DNS/hosting provider Neostrada

## Requirements

* `PHP >= 5.4`

## Installation

* Add `justim/neostrada-api-client` to your `composer.json`
* `composer install justim/neostrada-api-client`

## Usage

```php
$neostrada = new Neostrada($apiKey, $secret);

$domain = $neostrada->domain('example.com');

// set A-record for www to 127.0.0.1
$domain->a('www', '127.0.0.1');

// you can do the same for CNAME-records
$domain->cname('autodiscover', 'autodiscover.outlook.com');

// alternatively you can get an instance of a record and make your changes there
$a = $domain->a('www');
$a->content = '10.0.0.2';
$a->ttl = 1800;

// making changes to current records doesn't automatically save changes
$domain->save();

$mxRecords = $domain->mx(); // lists all MX-records

foreach ($mxRecords as $mx)
{
	// change the content of the record
	$mx->content = 'mail.google.com';

	// mark the records as deleted
	$mx->setDeleted();
}

$mxRecords->save();

// adding records can be done by a new record and adding it
$a = $domain->create('a');
$a->name = 'mail';
$a->content = '127.0.0.1';
$domain->add($a); // adding a record saves it immediately

// fetching the auth code
$authCode = $domain->authCode();
```

## List of possible API calls
- [x] getnameserver
- [x] getdns
- [x] dns
- [x] adddns
- [x] gettoken
- [ ] extensions
- [ ] whois
- [ ] holder
- [ ] deleteholder
- [ ] getholders
- [ ] register
- [ ] nameserver
