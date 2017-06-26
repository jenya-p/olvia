<?
namespace Common\Db;

interface Multilingual {

	public function lang($lang = null);

	public function langFields($langFields = null);

	public function abstractLanguage(array &$item);
	
	public function concretLanguage(array &$item);

}