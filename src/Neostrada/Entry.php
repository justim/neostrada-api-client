<?php

class Neostrada_Entry
{
	private $_deleted = false;

	public $neostradaDnsId;
	public $name;
	public $type;
	public $content;
	public $ttl = 3600;
	public $priority = 0;

	private $_domain;

	public function __construct(Neostrada_Domain $domain, $info = null)
	{
		$this->_domain = $domain;

		if ($info !== null)
		{
			list(
				$this->neostradaDnsId,
				$this->name,
				$this->type,
				$this->content,
				$this->ttl,
				$this->priority
				) = explode(';', $info);
		}
	}

	public function save()
	{
		return $this->_domain->save();
	}

	public function setDeleted()
	{
		$this->_deleted = true;
		return $this;
	}

	public function isDeleted()
	{
		return $this->_deleted;
	}

	public function toNeostradaFormat()
	{
		$neostradaEntry = [
			'name' => $this->name,
			'type' => $this->type,
			'content' => $this->content,
			'prio' => $this->priority,
			'ttl' => $this->ttl,
		];

		if ($this->isDeleted())
		{
			$neostradaEntry['delete'] = 1;
		}

		return $neostradaEntry;
	}

	// setter for chaining
	public function setNeostradaDnsId($neostradaDnsId)
	{
		$this->neostradaDnsId = $neostradaDnsId;
		return $this;
	}

	// setter for chaining
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	// setter for chaining
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	// setter for chaining
	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}

	// setter for chaining
	public function setTtl($ttl)
	{
		$this->ttl = $ttl;
		return $this;
	}

	// setter for chaining
	public function setPriority($priority)
	{
		$this->priority = $priority;
		return $this;
	}
}
