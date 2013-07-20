<?php

namespace Openbuildings\Spiderling;

/**
 * Functest_Environment definition
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Environment {

	protected $_groups = array();
	protected $_backup = array();

	public function __construct(array $groups = array(), array $parameters = array())
	{
		if ($groups) 
		{
			$this->groups($groups);
		}
		
		if ($parameters)
		{
			$this->backup_and_set($parameters);
		}
	}

	public function restore()
	{
		$this->set($this->_backup);
		$this->_backup = array();
		return $this;
	}
	
	public function groups($key = NULL, $value = NULL)
	{
		if ($key === NULL)
			return $this->_groups;
	
		if (is_array($key))
		{
			$this->_groups = $key;
		}
		else
		{
			if ($value === NULL)
				return isset($this->_groups[$key]) ? $this->_groups[$key] : NULL;
	
			$this->_groups[$key] = $value;
		}
	
		return $this;
	}

	public function backup_and_set(array $parameters)
	{
		return $this
			->backup(array_keys($parameters))
			->set($parameters);
	}

	public function group_for_name($name)
	{
		foreach ($this->_groups as $group) 
		{
			if ($group->has($name)) 
			{
				return $group;
			}
		}
		throw new Exception("Environment variable :name does not belong to any group", array(':name' => $name));
	}

	public function backup(array $parameters)
	{
		foreach ($parameters as $name)
		{
			$this->_backup[$name] = $this->group_for_name($name)->get($name);
		}
		return $this;
	}

	public function set(array $parameters)
	{
		foreach ($parameters as $name => $value) 
		{
			$this->group_for_name($name)->set($name, $value);
		}
		return $this;
	}
}