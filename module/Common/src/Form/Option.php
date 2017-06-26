<?
namespace Common\Form;


/**
 * @method Element id($id);
 * @method Element value($value);
 * @method Element label($label);
 * @method Element extra($extra);
 * @method Element disabled($disabled);
 * @method Element class($decorator); 
 */

class Option {
	private $id;
	private $value;
	private $label;
	private $extra;
	private $disabled = false;
	private $class;
	private $parent;
	
	public function __construct(Element $parent, $value = null, $label = null, $extra = null){
		$this->parent = $parent;
		$this->value($value);
		if(is_array($label) && $extra === null && isset($label['label'])){
			$this->label($label['label']);
			unset($label['label']);
			$extra = $label;
		} else {
			$this->label($label);
		}		
		if(is_string($extra)){
			$this->extra($extra);
		} else if(is_array($extra)){
			if(array_key_exists('disabled', $extra) && $extra['disabled']){
				$this->disabled(true);
			}
			foreach (['extra', 'class'] as $property){
				if(isset($extra[$property])){
					$this->$property($extra[$property]);
					unset($extra[$property]);					
				}
			}
			if(!empty($extra)){
				$this->extra($extra);
			}
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
		}
	}

	public function addClass($class){
		if(empty($this->class)){
			$this->class = $class;
		} else if(!preg_match('/(\s|^)'.$class.'(\s|$)/', $this->class)){
			$this->class = ' '.$class;
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
	
	function id($id = null){
		if($id === null){
			if($this->id === null){
				$name = $this->parent->name().'_'.$this->value;
				$this->id = preg_replace("/[^A-Za-z0-9_]/", '', $name);
			}
			return $this->id;
		} else {
			$this->id = $id;
			return $this;
		}
	}
	
	
	public function extraString(){
		$ret = (string)$this->extra;
	
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
	
}