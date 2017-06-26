<?
namespace Common\Db;

use Admin\Model\HistoryWriter;
use Admin\Model\HistoryReader;

interface Historical {
	
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null);
	
	public function readHistory($id);
	
	public function history($entityName);
	
	/**
	 * @return \Common\Db\HistoricalyReader
	 */
	public function getHistoryReader($id);
	public function setHistoryReader(HistoryReader $historyReader);
	
	/**
	 * @return \Common\Db\HistoryWriter
	 */
	public function getHistoryWriter(array $newValues = null, array $oldValues = null, $id = null);	
	public function setHistoryWriter(HistoryWriter $historyWriter); 
	
	
	
}