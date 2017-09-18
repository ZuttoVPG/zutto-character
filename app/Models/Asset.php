<?php

namespace App\Models;

use GuzzleHttp\Client;

class Asset
{
    protected $imageData;
    protected $valid = null;
    protected $errorBag = [];
    protected $imageMimeType;
    protected $imageFilesize;

    public function __construct($imageData, $errors = [])
    {
        $data = $this->getRawImage($imageData);
        $this->imageData = $data['image']; 
        $this->imageMimeType = $data['mime']; 
        $this->imageFilesize = strlen($this->imageData);

        $this->errorBag = array_merge($this->errorBag, $errors);
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
            return [
                'image' => base64_decode($imageData['image'], true),
                'mime' => null,
            ];
        }

        if ($this->checkUrlWhitelist($imageData['image']) == false)
        {
            $this->errorBag[] = 'Asset could not be loaded from that domain due to security restrictions.';
            return false;
        } 

        // @TODO: http://docs.guzzlephp.org/en/stable/quickstart.html#concurrent-requests 
        $client = new Client(['timeout' => 1]);
        $response = $client->get($imageData['image']);

        if ($response->getStatusCode() != 200) {
            $this->errorBag[] = "Unable to load resource: " . $response->getReasonPhrase();
            return false;
        }

        return [
            'image' => $response->getBody()->getContents(), 
            'mime' => $response->getHeader('Content-Type')[0],
        ]; 
    } // end getRawImage

    protected function checkUrlWhitelist($url) 
    {
        // @TODO: make this configurable
        $whitelist = ['https://character-dev.zuttopets.com'];

        $url_parts = parse_url($url);

        $host = [$url_parts['scheme'], '://', $url_parts['host']];
        if (array_key_exists('port', $url_parts) == true) {
            $host = array_merge($host, [':', $url_parts['port']]);
        }
        $host = implode('', $host);

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

    public function __call($name, $args)
    {
        $attr = lcfirst(preg_replace('/^get/', null, $name));

        return $this->$attr;
    } // end __call
} // end Asset
