<?php

namespace App\Traits;

trait HasMatches
{

    abstract public function matches();

    /**
     * Retrieves the date of the wrestler's first match.
     *
     * @return string
     */
    public function firstMatchDate()
    {
        return $this->pastMatches()->first()->date;
    }

    /**
     * Returns the wrestler's past matches.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pastMatches()
    {
        return $this->matches->filter->isPast();
    }

    /**
     * Checks to see if the wrestler has past matches.
     *
     * @return boolean
     */
    public function hasPastMatches()
    {
        return $this->pastMatches()->isNotEmpty();
    }
}
