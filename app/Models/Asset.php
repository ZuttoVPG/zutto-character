<?php

namespace App\Models;

class Asset
{
    protected $imageData;
    protected $imageMimeType;
    protected $imageFilesize;

    protected $xPos;
    protected $yPos;
    protected $zIndex;

    protected $valid = null;
    protected $errorBag = [];

    public function __construct($imageData, $mimeType, $fileSize, $x, $y, $z)
    {
        $this->imageData = $imageData;
        $this->imageMimeType = $mimeType;
        $this->imageFilesize = $fileSize; 

        $this->xPos = $x;
        $this->yPos = $y;
        $this->zIndex = $z;
    } // end __construct

    public function __call($name, $args)
    {
        $attr = lcfirst(preg_replace('/^get/', null, $name));

        return $this->$attr;
    } // end __call
} // end Asset
