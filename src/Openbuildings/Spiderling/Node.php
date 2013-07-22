<?php

namespace Openbuildings\Spiderling;

/**
 * Node - represents HTML node
 *
 * @package    Node
 * @author     Ivan Kerin
 * @copyright  (c) 2012 OpenBuildings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Node {

	protected $_driver;
	protected $_parent;
	protected $_id = NULL;
	protected $_next_wait_time = 2000;
	protected $_extension;
	
	function __construct(Driver $driver, Node $parent = NULL, $id = NULL)
	{
		$this->_driver = $driver;
		$this->_parent = $parent;
		$this->_id = $id;

		if ($parent AND $parent->_extension) 
		{
			$this->_extension = $parent->_extension;
		}
	}

	public function driver()
	{
		return $this->_driver;
	}

	public function extension($extension = NULL)
	{
		if ($extension !== NULL)
		{
			$this->_extension = $extension;
			return $this;
		}
		return $this->_extension;
	}

	public function parent()
	{
		return $this->_parent;
	}

	public function load_vars($id)
	{
		$this->_id = $id;
		return $this;
	}

	/**
	 * Set the next wait time
	 * @param  integer $next_wait_time milliseconds
	 * @return integer|Functest_Node
	 */
	public function next_wait_time($next_wait_time = NULL)
	{
		if ($next_wait_time !== NULL)
		{
			$this->_next_wait_time = $next_wait_time;
			return $this;
		}
		return $this->_next_wait_time;
	}

	public function wait($milliseconds = 1000)
	{
		usleep($milliseconds * 1000);
		
		return $this;
	}

	/**
	 * Get The wait time for finding a node, use next_wait_time if available
	 * @return integer milliseconds
	 */
	public function wait_time()
	{
		return $this->next_wait_time();
	}

	/**
	 * GETTERS
	 * ===========================================
	 */
	
	/**
	 * is this the main html page?
	 * @return boolean 
	 */
	public function is_root()
	{
		return ! (bool) $this->_id;
	}

	/**
	 * The DOMDocument or DOMElement representation of the current tag
	 * @return DOMDocument|DOMElement
	 */
	public function dom()
	{
		return $this->driver()->dom($this->id());
	}

	/**
	 * The current internal ID, unique to this page
	 * @return mixed
	 */
	public function id()
	{
		return $this->_id;
	}

	/**
	 * The html source of the current tag
	 * @return string
	 */
	public function html()
	{
		return $this->driver()->html($this->id());
	}

	public function screenshot($file)
	{
		$this->driver()->screenshot($file);
		return $this;
	}

	/**
	 * The html source of the current tag
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->html();
	}

	/**
	 * The tag name of the current tag (body, div, input)
	 * @return string
	 */
	public function tag_name()
	{
		return $this->driver()->tag_name($this->id());
	}

	/**
	 * Attribute of the current tag
	 * @param  string $name the name of the attribute
	 * @return string       
	 */
	public function attribute($name)
	{
		return $this->driver()->attribute($this->id(), $name);
	}

	/**
	 * The text content of the current tag (similar to javascript's innerText)
	 * @return string 
	 */
	public function text()
	{
		return $this->driver()->text($this->id());
	}

	/**
	 * Is this element visible?
	 * @return boolean
	 */
	public function is_visible()
	{
		return $this->driver()->is_visible($this->id());	
	}

	/**
	 * Is this option element selected?
	 * @return boolean 
	 */
	public function is_selected()
	{
		return $this->driver()->is_selected($this->id());
	}

	/**
	 * Is this checkbox checked?
	 * @return boolean
	 */
	public function is_checked()
	{
		return $this->driver()->is_checked($this->id());
	}

	/**
	 * Get the value of the current form field
	 * @return string 
	 */
	public function value()
	{
		return $this->driver()->value($this->id());
	}


	/**
	 * SETTERS
	 * ===========================================
	 */
	
	/**
	 * Set the value for the current form field
	 * @param mixed $value 
	 * @return Functest_Node $this
	 */
	public function set($value)
	{
		$this->driver()->set($this->id(), $value);
		return $this;
	}
	
	/**
	 * Append to the current value - useful for textarea / input fields
	 * @param  string $value 
	 * @return Functest_Node $this
	 */
	public function append($value)
	{
		$current_value = $this->driver()->value($this->id());

		$this->driver()->set($this->id(), $current_value.$value);
		
		return $this;
	}

	/**
	 * Click on the current html tag, either a button or a link
	 * @return Functest_Node $this
	 */
	public function click()
	{
		$this->driver()->click($this->id());
		return $this;
	}

	/**
	 * Select an option for the current select tag
	 * @return Functest_Node $this
	 */
	public function select_option()
	{
		$this->driver()->select_option($this->id(), TRUE);
		return $this;
	}

	/**
	 * Unselect an option for the current select tag
	 * @return Functest_Node $this
	 */
	public function unselect_option()
	{
		$this->driver()->select_option($this->id(), FALSE);
		return $this;
	}

	/**
	 * Hover over the current tag with the mouse
	 * @param  integer       $x offset inside the tag
	 * @param  integer       $y offset inside the tag
	 * @return Functest_Node $this
	 */
	public function hover($x = NULL, $y = NULL)
	{
		$this->driver()->move_to($this->id(), $x, $y);
		return $this;
	}

	/**
	 * Simulate drop file events on the current element
	 * @param  array|string $files local file filename or an array of filenames
	 * @return Functest_Node        $this
	 */
	public function drop_files($files)
	{
		$this->driver()->drop_files($this->id(), $files);
		return $this;
	}


	/**
	 * ACTIONS
	 * =======================================
	 */

	/**
	 * Hover the mouse over a specific tag child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters
	 * @return Functest_Node $this
	 */
	public function hover_on($selector, array $filters = array())
	{
		$this->find($selector, $filters)->hover();
		return $this;
	}

	/**
	 * Hover the mouse over a specific link child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function hover_link($selector, array $filters = array())
	{
		$this->find_link($selector, $filters)->hover();
		return $this;
	}

	/**
	 * Hover the mouse over a specific field child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function hover_field($selector, array $filters = array())
	{
		$this->find_field($selector, $filters)->hover();
		return $this;
	}

	/**
	 * Hover the mouse over a specific button child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function hover_button($selector, array $filters = array())
	{
		$this->find_button($selector, $filters)->hover();
		return $this;
	}

	/**
	 * Click on a specifc tag child of the current tag
	 * @param  string|array $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function click_on($selector, array $filters = array())
	{
		$this->find($selector, $filters)->click();
		return $this;
	}

	/**
	 * Click on a specifc link child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function click_link($selector, array $filters = array())
	{
		$this->find_link($selector, $filters)->click();
		return $this;
	}

	/**
	 * Click on a specifc button child of the current tag
	 * @param  string|array  $selector 
	 * @param  array         $filters 
	 * @return Functest_Node $this
	 */
	public function click_button($selector, array $filters = array())
	{
		$this->find_button($selector, $filters)->click();
		return $this;
	}

	/**
	 * Set the value of the specific form field inside the current tag
	 * @param  string|array  $selector 
	 * @param  mixed         $with     the value to be set
	 * @param  array         $filters  
	 * @return Functest_Node this
	 */
	public function fill_in($selector, $with, array $filters = array())
	{
		$field = $this->find_field($selector, $filters);

		if ( ! in_array($field->tag_name(), array('input', 'textarea')))
			throw new Exception('Element of type ":type" cannot be filled in! Only input and textarea elements can.');

		$field->set($with);
		
		return $this;
	}

	/**
	 * Choose a spesific radio tag inside the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function choose($selector, array $filters = array())
	{
		$this->find_field($selector, $filters)->set(TRUE);
		return $this;
	}

	/**
	 * Check a spesific checkbox input tag inside the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function check($selector, array $filters = array())
	{
		$this->find_field($selector, $filters)->set(TRUE);
		return $this;
	}

	/**
	 * Uncheck a spesific checkbox input tag inside the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function uncheck($selector, array $filters = array())
	{
		$this->find_field($selector, $filters)->set(FALSE);
		return $this;
	}

	/**
	 * Attach a file to a spesific file input tag inside the current tag
	 * @param  string|array   $selector 
	 * @param  string         $file      the filename for the file
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function attach_file($selector, $file, array $filters = array())
	{
		$this->find_field($selector, $filters)->set($file);
		return $this;
	}

	/**
	 * Select an option of a spesific select tag inside the current tag
	 * 
	 * To select the option the second parameter can be either a string of the option text
	 * or a filter to be applied on the options e.g. array('value' => 10)
	 * 
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @param  array|string   $option_filters  
	 * @return Functest_Node  $this
	 */
	public function select($selector, $option_filters, array $filters = array())
	{
		if ( ! is_array($option_filters))
		{
			$option_filters = array('text' => $option_filters);
		}

		$this
			->find_field($selector, $filters)
				->find('option', $option_filters)
					->select_option();

		return $this;
	}

	/**
	 * Unselect an option of a spesific select tag inside the current tag
	 * 
	 * To select the option the second parameter can be either a string of the option text
	 * or a filter to be applied on the options e.g. array('value' => 10)
	 * 
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @param  array|string   $option_filters  
	 * @return Functest_Node  $this
	 */
	public function unselect($selector, $option_filters, array $filters = array())
	{
		if ( ! is_array($option_filters))
		{
			$option_filters = array('value' => $option_filters);
		}

		$this
			->find_field($selector)
				->find('option', $option_filters)
					->unselect_option();
		return $this;
	}

	/**
	 * Confirm a javascript alert/confirm dialog box
	 * 
	 * @param  boolean|string $confirm alert/confirm - use boolean for inputs use string
	 * @return Functest_Node  $this
	 */
	public function confirm($confirm)
	{
		$this->driver()->confirm($confirm);
		return $this;
	}

	/**
	 * Execute arbitrary javascript on the page and get the result
	 * 
	 * @param  string $script 
	 * @return mixed         
	 */
	public function execute($script, $callback = NULL)
	{
		$result = $this->driver()->execute($this->id(), $script);
		if ($callback)
		{
			call_user_func($callback, $result, $this);
			return $this;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * FINDERS
	 * =====================================================
	 */
	
	/**
	 * Find an html form field child of the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function find_field($selector, array $filters = array())
	{
		return $this->find(array('field', $selector, $filters));
	}

	/**
	 * Find an html form field child of the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function find_link($selector, array $filters = array())
	{
		return $this->find(array('link', $selector, $filters));
	}

	/**
	 * Find an html button tag child of the current tag
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @return Functest_Node  $this
	 */
	public function find_button($selector, array $filters = array())
	{
		return $this->find(array('button', $selector, $filters));
	}

	/**
	 * Find an html tag child of the current tag
	 * This is the basic find method that is used by all the other finders. 
	 * To work with ajax requests it waits a bit (defualt 2 seconds) for the content to appear on the page
	 * before throwing an Functest_Exception_Notfound exception
	 * 
	 * @param  string|array   $selector 
	 * @param  array          $filters  
	 * @throws Functest_Exception_Notfound If element not found
	 * @return Functest_Node  $this
	 */
	public function find($selector, array $filters = array())
	{
		$locator = new Locator($selector, $filters);
		$self = $this;

		$node = Attempt::make(function() use ($self, $locator){
			return $self->all($locator)->first();
		}, $this->wait_time());
		
		$this->_next_wait_time = NULL;

		if ($node == NULL)
			throw new Exception_Notfound($locator, $this->driver());

		return $node;
	}

	/**
	 * Oposite to the find method()
	 * 
	 * @param  string|array  $selector 
	 * @param  array         $filters  
	 * @throws Functest_Exception_Found If element is found on the page
	 * @return Functest_Node $this
	 */
	public function not_present($selector, array $filters = array())
	{
		$locator = new Locator($selector, $filters);
		$self = $this;

		$not_found = Attempt::make(function() use ($self, $locator){
			return ! $self->all($locator)->first();
		}, $this->wait_time());

		$this->_next_wait_time = NULL;

		if ( ! $not_found)
			throw new Exception_Found($locator, $this->driver());

		return TRUE;
	}

	/**
	 * Returns the parent element
	 * 
	 * @return Functest_Node parent
	 */
	public function end()
	{
		return $this->_parent;
	}

	/**
	 * Find a list of elements represented by the selector / filter
	 * 
	 * @param  string|array $selector 
	 * @param  array        $filters  
	 * @return Functest_Nodelist
	 */
	public function all($selector, array $filters = array())
	{
		if ($selector instanceof Locator)
		{
			$locator = $selector;
		}
		else
		{
			$locator = new Locator($selector, $filters);
		}

		return new Nodelist($this->driver(), $locator, $this);
	}

	public function __call($method, $arguments)
	{
		if ( ! $this->extension() OR ! method_exists($this->extension(), $method))
			throw new Exception_Methodmissing('Method :method does not exist on this node or node extension', array(':method' => $method));

		array_unshift($arguments, $this);
		
		call_user_func_array(array($this->extension(), $method), $arguments);

		return $this;
	}
}
