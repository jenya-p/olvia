<?

namespace Admin\Model\Users;


trait UserHistoryTrait {
	
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
    
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    
    	if($historyWriter->hasUpdated('password')){
    		$historyWriter->write('password');
    	}
    
    	$oldRoles = $oldValues['roles'];
    	$newRoles = $newValues['roles'];
    	foreach ($newRoles as $newRole){
    		if(!in_array($newRole, $oldRoles)){
    			$historyWriter->write('add_role', $newRole);
    		}
    	}
    
    	foreach ($oldRoles as $oldRole){
    		if(!in_array($oldRole, $newRoles)){
    			$historyWriter->write('remove_role', null ,$oldRole);
    		}
    	}
    
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader();
    	$historyReader->reset('user', $id);
    
    	$historyReader->addDictionary('add_role', $this->getRoleOptions());
    	$historyReader->addDictionary('remove_role', $this->getRoleOptions());
    
    	return $historyReader->getRecordsByDate();
    }
    
}