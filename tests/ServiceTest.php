<?php

use Imagick;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ServiceTest extends TestCase
{
    protected function makeScreenData()
    {
        return [
            'userAgent' => 'Mozilla (PHPUnit)',
            'viewport' => '1024x768',
            'scale' => 1,
        ];
    } // end makeScreenData

    public function testPassthroughBlob()
    {
        $duck = file_get_contents('public/testImg/steve_the_awful_duck.png');

        $resp = $this->json('POST', '/character', [
            'client' => $this->makeScreenData(),
            'baseImage' => [
                'type' => 'blob',
                'image' => base64_encode($duck),
            ],
            'attachments' => [],
        ]);

        $resp->assertResponseOk();

        // Using hashes so it doesn't dump the whole blob on your screen when the test fails
        $data = json_decode($this->response->getContent(), true);
        $this->assertEquals(md5($duck), md5(base64_decode($data['image'])));
    } // end testPssthrough

    public function testPassthroughUrl()
    {
        $duck = file_get_contents('public/testImg/steve_the_awful_duck.png');

        $resp = $this->json('POST', '/character', [
            'client' => $this->makeScreenData(),
            'baseImage' => [
                'type' => 'url',
                // @TODO - mock guzzle, this is awful
                'image' => 'https://character-dev.zuttopets.com/testImg/steve_the_awful_duck.png',
            ],
            'attachments' => [],
        ]);

        $resp->assertResponseOk();

        // Using hashes so it doesn't dump the whole blob on your screen when the test fails
        $data = json_decode($this->response->getContent(), true);
        $this->assertEquals(md5($duck), md5(base64_decode($data['image'])));
    } // end testPassthroughUrl

    public function testAssetUrlWhitelist()
    {
        $resp = $this->json('POST', '/character', [
            'client' => $this->makeScreenData(),
            'baseImage' => [
                'type' => 'url',
                'image' => 'https://not-whitelisted.com'
            ],
            'attachments' => [],
        ]);

        $resp->assertResponseStatus(500);
        $resp->seeJsonStructure(['errors' => ['baseImage.image']]);
    } // end testAssetUrlWhitelist

    public function testAttaching()
    {
        $duck_path = 'public/testImg/steve_the_awful_duck.png';
        $hat_path = 'public/testImg/sad_beanie.png';

        $pre_merge = new Imagick($duck_path);
        $pre_merge_pixel = $pre_merge->getImagePixelColor(41, 52)->getColor();
        unset($pre_merge);

        $resp = $this->json('POST', '/character', [
            'client' => $this->makeScreenData(),
            'baseImage' => [
                'type' => 'blob',
                'image' => base64_encode(file_get_contents($duck_path)),
            ],
            'attachments' => [
                [
                    'image' => [
                        'type' => 'blob',
                        'image' => base64_encode(file_get_contents($hat_path)),
                    ],
                    'x' => 25,
                    'y' => 44,
                    'z' => 1,
                ],
            ],
        ]);

        $resp->assertResponseOk();
        $data = json_decode($this->response->getContent(), true);

        $merge = new Imagick();
        $merge->readImageBlob(base64_decode($data['image']));
        $merge_pixel = $merge->getImagePixelColor(41, 52)->getColor();
       
        $this->assertNotEquals($merge_pixel, $pre_merge_pixel);
    } // end testAttaching
} // end TestService
