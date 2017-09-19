<?php

namespace App\Services;

use League\Uri\Schemes\Http;
use League\Uri\Schemes\UriException;

class RequestValidatorService
{
    protected $input;
    protected $valid = null;
    protected $errorBag = [];
    protected $urlWhitelist = null;
    protected $maxAttachments = null;

    public function __construct($request, $max_attachments, $url_whitelist)
    {
        $this->input = $request;
        $this->maxAttachments = $max_attachments;

        // Let people have spaces, I guess. Be liberal in what you accept and all that.
        if (trim($url_whitelist) != null) {
            $this->urlWhitelist = array_map(function ($url) {
                return trim($url);
            }, explode(',', $url_whitelist)); 
        }
    } // end request

    public function isValid()
    {
        if ($this->valid === null) {
            $this->valid = $this->validate();
        }

        return $this->valid;
    } // end isValid

    protected function validationRunner($field_prefix, $input, $tree)
    {
        foreach($tree as $field => $validator) {
            $field_key = "$field_prefix.$field";

            if (array_key_exists($field, $input) == false) {
                $this->addError($field_key, "expected key was not found");
            } else {
                if ($validator != null) {
                    $this->$validator($field_key, $input[$field]);
                }
            }
        }
    } // end validationRunner

    protected function validate()
    {
        $this->validationRunner('', $this->input, [
            'client' => 'validateClient',
            'baseImage' => 'validateAsset',
            'attachments' => 'validateAttachments',
        ]);

        return sizeof($this->getErrors()) == 0;
    } // end validate

    protected function validateClient($field, $input)
    {
        $this->validationRunner($field, $input, [
            'userAgent' => 'validateNotEmpty', 
            'viewport' => 'validateNotEmpty', 
            'scale' => 'validateNotEmpty', 
        ]);
    } // end validateClient

    protected function validateAsset($field, $input)
    {
        $this->validationRunner($field, $input, [
            'type' => 'validateNotEmpty', 
            'image' => 'validateNotEmpty', 
        ]);

        if (in_array($input['type'], ['blob', 'url']) == false) {
            $this->addError("$field.$type", 'bad value. valid options blob, url');
        }

        if ($input['type'] == 'url') {
            $this->validateUrlWhitelisted("$field.image", $input['image']);
        }
    } // end validateAsset

    protected function validateUrlWhitelisted($field, $input)
    {
        try {
            $uri = Http::createFromString($input);
        } catch (League\Uri\Schemes\UriException $e) {
            $this->addError($field, 'Invalid URL');
            return;
        }
        
        if ($uri->getPort() == null) {
            $host = vsprintf('%s://%s', [$uri->getScheme(), $uri->getHost()]);
        } else {
            $host = vsprintf('%s://%s:%s', [$uri->getScheme(), $uri->getHost(), $uri->getPort()]);
        }

        if (in_array($host, $this->urlWhitelist) == false) {
            $this->addError($field, 'URL not on whitelist');
        }
    } // end validateUrlWhitelisted

    protected function validateAttachments($field, $input)
    {
        if ($this->maxAttachments != null)
        {
            if (sizeof($input) > $this->maxAttachments) {
                $this->addError($field, 'has more than ' . $this->maxAttachments . ' items');
                return;
            }
        }

        foreach ($input as $index => $attachment) {
            $this->validateAttachment("$field.$index", $attachment);
        }
    } // end validateAttachment

    protected function validateAttachment($field, $input)
    {
         $this->validationRunner($field, $input, [
            'image' => 'validateAsset',
            'x' => 'validateNotEmpty',
            'y' => 'validateNotEmpty',
            'z' => 'validateNotEmpty', 
        ]);
    } // end validateAttachment

    protected function validateNotEmpty($field, $input) 
    {
        if ($input === '' || $input === null) {
            $this->addError($field, 'should not be null');
        }
    } // end validateNotEmpty

    protected function addError($field, $message)
    {
        if (substr($field, 0, 1) == '.') {
            $field = substr($field, 1);
        }

        if(array_key_exists($field, $this->errorBag) == false) {
            $this->errorBag[$field] = [];
        }
           
        $this->errorBag[$field] = $message;
    } // end addError

    public function getErrors()
    {
        return $this->errorBag;
    } // end getErrors

    public function getConfig()
    {
        return [
            'maxAttachments' => $this->maxAttachments,
            'urlWhitelist' => $this->urlWhitelist,
        ];
    } // end getConfig
} // end RequestValidatorService
