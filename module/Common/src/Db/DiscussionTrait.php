<?
namespace Common\Db;


use Admin\Model\CommentsDb;

trait DiscussionTrait{

	/* @var CommentsDb */
	protected $commentsDb = null;
	
	protected $userId = null;
	
	protected $entityName = null;
	
	public function setCommentsDb(CommentsDb $commentsDb){
		$this->commentsDb = $commentsDb; 
	}

	public function setUserId($userId){
		$this->userId = $userId;
	}
	
	
	public function discution($entityName){
		$this->entityName = $entityName;
	}
	
	public function getComments($id){
		return $this->commentsDb->getCommments($this->entityName, $id);
	}
	
	public function getCommentsInfo($id){
		return $this->commentsDb->getInfo($this->entityName, $id);
	}
	
	public function addComment($item_id, $body){
		$insert = [
				'date' => time(),
				'user_id' => $this->userId,
				'entity' => $this->entityName, 
				'item_id' => $item_id, 
				'body'    => $body
		];
		return $this->commentsDb->insert($insert);
	}
	
	
	
	
	
}