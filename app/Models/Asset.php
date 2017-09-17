<?php

namespace App\Models;

class Asset
{
    protected $imageData;
    protected $valid = null;
    protected $errorBag = [];
    protected $imageMimeType;
    protected $imageFilesize;

    public function __construct($imageData, $errors = [])
    {
        $this->imageData = $this->getRawImage($imageData);
        $this->errorBag = array_merge($this->errorBag, $errors);

        $this->imageMimeType = finfo_buffer($this->imageData);
        $this->imageFilesize = strlen($this->imageData);
    } // end __construct

    protected function checkAssetSchema($imageData)
    {
        $errors = [];

        if (array_key_exists('type', $imageData) == false || in_array($imageData['type'], ['blob', 'url']) == false)
        { 
            $this->errorBag[] = 'Invalid Asset schema. Valid options for "type" key are "blob" and "url".';
        }

        if (array_key_exists('image', $imageData) == false) {
            $this->errorBag[] = 'Invalid Asset schema. "image" key must be present.';
        }

        return (sizeof($this->errorBag) == 0);
    } // end checkAssetSchema 

    protected function getRawImage($imageData) 
    {
        if ($this->checkAssetSchema($imageData) == false)
        {
            return false;
        }

        if ($imageData['type'] == 'blob') {
            return base64_decode($imageData['image'], true);
        }

        if ($this->checkUrlWhitelist($imageData['image']) == false)
        {
            $this->errorBag[] = 'Asset could not be loaded from that domain due to security restrictions.';
            return false;
        } 
        
        // @TODO: the error handling on this probably sucks, change it to curl
        return file_get_contents($imageData['image']);
    } // end getRawImage

    protected function checkUrlWhitelist($url) 
    {
        // @TODO: make this configurable
        $whitelist = ['http://zuttopets.com:8000'];
        
        $url_parts = parse_url($url);
        $host = "${url_parts['schema']}://${url_parts['host']}:${url_parts['port']}";

        return in_array($host, $whitelist);
    } // end checkUrlWhitelist
    
    public function isValid()
    {
        if ($this->valid !== null)
        {
            return $this->valid;
        }

        $this->valid = true;
        if (strlen($this->imageData) <= 0) {
            $this->errorBag[] = 'Image data missing';
            $this->valid = false;
        }

        return $this->valid;
    } // end isValid
} // end Asset
