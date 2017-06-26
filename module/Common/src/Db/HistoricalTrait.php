<?
namespace Common\Db;


use Admin\Model\HistoryReader;
use Admin\Model\HistoryWriter;

trait HistoricalTrait{

	/* @var HistoryReader */
	protected $historyReader = null;
	/* @var HistoryWriter */
	protected $historyWriter = null;

	protected $entityName = null;
	
	public function history($entityName){
		$this->entityName = $entityName;
	}
	
	/**
	 * @return HistoryReader
	 */
	public function getHistoryReader($id) {
		$this->historyReader->reset($this->entityName, $id);
		return $this->historyReader;
	}

	public function setHistoryReader(HistoryReader $historyReader) {
		$this->historyReader = $historyReader;
		return $this;
	}

	/**
	 * @return HistoryWriter
	 */
	public function getHistoryWriter(array $newValues = null, array $oldValues = null, $id = null) {
		$this->historyWriter->reset($this->entityName, $newValues, $oldValues, $id);
		return $this->historyWriter;
	}

	public function setHistoryWriter(HistoryWriter $historyWriter) {
		$this->historyWriter = $historyWriter;
		return $this;
	}
	
	
	
	
	
}