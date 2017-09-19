<?php

namespace App\Services;

use \Imagick;

class ImageMergeService 
{
    protected $baseImage;
    protected $attachments;

    public function __construct($baseImage, $attachments) 
    {
        $this->baseImage = $baseImage;
        $this->attachments = $this->sortAttachments($attachments);
    } // end __construct

    public function render()
    {
        if (sizeof($this->attachments) == 0) {
            // NOOP, pass base image through unmolested
            return $this->resolveImageData($this->baseImage->getImageData());
        }

        $image = new Imagick();
        $image->readImageBlob($this->resolveImageData($this->baseImage->getImageData()));
        foreach ($this->attachments as $attachment) {
            $image = $this->addAttachment($image, $attachment);
        }

        $image->setImageFormat('png');

        return $image->getImageBlob();
    } // end render

    public function addAttachment(Imagick $image, $attachment)
    {
        $addition_img = new Imagick();
        $addition_img->readImageBlob($this->resolveImageData($attachment->getImageData()));

        $image->compositeImage($addition_img, Imagick::COMPOSITE_DEFAULT, $attachment->getXPos(), $attachment->getYPos());
        return $image;
    } // end attachment

    protected function sortAttachments($attachments) 
    {
        usort($attachments, function ($a, $b) {
            if ($a->getZIndex() == $b->getZIndex()) {
                return 0;
            }

            return ($a->getZIndex() < $b->getZIndex()) ? -1 : 1;
        });

        return $attachments;
    } // end sortAttachments

    protected function resolveImageData($data)
    {
        if (is_resource($data) == true) {
            return stream_get_contents($data);
        }

        return $data;
    } // end resolveImageData

} // end ImageMergeService
