<?php

class Neostrada_Entries implements ArrayAccess, Iterator, Countable
{
	private $_domain;
	private $_type;

	private $_entries = [];
	private $_position = 0;

	public function __construct(Neostrada_Domain $domain, $type, array $entries = [])
	{
		$this->_domain = $domain;
		$this->_type = $type;
		$this->_entries = $entries;
	}

	public function sort(Callable $compareFunc)
	{
		usort($this->_entries, $compareFunc);
	}

	public function filter(Callable $callback)
	{
		$this->_entries = array_filter($this->_entries, $callback);
	}

	public function save()
	{
		return $this->_domain->save();
	}

	/////////////////////////////////////////////
	// ArrayAccess

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_entries);
	}

	public function offsetGet($offset)
	{
		return $this->_entries[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if ($offset === null)
		{
			$this->_entries[] = $value;
		}
		else
		{
			$this->_entries[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		$this->_entries[$offset]->setDeleted();

		unset($this->_entries[$offset]);
		$this->_entries = array_values($this->_entries);
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
		return $this->_entries[$this->_position];
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
		return array_key_exists($this->_position, $this->_entries);
	}

	///////////////////////////////////////////////
	// Countable

	public function count()
	{
		return count($this->_entries);
	}
}
