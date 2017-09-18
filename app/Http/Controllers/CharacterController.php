<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attachment;
use App\Services\ImageMergeService;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function generate(Request $request)
    {
        $payload = $request->all();

        // @TODO validate the client stuff, we'll need it at some point
        // In fact, rip a bunch of validation out of the POPOs and do a JSON schema validation

        $base_image = new Asset($payload['baseImage']);
        $attachments = [];
    
        foreach ($payload['attachments'] as $attach) {
            $attachments[] = new Attachment($attach['image'], $attach['x'], $attach['y'], $attach['z']);
        }

        $merge = new ImageMergeService($base_image, $attachments);
        $image_data = base64_encode($merge->render());

        return response(['image' => $image_data, 'contentType' => 'image/png']);
    } // end generate
} // end CharacterController
