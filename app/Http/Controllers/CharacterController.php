<?php

namespace App\Http\Controllers;

use App\Services\AssetLoadService;
use App\Services\ImageMergeService;
use App\Services\RequestValidatorService;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function generate(Request $request)
    {
        $payload = $request->all();

        $validator = new RequestValidatorService($payload, config('character.maxAttachments'), config('character.urlWhitelist'));
        if ($validator->isValid() == false) {
            return response(['errors' => $validator->getErrors()], 500);
        }

        $loader = AssetLoadService::fetch($payload);
        if ($loader->successful() != AssetLoadService::LOAD_SUCCESS) {
            return response(['errors' => $loader->getErrors()], 500);
        }

        $merge = new ImageMergeService($loader->getBaseImageAsset(), $loader->getAttachmentAssets());
        $image_data = base64_encode($merge->render());

        return response(['image' => $image_data, 'contentType' => 'image/png']);
    } // end generate
} // end CharacterController
