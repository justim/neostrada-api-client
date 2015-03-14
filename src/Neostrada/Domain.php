<?php

class Neostrada_Domain
{
	private $_client;

	private $_name;
	private $_extension;
	private $_records = [];

	public function __construct(Neostrada $client, $domain)
	{
		$this->_client = $client;

		if (substr_count($domain, '.') !== 1)
		{
			throw new \InvalidArgumentException('Invalid domain: ' . $domain);
		}

		list($this->_name, $this->_extension) = explode('.', trim($domain));

		$this->_getDns();
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getExtension()
	{
		return $this->_extension;
	}

	public function getDomain()
	{
		return $this->_name . '.' . $this->_extension;
	}

	public function getRecords()
	{
		return $this->_records;
	}

	private function _getDns()
	{
		$this->_records = [];
		$xml = $this->_client->request($this, 'getdns');

		foreach ($xml->dns->item as $item)
		{
			$this->_records[explode(';', (string) $item)[0]] = new Neostrada_Record($this, (string) $item);
		}

		return $this;
	}

	public function create($type)
	{
		$record = new Neostrada_Record($this);
		$record->type = strtoupper($type);
	}

	public function add(Neostrada_Record $record)
	{
		return $this->_client->request($this, 'adddns', $record->toNeostradaFormat());
	}

	private function _single($type, $name, $content = null)
	{
		foreach ($this->_records as $record)
		{
			if ($record->type === $type && $record->name === $name)
			{
				if ($content !== null)
				{
					$record->setContent($content);
					return $this;
				}
				else
				{
					return $record;
				}
			}
		}

		return null;
	}

	private function _multiple($type)
	{
		$rs = new Neostrada_Records($this, $type);

		foreach ($this->_records as $record)
		{
			if ($record->type === $type)
			{
				$rs[] = $record;
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
		$xml = $this->_client->request('getnameserver');

		if (isset($xml->nameservers->nameserver0)) $nss[] = (string) $xml->nameservers->nameserver0;
		if (isset($xml->nameservers->nameserver1)) $nss[] = (string) $xml->nameservers->nameserver1;
		if (isset($xml->nameservers->nameserver2)) $nss[] = (string) $xml->nameservers->nameserver2;

		return $nss;
	}

	public function save()
	{
		$this->_client->save($this);
		return $this;
	}
}
