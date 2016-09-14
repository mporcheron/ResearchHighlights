<?php

/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Update the wordcounts for each cohort.

\header('Content-type: application/json');

try {
    $cUser = I::RH_User();
    $mInput = I::RH_Model_Input();
    $mUsers = new \RH\Model\Users();

    $mUser = $cUser->login($mInput->username, $mInput->password, true);

    foreach($mInput->admin[0] as $key => $cohort) {
        $mUser = new \RH\Model\User();
        $mUser->cohort = $cohort;
        $mUser->username = $mInput->admin[1][$key];
        $mUser->firstName = $mInput->admin[2][$key];
        $mUser->surname = $mInput->admin[3][$key];
        $mUser->email = $mInput->admin[4][$key];
        $mUser->fundingStatementId = $mInput->admin[5][$key];
        $mUser->admin = true;

        $enabled = \trim($mInput->admin[6][$key]);
        $mUser->enabled = $enabled == 'true' ? true : ($enabled == 'false' ? false : -1);

        $countSubmission = \trim($mInput->admin[7][$key]);
        $mUser->countSubmission = $countSubmission == 'true' ? true : ($countSubmission == 'false' ? false : -1);

        $emailOnChange = \trim($mInput->admin[8][$key]);
        $mUser->emailOnChange = $emailOnChange == 'true' ? true : ($emailOnChange == 'false' ? false : -1);

        if (empty($mUser->cohort) || !is_numeric($mUser->cohort)) {
            throw new \RH\Error\InvalidInput('Cohort value "'. $cohort .'" is not numeric');
        } else if (empty($mUser->username)) {
            throw new \RH\Error\InvalidInput('Cannot have a blank username');
        } else if (empty($mUser->firstName)) {
            throw new \RH\Error\InvalidInput('Cannot have a blank first name for "'. $mUser->username .'"');
        } else if (empty($mUser->surname)) {
            throw new \RH\Error\InvalidInput('Cannot have a blank surname for "'. $mUser->username .'"');
        } else if (empty($mUser->email)) {
            throw new \RH\Error\InvalidInput('Cannot have a blank email address for "'. $mUser->username .'"');
        } else if (empty($mUser->fundingStatementId) || \is_null($cUser->getFundingById($mUser->fundingStatementId))) {
            throw new \RH\Error\InvalidInput('No funding statement "'. $fundingStatementId .'" - create this first.');
        } else if ($mUser->enabled === -1) {
            throw new \RH\Error\InvalidInput('Invalid value for Login Enabled for "'. $mUser->username .'" - must be true or false (value is "' . $emailOnChange . '")');
        } else if ($mUser->countSubmission === -1) {
            throw new \RH\Error\InvalidInput('Invalid value for Show Submission for "'. $mUser->username .'" - must be true  or false (value is "' . $countSubmission . '")');
        } else if ($mUser->emailOnChange === -1) {
            throw new \RH\Error\InvalidInput('Invalid value for Notify for "'. $mUser->username .'" - must be true or false (value is "' . $emailOnChange . '")');
        } else {
            $mUsers->__set($mUser->username, $mUser);
        }
    }

    print \json_encode(array ('success' => $cUser->updateUsers($mUsers, true) ? 1 : 0));
} catch (\RH\Error $e) {
    print $e->toJson();
}