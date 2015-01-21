<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Perform a search

$rh = \CDT\RH::i();
$oInputModel = $rh->cdt_input_model;
$oUserController = $rh->cdt_user_controller;
$oSubmissionController = $rh->cdt_submission_controller;

// if no query, no results...
if (\is_null ($oInputModel->get ('q'))) {
	print '[]';
	exit;
}

// Weight factors
$W							= array ();
$W['author']				= 40;
$W['keyword']				= 20;
$W['title']					= 10;
$W['text']['h1']			= 9;
$W['text']['h2']			= 8;
$W['text']['h3']			= 7;
$W['text']['h4']			= 6;
$W['text']['strong']		= 5;
$W['text']['em']			= 3;
$W['text']['blockquote']	= 3;

// Usage factors; how much the weight changes by the more its used
$U							= array ();
$U['author']				= 1;
$U['keyword'] 				= .95;
$U['title'] 				= .85;
$U['text']['h1'] 			= .90;
$U['text']['h2'] 			= .91;
$U['text']['h3'] 			= .92;
$U['text']['h4'] 			= .93;
$U['text']['strong'] 		= .94;
$U['text']['em'] 			= .94;
$U['text']['blockquote'] 	= .95;

// Standard factors
$F['exact_match'] 			= 1.2;

// is there a cached db?
$file = DIR_DAT . '/search-keywords.txt';
if (!\is_file ($file) || \filemtime ($file) + KEY_CACHE > \date ('U')) {
	
	// Weighted factors and all keywords
	$globalWeights = array();
	$searchKeywords = array();

	// utility functions
	function addKeywords ($keywords, $weight, $factor, $user) {
		$keywordsA = \explode ("\n", \trim ($keywords));
		foreach ($keywordsA as $keywordB) {
			$keywordC = \explode (' ', \trim ($keywordB));
			foreach ($keywordC as $keyword) {
				addKeyword ($keyword, $weight, $factor, $user);
			}
		}
	}

	function addKeyword ($keyword, $weight, $factor, $user) {
		global $globalWeights, $searchKeywords;

		$keyword = \strtolower ($keyword);

		if (isSet ($globalWeights[$keyword])) {
			if (!\in_array ($user, $searchKeywords[$keyword]['users'])) {
				$searchKeywords[$keyword]['weight'] = $globalWeights[$keyword] * $weight;
				$searchKeywords[$keyword]['users'][] = $user;
				$globalWeights[$keyword] *= $factor;
			}
		} else {
			$searchKeywords[$keyword] = array ('weight' => $weight, 'users' => array ($user));
			$globalWeights[$keyword] = $factor;
		}
	}

	function getTags ($tag, $text) {
		$matches = array();
		\preg_match_all ("/<$tag>(.*?)<\/$tag>/", $text, $matches, PREG_PATTERN_ORDER);
    	return $matches[1];
	}

	// load all submission data
	$oUsers = $oUserController->getAll (null, function ($user) {
		return $user->countSubmission;
	});

	// catalogue the keywords
	foreach ($oUsers as $oUser) {
		$data = $oSubmissionController->get ($oUser->username, false);

		if (!isSet ($data->text)) {
			continue;
		}

		addKeywords ($oUser->firstName, $weights['author'], $useFactors['author'], $oUser->username);
		addKeywords ($oUser->surname, $weights['author'], $useFactors['author'], $oUser->username);

		addKeywords ($data->title, $weights['title'], $useFactors['title'], $oUser->username);

		$keywords = \explode (',', $data->keywords);
		foreach ($keywords as $keyword) {
			addKeywords ($words, $weights['keyword'], $useFactors['keyword'], $oUser->username);
		}

		$text = $oUserController->makeSubsts ($data->text, $oUser->username);
		$text = $oSubmissionController->markdownToHtml ($text);

		$tags = array('h1', 'h2', 'h3', 'h4', 'strong', 'em', 'blockquote');
		foreach ($tags as $tag) {
			$results = getTags ($tag, $text);
			foreach ($results as $result) {
				addKeywords (\trim (\strip_tags ($text)), $weights['text_' . $tag], $useFactors['text_' . $tag], $oUser->username);
			}
		}
	}

	@\file_put_contents ($file, \serialize ($searchKeywords));
}


// keyword database
$db = \unserialize (\file_get_contents ($file));
$dbK = \array_keys ($db);

// search database
$query = $oInputModel->get ('q');
$qWords = \preg_split ('/ /', $query, -1, PREG_SPLIT_NO_EMPTY);
$results = array();
foreach ($qWords as $qWord) {
	$ms = \preg_grep ('/' . $qWord .'/', $dbK);
	foreach ($ms as $m => $d) {
		$k = $dbK[$m];
		$results[$k] = $db[$k];
		if ($k === $qWord) {
			$results[$k]['weight'] *= $F['exact_match'];
		}
	}
}


// // load results                      
// $results = array();
// $query = $oInputModel->get ('q');
// $qWords = \explode (' ', $query);
// foreach ($qWords as $qWord) {
// 	$qWordL = \trim (\strtolower ($qWord));
// 	if (!empty ($qWordL)) {
// 		foreach ($db as $key => $row) {
// 			if (\strpos ($key, $qWord) !== false) {
// 				$row['qWord'] = $qWord;
// 				$row['key'] = $key;
// 				$results[] = $row;
// 			}
// 		}
// 	}
// }

// Get user weights
$combinedResults = array();
foreach ($results as $result) {
	foreach ($result['users'] as $user) {
		if (!isSet ($combinedResults[$user])) {
			$combinedResults[$user] = $result['weight'];
		} else {
			$combinedResults[$user] += $result['weight'];
		}
	}
}

\arsort ($combinedResults, SORT_NUMERIC);

// Collect the relevant submissions and return
$output = array();
foreach ($combinedResults as $username => $weight) {
	$oUser = $oUserController->get ($username);
	$temp = $oSubmissionController->get ($username, false);

	if (isSet ($temp->text)) {
		$output[] = \array_merge ($temp->toArray (), $oUser->toArray (), array('weight' => $weight));
	}
}

print \json_encode ($output);