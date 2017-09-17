<?php

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
        $duck = file_get_contents('tests/test_img/steve_the_awful_duck.png');

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
        $this->assertEquals(md5($duck), md5($this->response->getContent()));
    } // end testPssthrough

    public function testPassthroughUrl()
    {
        // @TODO - mock the HTTP client 
        $this->assertEquals(1, 0);
    } // end testPassthroughUrl

    public function testAssetUrlWhitelist()
    {
         // @TODO - mock the HTTP client & config 
        $this->assertEquals(1, 0);
    } // end testAssetUrlWhitelist

    public function testAttaching()
    {
        $duck_path = 'tests/test_img/steve_the_awful_duck.png';
        $hat_path = 'tests/test_img/sad_beanie.png';

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
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                ],
            ],
        ]);

        $resp->assertResponseOk();
        // @TODO: do something clever to test the result, like look for pink on a spot that was black
        $this->assertEquals(1, 0);
    } // end testAttaching
} // end TestService
