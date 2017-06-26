<?php
namespace Common\Logger;

use Zend\Log\Writer\Db;

class DbWriter extends Db {
	
	/**
	 * Transform event into column for the db table
	 *
	 * @param  array $event
	 * @return array
	 */
	protected function eventIntoColumn(array $event){
	
		if (empty($event)) {
			return array();
		}

		$data = array();
		foreach ($event as $name => $value) {
			if(!is_scalar($value)){
				if (empty($value)){
					$value = null;
				//} else if($value instanceof Exception) {
				//	$value = $value->getMessage()."\r\n\r\n".$value->getTraceAsString();
				//} else if (method_exists($value, 'toString')) {
				//	$value = $value->toString();
				} else if (is_array($value)) {				
					$value = json_encode($value, JSON_UNESCAPED_UNICODE);
				} else {
					$value = null;
				}
			}
			$data[$name] = $value;
		}
		return $data;
	}
}