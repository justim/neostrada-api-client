<?php

class Neostrada
{
	const API_HOST = 'https://api.neostrada.nl/';

	private $_key;
	private $_secret;

	public function __construct($key, $secret)
	{
		$this->_key = $key;
		$this->_secret = $secret;
	}

	public function domain($domain)
	{
		return new Neostrada_Domain($this, $domain);
	}

	public function save(Neostrada_Domain $domain)
	{
		$data = [];

		foreach ($domain->getRecords() as $record)
		{
			$data[$record->neostradaDnsId] = $record->toNeostradaFormat();
		}

		$this->request($domain, 'dns', [
			'dnsdata' => serialize($data),
		]);

		return $this;
	}

	public function request(Neostrada_Domain $domain, $action, array $rawParams = [])
	{
		$params = [
			'domain' => $domain->getName(),
			'extension' => $domain->getExtension(),
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

		$oldUseErrors = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($rawData);

		if ($xml === false)
		{
			$message = libxml_get_errors()[0]->message;
			libxml_use_internal_errors($oldUseErrors);
			throw new \RuntimeException('Invalid XML: ' . $message);
		}

		libxml_use_internal_errors($oldUseErrors);

		$this->_validateResponse($xml);

		return $xml;
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
}
