<?php
namespace Common\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="phone")
 * */
class Phone extends \Zend\View\Helper\AbstractHelper{
	public function __invoke($phone){
		return 	self::format($phone);
	}
		
	static public function normalize($phone){
		$p = preg_replace('~\D+~','', $phone);
		if(strlen($p)!=11) $p = null;
		return $p;
	}
	
	static public function format($phone, $default = ''){
		if(empty($phone)) return $default;
		return '+7 ('.substr($phone, 1, 3). ')' .
				' '.substr($phone, 4, 3).
				'-'.substr($phone, 7, 2).
				'-'.substr($phone, 9, 2);
	}
	
	static public function valid($phone){
		return preg_match("/^\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d$/", $phone);
	}	
	
}