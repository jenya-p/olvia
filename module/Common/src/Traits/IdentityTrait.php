<?php

namespace Common\Traits;

use Common\Identity;

trait IdentityTrait{
	private $identity;	

	
	/**
	 * @param Identity $identity
	 */
	public function setIdentity($identity){
		$this->identity = $identity;
	}
	
	
	/**
	 * @return Identity
	 */
	public function identity(){
		return $this->identity;
	}
	
}