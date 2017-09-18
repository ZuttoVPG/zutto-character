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
            return $this->baseImage->getImageData();
        }

        $image = new Imagick();
        $image->readImageBlob($this->baseImage->getImageData());
        foreach ($this->attachments as $attachment) {
            $image = $this->addAttachment($image, $attachment);
        }

        $image->setImageFormat('png');

        return $image->getImageBlob();
    } // end render

    public function addAttachment(Imagick $image, $attachment)
    {
        $addition_img = new Imagick();
        $addition_img->readImageBlob($attachment->getImageData());

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

} // end ImageMergeService
