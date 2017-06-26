<?php

namespace Common\Traits;

trait ConfigTrait{
	protected $config;	

	public function setConfig($config){
		$this->config = $config;
	}
	
	public function getConfig($key = null){
    	if($key == null){
    		return $this->config;
    	} else {
    		return $this->config[$key];
    	}
    }
    
    public function getAppConfig($key = null){
    	if($key == null){
    		return $this->config['application'];
    	} else {
    		return $this->config['application'][$key];
    	}
    }
}