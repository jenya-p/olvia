<?php 

namespace Common;


interface CRUDListModel{
	/**
	 * @param array|null $filter
	 * @return array
	 */
	public function getTotals($filter);
	/**
	* @param array|null $filter
	* @param int $p
	* @param int $ipp
	* @return array
	*/
	public function getItems($filter,  $p, $ipp);
	
	
}