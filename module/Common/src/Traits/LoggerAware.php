<?php

namespace Common\Traits;

use Zend\Log\LoggerInterface;

interface LoggerAware{
	
	public function setLogger(LoggerInterface $logger);

	public function getLogger();

	public function logInfo($message, $extra = []);
	
	public function logDebug($message, $extra = []);
	
	public function logError($message, $extra = []);
	
	public function logWarn($message, $extra = []);
		
}