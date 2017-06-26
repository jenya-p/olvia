<?
namespace Common\Form;


class Filter {
	private $name;
	private $message;
	private $parameters;	
	private $parent;
	
	public function __construct(Element $parent, $name = null, $message = null, $parameters = null){
		$this->parent = $parent;
		$this->name($name);
		$this->message($message);
		$this->parameters($parameters);		
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
	
}