<?
namespace Application\Model\Orders;

use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;

class ConsultationDb extends Table implements Historical {
	
	use HistoricalTrait;
	
	protected $table = 'order_consultations';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('order_consultation');
	}
		
		
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		return $historyReader->getRecordsByDate();
	}
	
		
}
