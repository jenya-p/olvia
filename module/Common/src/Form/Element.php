<?
namespace Common\Form;


use Common\Db\OptionsModel;

/**
 * @method Element name($name);
 * @method Element type($type);
 * @method Element id($id);
 * @method Element value($value);
 * @method Element error($errors); 
 * @method Element label($label);
 * @method Element description($description); 
 * @method Form parent($parent);
 * @method Element extra(array $extra);
 * @method Element disabled(bool $disabled);
 * @method Element decorator($decorator);
 * @method Element validators(array $validators);  
 */

class Element {
	private $name;
	private $type;
	private $id = null;
	private $error;
	private $label;
	private $description;
	private $options;
	private $extra;
	private $decorator;
	private $disabled = false;
	private $class;
	private $parent;
	
	private $validators;
	
	public function __construct(Form $parent, $name, $type=null, $label=null, $extra=null){
		$this->parent = $parent;
		$this->name($name);
		$this->type($type);
		$this->label($label);
		if(is_string($extra)){
			$this->extra($extra);
		} else if(is_array($extra)){
			foreach ($extra as $property => $value){
				if(in_array($property, ['error','description','extra','decorator','class','options','label'])){					
					$this->$property($value);
				} else if($property == 'disabled'){
					$this->disabled(true);
				} else if (!empty($value)){					
					$this->extra[$property] = $value;
				}
			}
		}
		
		if(empty($this->decorator) && !empty($parent->defaultElementDecorator)){
			$this->decorator($parent->defaultElementDecorator);
		}
	}
	
	public function __call($name , array $arguments){
		if(property_exists($this, $name)){
			if(count($arguments) == 0){
				return $this->$name;
			} else if(count($arguments) == 1){
				$this->$name = $arguments[0];
				return $this;
			}
		} else {
			if(count($arguments) == 0){
				return $this->extra[$name];
			} else if(count($arguments) == 1){
				$this->extra[$name] = $arguments[0];
				return $this;
			}
		}
	}
		
	/**
	 * @return Option
	 */
	public function option($key, $label = null, $extra = null){
		if(!array_key_exists($key, $this->options)){
			if($label !== null){
				$this->options[$key] = new Option($this,$key,$label,$extra);
			} else {
				$optionSource = $this->extraParam('optionSource', null);
				if($optionSource instanceof OptionsModel){			
					$this->options[$key] = new Option($this, $key, $optionSource->option($key));
				} else if(is_callable($optionSource)){
					$this->options[$key] = new Option($this, $key, call_user_func($optionSource, $key));
				}
			}
		} 
		return $this->options[$key];
	}
	
	/**
	 * @param array|OptionsModel|callable $options
	 * @return \Common\Form\Element
	 */
	public function options($options = null){		
		if($options !== null){
			if($options instanceof OptionsModel || is_callable($options)){
				$this->addExtra('optionSource',$options);
				$this->options = null;
			} else if(is_array($options)){
				foreach ($options as $optionKey => $optionLabel){
					$this->option($optionKey, $optionLabel);
				}
			} else {
				throw new \Exception('option must be array, OptionsModel or callable');
			}
			return $this;
		} else {
			if($this->options === null){
				$optionSource = $this->extraParam('optionSource');				
				if($optionSource instanceof OptionsModel){
					$options = $optionSource->options();
				} else if(is_callable($optionSource)){
					$options = call_user_func($optionSource);
				}
				foreach ($options as $optionKey => $optionLabel){
					$this->option($optionKey, $optionLabel);
				}
			}
				
			return $this->options;
		} 
	}
	
	public function addClass($class){
		if(empty($this->class)){
			$this->class = $class;
		} else if(!preg_match('/(\s|^)'.$class.'(\s|$)/', $this->class)){
			$this->class .= ' '.$class;
		}
		return $this;
	}
	
	public function disable(){
		$this->disabled(true);
		return $this;
	}
	
	public function enable(){
		$this->disabled(false);
		return $this;
	}
	
	public function value(){		
		if(func_num_args() == 0){
			return $this->parent->value($this->name);
		} else {
			$value = func_get_arg(0);
			$this->parent->value($this->name, $value);
			return $this;
		}
	}
	
	public function error($error = null){
		if($error === null){
			return $this->parent->error($this->name);
		} else {
			$this->parent->error($this->name, $error);
			return $this;
		}
	}
	
	public function hasError(){
		return !empty($this->parent->error($this->name));
	}
	

	function id($id = null){
		if($id === null){
			if(empty($this->id)){
				$this->id = preg_replace("/[^A-Za-z0-9_]/", '', $this->name);
			}	
			return $this->id;
		} else {
			$this->id = $id;
			return $this;
		}		
	}
	
	function extraString(){	
		if(is_string($this->extra)){
			$ret = $this->extra;
		} else if(is_array($this->extra) && array_key_exists('attributes', $this->extra)){
			$ret = $this->extra['attributes'];
		} else {
			$ret = '';
		}
		
		if($this->disabled){
			$ret = 'disabled="disabled" '.$ret;
		}
		if(!empty($this->class)){
			$ret = 'class="'.$this->class.'"' .$ret;
		}
		if(strpos($ret, 'id="') === false){
			$ret = 'id="'.$this->id().'" '.$ret;
		}
		if(!empty($ret)){
			$ret = ' '.$ret;
		}
		return $ret;
	}
	
	public function extraParam($name, $defaulf = null){
		if(is_array($this->extra) && array_key_exists($name, $this->extra)){
			return $this->extra[$name];
		} else {
			return $defaulf;
		}
	}
	
	public function addExtra($name, $value){
		$this->extra[$name] = $value;
		return $this;
	}
	
	
	public function __toString(){
		$parent = $this->parent();
		try{
			return $parent($this->name())->__toString();
		} catch (\Exception $e){
			return '<div style="color:red; padding:10px;">'.$e->getMessage().'</div>';
		}	
	}
	
	public function validator(){
		$parameters = func_get_args();
		if(count($parameters)>0){
			$name = array_shift($parameters);
			$message = array_shift($parameters);
			$this->validators[] = new Validator($this, $name, $message, $parameters);
		}
		return $this;
	}
	
	public function fieldset($name){
		$this->parent()->fieldset($name, [$this->name]);
		return $this;
	}
	
	
}