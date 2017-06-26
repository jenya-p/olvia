<?php

namespace Common\Traits;

use Zend\Log\LoggerInterface;

trait LoggerTrait{
	protected $logger;

	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
		return $this;
	}

	public function getLogger() {
		return $this->logger;
	}

	public function logInfo($message, $extra = []) {
		$this->logger->info($message, $extra);
	}
	
	public function logDebug($message, $extra = []) {
		$this->logger->debug($message, $extra);
	}
	
	public function logError($message, $extra = []) {
		$this->logger->err($message, $extra);
	}
	
	public function logWarn($message, $extra = []) {
		$this->logger->warn($message, $extra);
	}
	
	
	
}