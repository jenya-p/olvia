<?php

namespace Common\Traits;

interface ConfigAware{

	public function setConfig($config);
	
	public function getConfig($key = null);
    
    public function getAppConfig($key = null);
}