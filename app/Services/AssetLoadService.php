<?php

namespace App\Services;

use App\Models\Asset;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class AssetLoadService
{
    protected $baseImage;
    protected $attachmentImages;

    protected $assets = [];
    protected $failedAssets = [];

    /**
     * Mapping of URL => [attachIndex, attachIndex] to avoid reloading
     * the same asset repeatedly.
     */
    protected $deduplicatedUrls = [];

    const BASE_IMAGE = -1;

    const LOAD_SUCCESS = 2;
    const LOAD_PARTIAL = 1;
    const LOAD_FAILED = 0;

    public static function fetch($request)
    {
        $service = new self($request['baseImage'], $request['attachments']);
        $service->loadAll();

        return $service;
    } // end $request

    public function __construct($baseImage, $attachmentImages)
    {
        $this->baseImage = $baseImage;
        $this->attachmentImages = $attachmentImages;

        $this->deduplicatedUrls = $this->deDuplicateUrls();
    } // end __construct

    public function loadAll()
    {
        $this->loadByBlob();
        $this->loadByUrl();

        return $this->successful();
    } // end loadAll

    public function successful()
    {
        // No base image = no love 
        if (array_key_exists(self::BASE_IMAGE, $this->assets) == false) {
            return self::LOAD_FAILED;
        }

        if (sizeof($this->failedAssets) > 0) {
            return self::LOAD_PARTIAL;
        }

        return self::LOAD_SUCCESS;
    } // end successful

    protected function loadByBlob()
    {
        foreach ($this->getByType('blob') as $index => $image) {
            $blob = base64_decode($image['image']);
            if ($blob === false) {
                $this->failedAssets[$index] = 'Failed to decode';
                continue;
            }

            $coord = $this->getCoordinates($index);
            $this->assets[$index] = new Asset($blob, '@TODO', strlen($blob), $coord['x'], $coord['y'], $coord['z']);
        }
    } // end loadByBlob

    protected function loadByUrl()
    {
        $client = new Client(['timeout' => 2]);

        $promises = [];
        foreach ($this->deduplicatedUrls as $url => $indexes) {
            $promises[$url] = $client->getAsync($url);
        }

        $results = Promise\settle($promises)->wait();

        foreach ($results as $url => $response) {
            $response = $response['value']; 

            foreach ($this->deduplicatedUrls[$url] as $index) {
                $resource = $response->getBody();

                $mime = $response->getHeader('Content-Type')[0];
                $filesize = $response->getHeader('Content-Length')[0];

                if ($response->getStatusCode() != 200) {
                    $this->failedAssets[$index] = $response->getReasonPhrase();
                    continue;
                } 

                $coord = $this->getCoordinates($index);
                $this->assets[$index] = new Asset($resource, $mime, $filesize, $coord['x'], $coord['y'], $coord['z']);
            } // end loop attachment indexes
        } // end loop responses
    } // end loadByUrl

    protected function getCoordinates($index)
    {
        if ($index != self::BASE_IMAGE) {
            return [
                'x' => $this->attachmentImages[$index]['x'],
                'y' => $this->attachmentImages[$index]['y'],
                'z' => $this->attachmentImages[$index]['z'],
            ];
        } 
        
        $coord = ['x' => null, 'y' => null, 'z' => null];
    } // end getCoordinates 

    protected function deDuplicateUrls()
    {
        $mapping = [];

        $all = $this->getByType('url');
        foreach ($all as $index => $image) {
            if (array_key_exists($image['image'], $mapping) == false) {
                $mapping[$image['image']] = [];
            }

            $mapping[$image['image']][] = $index;
        }

        return $mapping;
    } // end deduplicatedUrls

    protected function getByType($type)
    {
        // array_mergE Will ruin the keys since they are numeric-y
        $all = [self::BASE_IMAGE => $this->baseImage];

        foreach ($this->attachmentImages as $index => $attach) {
            $all[$index] = $attach['image'];
        }

        return array_filter($all, function ($image) use ($type) {
            return $image['type'] == $type;
        });
    } // end getByType 

    public function getErrors()
    {
        return $this->failedAssets;
    } // end getErrors

    public function getBaseImageAsset()
    {
        return $this->assets[self::BASE_IMAGE];
    } // end getBaseImage

    public function getAttachmentAssets()
    {
        return array_filter($this->assets, function ($index) { 
            return $index != self::BASE_IMAGE; 
        }, ARRAY_FILTER_USE_KEY); 
    } // end getAttachmentAssets

} // end AssetLoadService
