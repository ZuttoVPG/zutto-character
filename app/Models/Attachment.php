<?php

namespace App\Models;

use App\Models\Asset;

class Attachment extends Asset
{
    protected $xPos;
    protected $yPos;
    protected $zIndex;

    public function __construct($imageData, $x, $y, $z, $errors = [])
    {
        parent::__construct($imageData, $errors);

        $this->xPos = $x;
        $this->yPos = $y;
        $this->zIndex = $z;
    } // end __construct
    
    public function isValid()
    {
        if ($this->valid !== null)
        {
            return $this->valid;
        }

        // Get initial state from the parent 
        $this->valid = parent::isValid();

        if ($this->getXPos() < 0) {
            $this->errorBag[] = 'x position is less than 0';
        }

        if ($this->getYPos() < 0) {
            $this->errorBag[] = 'y position is less than 0';
        }

        $this->valid = (sizeof($this->errorBag) == 0);
        return $this->valid;
    } // end isValid

} // end Attachment
