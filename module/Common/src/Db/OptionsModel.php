<?

namespace Common\Db;

use Common\Form\Option;

interface OptionsModel {
	

	/**
	 * @return array
	 */
	public function options();
	
	/**
	 * @return string
	 */
	public function option($key);
}