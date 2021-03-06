<?php

/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

namespace RH\Model;

/**
 * List of deadlines.
 *
 * @author Martin Porcheron <martin@porcheron.uk>
 */
class Deadlines extends AbstractModel
{

    /**
     * Create a new deadline within this list.
     *
     * @param mixed $value Value of the data data
     * @return \RH\Model\Deadline New Deadline object.
     */
    protected function newChild($value)
    {
        return new Deadline($value);
    }
}
