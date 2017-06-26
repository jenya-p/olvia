<?
namespace Common\Form;

use Zend\Stdlib\ArrayUtils;
use Common\Utils;

/**
 * @method Form action($action);
 * @method Form method($method);
 * @method Form class($class);
 */
class Form{
	
	use BaseElements, BaseValidators;
	
	private $fields 	 = [];
	
	private $values 	 = [];
	private $errors 	 = [];
	
	private $defaultElementDecorator = null;

	private $_rendered = [];	
	private $_current;
	private $_to;
	private $_except;
	
	
	private $action = "";
	private $method = "post";
	private $class = "form";
	private $id = "itemForm";
	
	/**
	 * @return \Common\Form\Form
	 */
	public function __invoke($fieldName = null, $to = null, $except = null){
		if($fieldName !== null){
			if($fieldName === '*' ){
				$this->_current = null;
			} else if(array_key_exists($fieldName, $this->fields)){
				$this->_current = $fieldName;
			} else {
				throw new \Exception("field $fieldName not on the form (1)");
			}
		} else if (empty($this->_current)){
			$this->_current = null;
		}
		
		if($to === null){
			if($fieldName === '*' ){
				$this->_to = null;
			} else {				
				$this->_to = $this->nextField($this->_current);
			}
		} else {
			$this->to($to);
		}
		$this->except($except);
		return $this;
	}	
	
	
	private function nextField($current){
		$names = array_keys($this->fields);
		$index = array_search($current, $names);
		if($index < count($names)-1){
			return $names[$index + 1];
		} else {
			return null;
		}
	}
	
	public function to($fieldName = '*', $inclusive = false){
		if($fieldName == '*'){
			$this->_to = null;
		} else if(array_key_exists($fieldName, $this->fields)){
			if($inclusive){
				$this->_to = $this->nextField($fieldName);
			} else {
				$this->_to = $fieldName;
			}			
		} else {
			throw new \Exception("field $fieldName not on the form");
		}
		return $this;
	}
	
	public function except($except){
		if(empty($except)){
			$this->_except = [];
		} else {
			if(is_string($except)){
				$except = [$except];
			}
			foreach ($except as $fieldName){
				if(!array_key_exists($fieldName, $this->fields)){
					\Exception("field $fieldName not on the form");
				}
			}
			$this->_except = $except;
		}
		
		return $this;
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
	
	public function addClass($class){
		if(empty($this->class)){
			$this->class = $class;
		} else if(!preg_match('/(\s|^)'.$class.'(\s|$)/', $this->class)){
			$this->class .= ' '.$class;
		}
		return $this;
	}
	
	

	
	/**
	 * @return Element
	 */
	public function field($name, $type=null, $label=null, $extra=null){
		if(!array_key_exists($name, $this->fields)){			
			$this->fields[$name] = new Element($this, $name, $type, $label, $extra);
		}		
		return $this->fields[$name];
	}
	
	public function hasField($name){
		return array_key_exists($name, $this->fields);
	}
	
	
	
	public function __get($name){		
		return $this->field($name);
	}
	
	public function values($values = null){
		if($values === null){

			return $this->values;
			
		} else if(ArrayUtils::isHashTable($this->values, true)){
			$this->values = ArrayUtils::merge($this->values, $values);
			return $this;
			
		} else {
			throw new \Exception("Values should be an hash table");
		}
	}
	
	/**
	 * @param string $fieldName
	 * @param mixed $value
	 * @return mixed|\Common\Form\Form
	 */
	public function value($fieldName){
		if(func_num_args() == 1){
			return $this->values[$fieldName];
		} else {
			$value = func_get_arg(1);
			$this->values[$fieldName] = $value;
			return $this;
		}
	}
	
	public function validate($data){
		/* @var $field Element */
		foreach ($this->fields as $field){			
			$parseMethodName = 'parse'.Utils::camelize($field->type());
			if(!method_exists($this, $parseMethodName)){
				$parseMethodName = 'parseDefault';
			}			
			$this->$parseMethodName($field, $data);
				
			$this->processFieldValidators($field);
		}
	}
	
	private function processFieldValidators(Element $field){
		$fieldValidators = $field->validators();
		if(!empty($fieldValidators)){
			/* @var $validator Validator */
			foreach ($fieldValidators as $validator){
				$this->processValidator($field, $validator);
				if($field->hasError()) return;
			}
		}
	}
	
	private function processValidator(Element $element, Validator $validator){
		$name = $validator->name();
		
		$methodName = 'validator'.Utils::camelize($name);
		
		if(!method_exists($this, $methodName)){
			throw new \Exception('validator "'.$name.'" not defined');
		}
		
		$params = $validator->parameters();
		if(empty($params)){
			$params = [];
		}
		array_unshift($params, $validator->message());
		array_unshift($params, $element);
		
		call_user_func_array([$this, $methodName], $params);		
	}
		
	public function errors($errors = null){
		if($errors == null){
			return $this->errors;
		} else {
			$this->errors = $errors;
			return $this;
		}
	}
	
	public function error($fieldName, $error = null){
		if($error == null){
			return $this->errors[$fieldName];
		} else {
			$this->errors[$fieldName] = $error;
			return $this;
		}
	}
		
	
	public function hasErrors(){
		return !empty($this->errors);
	}
	
	public function isNew(){
		// TODO
		return false;
	}
	
	public function __toString(){
			
		$names = array_keys($this->fields);
		if(empty($names)) return '';
		
		$ret = '';
		
		if($this->_current === null){
			$index = 0;					
		} else {
			$index = array_search($this->_current, $names);
		}

		for(;;){
			if($index >= count($names)){
				$this->_current = null;
				break;
			}
			$this->_current = $names[$index];
			
			$index++;
			if(null !== $this->_to && $this->_to == $this->_current){
				break;
			}
			
			if(array_search($this->_current, $this->_except) !== false || array_search($this->_current, $this->_rendered) !== false){
				continue;
			}
			
			try{
				$ret .= "\n".$this->renderField($this->fields[$this->_current]);
			} catch (\Throwable $e){
				$ret .= '<div style="color:red; padding:10px;">'.$e->getMessage()./*' at <br />'.$e->getTraceAsString().*/'</div>';
			}			
			
			$this->_rendered[] = $this->_current;
		}
		
		return $ret;		
	}

	
	private function renderField(Element $element){
		$decorator = $element->decorator();
		
		if(empty($decorator)){
			$decorator = $this->defaultElementDecorator;
		}
		$methodName = 'decorator'.Utils::camelize($decorator);
		
		if(!method_exists($this, $methodName)){
			throw new \Exception('decorator "'.$decorator.'" not defined');
		}		
		$ret = $this->$methodName($element);
		
		return $ret;
	}
		
	public function start($extra = ''){
		return '<form method="'.$this->method.'" class="'.$this->class.'" id="'.$this->id.'" autocomplete="off" action="'.$this->action.'" '.$extra.'>';
	}
	
	public function end(){
		return '</form>';
	}
	
}