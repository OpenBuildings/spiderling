<?php

namespace Openbuildings\Spiderling;

/**
 * NodeList represents a list of dom nodes. Has lazy loading
 *
 * @package    Openbuildings\Spiderling
 * @author     Ivan Kerin
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Nodelist implements \Iterator, \Countable, \SeekableIterator, \ArrayAccess {

	/**
	 * The driver for traversing this node and its children
	 * @var Driver
	 */
	protected $_driver;

	/**
	 * The parent node, NULL if root
	 * @var Node
	 */
	protected $_parent;

	/**
	 * Cache for the ids of this node list
	 * @var array
	 */
	protected $_list_ids;

	/**
	 * Single node object to be used during iteration
	 * @var Node
	 */
	protected $_node;

	/**
	 * Implementing Iterator
	 * @var integer
	 */
	protected $_current = 0;

	function __construct(Driver $driver, Locator $locator, Node $parent)
	{
		$this->_driver  = $driver;
		$this->_locator = $locator;
		$this->_parent  = $parent;
		$this->_node = new Node($driver, $parent);
	}

	/**
	 * Returns a string representation of the collection.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		$nodes = array();
		foreach($this as $node)
		{
			$nodes[] = $node->html();
		}
		return "Nodelist: \nLocator: {$this->locator()} \nContent: [\n".join("\n", $nodes)."\n]\n";
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  Nodelist
	 */
	public function rewind()
	{
		$this->_current = 0;
		return $this;
	}

	/**
	 * Implementation of the Iterator interface
	 *
	 * @return  Node
	 */
	public function current()
	{
		$ids = $this->list_ids();
		return $this->_load($ids[$this->_current]);
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  int
	 */
	public function key()
	{
		return $this->_current;
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  Nodelist
	 */
	public function next()
	{
		++$this->_current;
		return $this;
	}

	/**
	 * Implementation of the Iterator interface
	 *
	 * @return  boolean
	 */
	public function valid()
	{
		return $this->offsetExists($this->_current);
	}

	/**
	 * Implementation of the Countable interface
	 *
	 * @return  int
	 */
	public function count()
	{
		return count($this->list_ids());
	}

	/**
	 * Implementation of SeekableIterator
	 *
	 * @param   mixed  $offset
	 * @return  boolean
	 */
	public function seek($offset)
	{
		if ($this->offsetExists($offset))
		{
			$this->_current = $offset;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ArrayAccess: offsetExists
	 *
	 * @param   mixed  $offset
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 AND $offset < $this->count());
	}

	/**
	 * ArrayAccess: offsetGet
	 *
	 * @param   mixed  $offset
	 * @return  Node
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->offsetExists($offset))
			return NULL;
		$ids = $this->list_ids();
		return $this->_load($ids[$offset]);
	}

	/**
	 * ArrayAccess: offsetSet
	 *
	 * @throws  Kohana_Exception
	 * @param   mixed  $offset
	 * @param   mixed  $value
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		throw new Exception('Cannot modify Nodelist');
	}

	/**
	 * ArrayAccess: offsetUnset
	 *
	 * @throws  Kohana_Exception
	 * @param   mixed  $offset
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		throw new Exception('Cannot modify Nodelist');
	}

	protected function _load($id)
	{
		return $this->_node->load_new_id($id);
	}

	protected function list_ids()
	{
		if ($this->_list_ids === NULL)
		{
			$this->_list_ids = (array) $this->_driver->all($this->_locator->xpath(), $this->_parent->id());

			if ($this->_locator->filters())
			{
				foreach ($this->_list_ids as $offset => $id)
				{
					if ( ! $this->_locator->is_filtered($this->_load($id), $offset))
					{
						unset($this->_list_ids[$offset]);
					}
				}
			}
		}

		return $this->_list_ids;
	}

	public function locator()
	{
		return $this->_locator;
	}

	public function driver()
	{
		return $this->_driver;
	}

	public function first()
	{
		$ids = $this->list_ids();

		if (count($ids) <= 0)
			return NULL;

		return $this->_load(reset($ids));
	}

	public function last()
	{
		$ids = $this->list_ids();

		if (count($ids) <= 0)
			return NULL;

		return $this->_load(end($ids));
	}

	public function as_array()
	{
		$nodes = array();
		foreach ($this->list_ids() as $i => $id)
		{
			$nodes[] = new Node($this->_driver, $this->_parent, $id);
		}
		return $nodes;
	}

}
