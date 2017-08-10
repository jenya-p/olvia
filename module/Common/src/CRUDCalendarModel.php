<?php 

namespace Common;


interface CRUDCalendarModel{
	/**
	 * @param array|null $filter
	 * @return array
	 */
	public function getCalendarBounds($filter);
	/**
	* @param array|null $filter
	* @param int $from 
	* @param int $to
	* @return array
	*/
	public function getCalendarItems($filter, $from, $to);
	
	
}