<?php
/**
 * Register all actions and filters for the plugin.
 */
class TM_Loader {
	 /**
	  * The array of actions registered with WordPress.
	  *
	  * @var array
	  */
	 protected $actions;
	 
	 /**
	  * The array of filters registered with WordPress.
	  *
	  * @var array
	  */
	 protected $filters;
	 
	 /**
	  * Initialize the collections used to maintain the actions and filters.
	  */
	 public function __construct() {
		 $this->actions = array();
		 $this->filters = array();
	 }
	 
	 /**
	  * Add a new action to the collection to be registered with WordPress.
	  *
	  * @param string $hook          The name of the WordPress action.
	  * @param object|string $component A reference to the instance of the object or the function name.
	  * @param string|null $callback      The name of the function definition on the $component.
	  * @param int    $priority      Optional. The priority. Default is 10.
	  * @param int    $accepted_args Optional. The number of arguments. Default is 1.
	  */
	 public function add_action($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
		 // Handle case where $component is a string function name
		 if (is_string($component) && $callback === null) {
			 $this->actions = $this->add($this->actions, $hook, null, $component, $priority, $accepted_args);
		 } else {
			 $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
		 }
	 }
	 
	 /**
	  * Add a new filter to the collection to be registered with WordPress.
	  *
	  * @param string $hook          The name of the WordPress filter.
	  * @param object|string $component A reference to the instance of the object or the function name.
	  * @param string|null $callback      The name of the function definition on the $component.
	  * @param int    $priority      Optional. The priority. Default is 10.
	  * @param int    $accepted_args Optional. The number of arguments. Default is 1.
	  */
	 public function add_filter($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
		 // Handle case where $component is a string function name
		 if (is_string($component) && $callback === null) {
			 $this->filters = $this->add($this->filters, $hook, null, $component, $priority, $accepted_args);
		 } else {
			 $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
		 }
	 }
	 
	 /**
	  * A utility function that is used to register the actions and hooks into a single
	  * collection.
	  *
	  * @param array  $hooks         The collection of hooks that is being registered.
	  * @param string $hook          The name of the WordPress filter that is being registered.
	  * @param object|null $component A reference to the instance of the object or null.
	  * @param string $callback      The name of the function definition on the $component.
	  * @param int    $priority      The priority at which the function should be fired.
	  * @param int    $accepted_args The number of arguments that should be passed to the $callback.
	  * @return array The collection of actions and filters registered with WordPress.
	  */
	 private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
		 $hooks[] = array(
			 'hook'          => $hook,
			 'component'     => $component,
			 'callback'      => $callback,
			 'priority'      => $priority,
			 'accepted_args' => $accepted_args
		 );
		 return $hooks;
	 }
	 
	 /**
	  * Register the filters and actions with WordPress.
	  */
	 public function run() {
		 foreach ($this->filters as $hook) {
			 if ($hook['component'] === null) {
				 add_filter($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
			 } else {
				 add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
			 }
		 }
		 
		 foreach ($this->actions as $hook) {
			 if ($hook['component'] === null) {
				 add_action($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
			 } else {
				 add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
			 }
		 }
	 }
 }