<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Fetch all submissions, or a single submission for reading

$rh = \CDT\RH::i();
$oSubmissionController = $rh->cdt_submission_controller;
$oInputModel = $rh->cdt_input_model;
$oUserController = $rh->cdt_user_controller;

// Get the users for which we want to return their submission
if(!is_null ($oInputModel->get('user'))) {
	$oUsers = array ($oUserController->get ($oInputModel->get ('user')));
} else if(!is_null ($oInputModel->get('cohort'))) {
	$cohort = $oInputModel->get('cohort');
	$oUsers = $oUserController->getAll (null, function ($user) use ($cohort) {
		return $user->countSubmission && $user->cohort === $cohort;
	});
} else if(!is_null ($oInputModel->get('keywords'))) {
	
	// is there a saved copy of all keywords?
	$keywords = @\explode (',', $oInputModel->get('keywords'));
	foreach($keywords as $keyword) {
		$keywords[] = \str_replace ('_', ' ', $keyword);
	}

	$allKeywords = $oSubmissionController->getKeywords ()->toArray();
	$oUsers = array();

	foreach ($keywords as $keyword) {
		if(!empty ($keyword) && isSet ($allKeywords[$keyword])) {
			foreach ($allKeywords[$keyword]['users'] as $k => $user) {
				$oUsers[$user] = $oUserController->get ($user);
			}
		}
	}
	$oUsers = \array_values ($oUsers);
} else {
	$oUsers = $oUserController->getAll (null, function ($user) {
		return $user->countSubmission;
	});
}

// Format the submission for output
$output = array();
foreach ($oUsers as $oUser) {
	$temp = $oSubmissionController->get ($oUser->username, false);

	if (isSet ($temp->text)) {
		$userData =  $oUserController->get ($oUser->username);
		
		$temp->text = $oUserController->makeSubsts ($temp->text, $oUser->username);
	
		$textMd = $temp->text;
		$textHtml = !empty ($textMd) ? $oSubmissionController->markdownToHtml ($textMd) : '<em>No text submitted.</em>';

		$refMd = \trim ($temp->references);
		$refHtml = !empty ($textMd) && !empty ($refMd) ?  '<h1>References</h1>' . $oSubmissionController->markdownToHtml ($refMd) : '';
		
		$pubMd = \trim ($temp->publications);
		$pubHtml = !empty ($pubMd) ? '<h1>Publications in the Last Year</h1>' . $oSubmissionController->markdownToHtml ($pubMd) : '';

		$temp->html = $textHtml . $refHtml . $pubHtml;
		$temp->fundingStatement = $oUserController->getFunding ($oUser->username);

		$output[] = \array_merge ($temp->toArray (), $userData->toArray ());
	}
}

print \json_encode ($output);
exit;