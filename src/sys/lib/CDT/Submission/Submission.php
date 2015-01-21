<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

namespace CDT\Submission;

/**
 * A user's submission.
 * 
 * @author Martin Porcheron <martin@porcheron.uk>
 */
class Submission extends \CDT\BaseData {

	/**
	 * Take this submission and make substitutes for the keywords.
	 * 
	 * @param \CDT\User\Controller $oUserController User controller.
	 * @param \CDT\User\User $oUser User to make modifications for.
	 * @return Submission
	 */
	public function makeSubsts (\CDT\User\Controller $oUserController, \CDT\User\User $oUser) {
		foreach ($this as $key => $value) {
			 $this->$key = $oUserController->makeSubsts ($value, $oUser->username);
		}

		return $this;
	}

}