<?php

class Neostrada_Records implements ArrayAccess, Iterator, Countable
{
	private $_domain;
	private $_type;

	private $_records = [];
	private $_position = 0;

	public function __construct(Neostrada_Domain $domain, $type, array $records = [])
	{
		$this->_domain = $domain;
		$this->_type = $type;
		$this->_records = $records;
	}

	public function sort(Callable $compareFunc)
	{
		usort($this->_records, $compareFunc);
	}

	public function filter(Callable $callback)
	{
		$this->_records = array_filter($this->_records, $callback);
	}

	public function save()
	{
		return $this->_domain->save();
	}

	/////////////////////////////////////////////
	// ArrayAccess

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_records);
	}

	public function offsetGet($offset)
	{
		return $this->_records[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if ($offset === null)
		{
			$this->_records[] = $value;
		}
		else
		{
			$this->_records[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		$this->_records[$offset]->setDeleted();

		unset($this->_records[$offset]);
		$this->_records = array_values($this->_records);
		$this->rewind();
	}

	////////////////////////////////////////////
	// Iterator

	public function rewind()
	{
		$this->_position = 0;
	}

	public function current()
	{
		return $this->_records[$this->_position];
	}

	public function key()
	{
		return $this->_position;
	}

	public function next()
	{
		$this->_position++;
	}

	public function valid()
	{
		return array_key_exists($this->_position, $this->_records);
	}

	///////////////////////////////////////////////
	// Countable

	public function count()
	{
		return count($this->_records);
	}
}
