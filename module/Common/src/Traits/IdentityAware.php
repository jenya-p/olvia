<?php

namespace Common\Traits;

use Common\Identity;

interface IdentityAware{

	/**
	 * 
	 * @param Identity $identity
	 */
	public function setIdentity($identity);
	
	/**
	 * @return Identity
	 */
	public function identity();
	
	
}