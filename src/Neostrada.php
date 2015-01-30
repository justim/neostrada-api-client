<?php

class Neostrada
{
	const API_HOST = 'https://api.neostrada.nl/';

	private $_key;
	private $_secret;
	private $_name;
	private $_extension;
	private $_entries = [];

	public function __construct($key, $secret, $domain = null)
	{
		$this->_key = $key;
		$this->_secret = $secret;

		if ($domain !== null)
		{
			if (substr_count($domain, '.') !== 1)
			{
				throw new \InvalidArgumentException('Invalid domain: ' . $domain);
			}

			list($this->_name, $this->_extension) = explode('.', trim($domain));

			$this->_getDns();
		}
	}

	public function create($domain)
	{
		return new self($this->_key, $this->_secret, $domain);
	}

	public function getDomain()
	{
		return $this->_name . '.' . $this->_extension;
	}

	private function _getDns()
	{
		$this->_entries = [];
		$xml = $this->_request('getdns');

		foreach ($xml->dns->item as $item)
		{
			$this->_entries[explode(';', (string) $item)[0]] = new Neostrada_Entry($this, (string) $item);
		}

		return $this;
	}

	public function add(Neostrada_Entry $entry)
	{
		return $this->_request('adddns', $this->_toNeostradaFormat($entry));
	}

	private function _request($action, array $rawParams = [])
	{
		if ($this->_name === null)
		{
			throw new \InvalidArgumentException('No domain configured');
		}

		$params = [
			'domain' => $this->_name,
			'extension' => $this->_extension
		] + $rawParams;

		$params['api_sig'] = $this->_calculateSignature($action, $params);
		$params['action'] = $action;
		$params['api_key'] = $this->_key;

		$url = self::API_HOST . '?' . http_build_query($params, '', '&');

		$c = curl_init();

		if ($c === false)
		{
			throw new \RuntimeException('Could not initialize cURL');
		}

		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		$rawData = curl_exec($c);

		if ($rawData === false)
		{
			throw new \RuntimeException('Could not complete cURL request: ' . curl_error($c));
		}

		curl_close($c);

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($rawData);

		if ($xml === false)
		{
			throw new \RuntimeException('Invalid XML: ' . libxml_get_errors()[0]->message);
		}

		$this->_validateResponse($xml);

		return $xml;
	}

	private function _single($type, $name, $content = null)
	{
		foreach ($this->_entries as $entry)
		{
			if ($entry->type === $type && $entry->name === $name)
			{
				if ($content !== null)
				{
					$entry->setContent($content);
					return $this;
				}
				else
				{
					return $entry;
				}
			}
		}

		return null;
	}

	private function _multiple($type)
	{
		$rs = new Neostrada_Entries($this, $type);

		foreach ($this->_entries as $entry)
		{
			if ($entry->type === $type)
			{
				$rs[] = $entry;
			}
		}

		return $rs;
	}

	public function a($name, $content = null)
	{
		return $this->_single('A', $name, $content);
	}

	public function cname($name, $content = null)
	{
		return $this->_single('CNAME', $name, $content);
	}

	public function mx()
	{
		$mxs = $this->_multiple('MX');

		$mxs->sort(function($mx1, $mx2)
		{
			return $mx1->priority - $mx2->priority;
		});

		return $mxs;
	}

	public function spf()
	{
		$txts = $this->_multiple('TXT');

		$txts->filter(function($txt)
		{
			return strpos($txt->content, 'v=spf1') !== false;
		});

		return $txts;
	}

	public function ns()
	{
		$nss = [];
		$xml = $this->_request('getnameserver');

		if (isset($xml->nameservers->nameserver0)) $nss[] = (string) $xml->nameservers->nameserver0;
		if (isset($xml->nameservers->nameserver1)) $nss[] = (string) $xml->nameservers->nameserver1;
		if (isset($xml->nameservers->nameserver2)) $nss[] = (string) $xml->nameservers->nameserver2;

		return $nss;
	}

	public function save()
	{
		$data = [];

		foreach ($this->_entries as $entry)
		{
			$data[$entry->neostradaDnsId] = $this->_toNeostradaFormat($entry);
		}

		$this->_request('dns', [
			'dnsdata' => serialize($data),
		]);

		return $this;
	}

	private function _validateResponse(SimpleXMLElement $xml)
	{
		if ((string) $xml->code !== '200')
		{
			throw new \UnexpectedValueException('Request failed [' . $xml->code . ']: ' . $xml->description);
		}
	}

	private function _calculateSignature($action, array $params = [])
	{
		$signature = $this->_secret . $this->_key . 'action' . $action;

		foreach ($params as $key => $value)
		{
			$signature .= $key . $value;
		}

		return md5($signature);
	}

	private function _toNeostradaFormat(Neostrada_Entry $entry)
	{
		$neostradaEntry = [
			'name' => $entry->name,
			'type' => $entry->type,
			'content' => $entry->content,
			'prio' => $entry->priority,
			'ttl' => $entry->ttl,
		];

		if ($entry->isDeleted())
		{
			$neostradaEntry['delete'] = 1;
		}

		return $neostradaEntry;
	}
}
