<?
namespace Common\Db;


use Admin\Model\CommentsDb;

interface Discussion {
	
	public function setCommentsDb(CommentsDb $table);
	
	public function setUserId($userId);
	
	public function discution($entityName);
	
	public function getComments($id);
	
	public function getCommentsInfo($id);
	
	public function addComment($item_id, $body);
	
}