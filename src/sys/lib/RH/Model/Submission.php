<?php

/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

namespace RH\Model;

/**
 * A user's submission.
 *
 * @author Martin Porcheron <martin@porcheron.uk>
 */
class Submission extends AbstractModel
{

    /** @var Images List of Images in the Submission to save */
    private $images;

    /**
     * Construct the data object, with initial data values, if any.
     *
     * @param mixed[] $data Data to construct initial object with
     * @return \RH\Model\Submission
     */
    public function __construct($data = array ())
    {
        parent::__construct($data);
        $this->images = new Images();
    }

    /**
     * Take this submission and make substitutes for the keywords.
     *
     * @param \RH\Model\User $mUser User to make modifications for.
     * @return \RH\Model\Submission
     */
    public function makeSubsts(\RH\Model\User $mUser)
    {
        foreach ($this as $key => $value) {
             $this->$key = $mUser->makeSubsts($value);
        }

        return $this;
    }

    /**
     * Add an image to be saved to disk.
     *
     * @param string $filename name of the image
     * @param string $url Image URL
     * @return void
     */
    public function addImage($filename, $url)
    {
        $image = new Image(array ('filename' => $filename, 'url' => $url));
        $this->images[] = $image;
    }

    /**
     * Save this submission to the file system.
     *
     * @return true
     * @throws \RH\Error\System if something went wrong
     */
    public function save()
    {
        $ext = \RH\Submission::DAT_FILE_SUF;
        $version = date('U');

        $dir = DIR_DAT . '/' . $this->cohort;
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0775, true) === false) {
                throw new \RH\Error\SystemError('Could not create cohort data directory');
            }
            @\chmod($dir, 0775);
        }

        $dir = $dir . '/' . $this->username;
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0775, true) === false) {
                throw new \RH\Error\SystemError('Could not create user data directory');
            }
            @\chmod($dir, 0775);
        }

        $dir = $dir  . '/' . $version .'/';
        if (@mkdir($dir, 0775, true) === false) {
            throw new \RH\Error\SystemError('Could not create submission data directory');
        }
        @\chmod($dir, 0775);

        foreach ($this->images as $image) {
            if (copy($image->url, $dir . $image->filename) === false) {
                throw new \RH\Error\SystemError('Could not save image ' . $image->url . ' to the system');
            }
            @\chmod($dir . $image->filename, 0775);
        }

        foreach ($this as $key => $value) {
            if (@\file_put_contents($dir . $key . $ext, $value) === false) {
                throw new \RH\Error\SystemError('Could not save ' . $key . ' to the system');
            }
            @\chmod($dir . $key . $ext, 0775);
        }

        if (CACHE_CLEAR_ON_SUBMIT) {
            $cUser = \I::RH_User();
            $cUser->getAll()->clearCache();
            $cUser->get($this->username)->clearCache();
        }

        return true;
    }

    /**
     * Fetch the model for the keywords.
     *
     * @return \RH\Model\Keywords
     */
    public function getKeywords()
    {
        $keywords = new \RH\Model\Keywords();
        $keywords->fromString($this->keywords, ',');
        return $keywords;
    }
}
