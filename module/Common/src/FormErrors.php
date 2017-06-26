<?php 
namespace Common;

class FormErrors{
	
	var $errors = array();
	
	public function __set($name,$msg=null){
		if(!empty($msg)){
			$this->errors[$name] = $msg;
		} else {
			unset($this->errors[$name]);
		}
		
	}
	
	public function __get($name){
		if(array_key_exists($name, $this->errors)){
			return $this->errors[$name];
		} else {
			return null;
		}
	}
	
	public function hasErrors(){
		return count($this->errors) != 0;  
	}
		
		
	public function ifHasErrorClass($name, $class = ' has-error', $default = ''){
		if(array_key_exists($name, $this->errors)){
			return $class;
		} else {
			return $default;
		}
	}
	
	public function render($name, $extra=''){
		if(array_key_exists($name, $this->errors)){			
			return '<p class="error error-'.$name.'" '.$extra.' style="display: inherit;">'.$this->errors[$name].'</p>';
		} else {
			return '<p class="error error-'.$name.'" '.$extra.'></p>';			
		}		
	}
	
	public function renderAll(){
		$ret = '';
		foreach ($this->errors as $name => $error) {
			$ret .= '<p class="error error-'.$name.'">'.$name.': '.$error.'</p>';
		}
		return $ret;
	}
	
}