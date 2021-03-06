<?php

/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

namespace RH\Model;

/**
 * A list of images.
 *
 * @author Martin Porcheron <martin@porcheron.uk>
 */
class Images extends AbstractModel
{

    /**
     * Create a new image within this list.
     *
     * @param mixed $value Value of the image data.
     * @return \RH\Model\Image New Image object.
     */
    protected function newChild($value)
    {
        return new Image($value);
    }
}
