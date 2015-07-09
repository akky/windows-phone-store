<?php
 
use Akky\WindowsPhoneStore\WindowsPhoneStore;
 
class WindowsPhoneStoreTest extends PHPUnit_Framework_TestCase {

  public function testGetAppInfo_PaidApp()
  {
    $app_guid = '5fad1b41-f8db-4397-b584-4a0da12fe233';
    $app_info = WindowsPhoneStore::getAppInfo($app_guid, '8.0.10211.204', 'JP', 'ja-JP', '486577666');
    $this->assertNotEmpty($app_info['offers'], 'paid app must have offers info.');

//    var_dump($app_info);exit();
  }

  public function testGetAppInfo()
  {
    $app_guid = '4bfa010c-2e5f-4da8-86fa-03de83fb1ba3';
    $app_info = WindowsPhoneStore::getAppInfo($app_guid, '8.0.10211.204', 'JP', 'ja-JP', '486577666');
    $this->assertArrayNotHasKey('offers', $app_info, 'free app does not have offers.');

//    var_dump($app_info);exit();
  }

/* screenshot images can be different time by time probably because of CDN
  public function testRenderAppInfoInText()
  {
    $app_guid = '4bfa010c-2e5f-4da8-86fa-03de83fb1ba3';
    $app_info = WindowsPhoneStore::getAppInfo($app_guid, '8.0.10211.204', 'JP', 'ja-JP', '486577666');
    $texts = WindowsPhoneStore::renderAppInfoInText($app_info);
    $this->assertEquals($texts, <<<END_OF_TEXT
title: Lara Croft: Relic Run
publisher: Square Enix Ltd.
image: http://cdn.marketplaceimages.windowsphone.com/v8/images/d30646e5-841d-4a2c-93ef-7538b38f83c3?hw=520293381&imagetype=icon_large
Screenshots:
http://cdn.marketplaceimages.windowsphone.com/v8/images/428e8aea-2b0b-43fb-8148-d95ee7c82195?hw=520293381&imagetype=ws_screenshot_large&rotation=0
http://cdn.marketplaceimages.windowsphone.com/v8/images/b5d83cfe-2e15-4ce9-8a06-451869d4b067?hw=520293381&imagetype=ws_screenshot_large&rotation=0
http://cdn.marketplaceimages.windowsphone.com/v8/images/7b56ef24-e298-4903-bc82-e4a3f3aa92a5?hw=520293381&imagetype=ws_screenshot_large&rotation=0
http://cdn.marketplaceimages.windowsphone.com/v8/images/2c8ceb5f-8995-4662-8827-e4256072781d?hw=520293381&imagetype=ws_screenshot_large&rotation=0
http://cdn.marketplaceimages.windowsphone.com/v8/images/22192153-ade8-40f4-8fe3-bee37ca05f39?hw=520293381&imagetype=ws_screenshot_large&rotation=0

END_OF_TEXT
);
  }
*/

  public function testGetAppInfoUrl()
  {
    $app_guid = 'b658425e-ba4c-4478-9af3-791fd0f1abfe';
    $app_info_url = WindowsPhoneStore::getAppInfoUrl($app_guid, '8.0.10211.204', 'US', 'en-US', '486577666');
    $this->assertEquals($app_info_url, 'http://marketplaceedgeservice.windowsphone.com/v8/catalog/apps/b658425e-ba4c-4478-9af3-791fd0f1abfe?os=8.0.10211.204&cc=US&lang=en-US&hw=486577666');
  }

  public function testGetIconUrl()
  {
    $image_guid = '6bcd45a1-8fe4-40a4-a817-a8f66eec187e';
    $image_url = WindowsPhoneStore::getIconUrl($image_guid, 'large', '486577666');
    $this->assertEquals($image_url, 'http://cdn.marketplaceimages.windowsphone.com/v8/images/6bcd45a1-8fe4-40a4-a817-a8f66eec187e?hw=486577666&imagetype=icon_large');
  }

  public function testGetScreenshotUrl()
  {
    $image_guid = 'urn:uuid:d30646e5-841d-4a2c-93ef-7538b38f83c3';
    $image_url = WindowsPhoneStore::getScreenshotUrl($image_guid, 'large', 0, '486577666');
    $this->assertEquals($image_url, 'http://cdn.marketplaceimages.windowsphone.com/v8/images/d30646e5-841d-4a2c-93ef-7538b38f83c3?hw=486577666&imagetype=ws_screenshot_large&rotation=0');
  }

  public function testGetScreenshotUrlByRawId()
  {
    $image_guid = 'd30646e5-841d-4a2c-93ef-7538b38f83c3';
    $image_url = WindowsPhoneStore::getScreenshotUrl($image_guid, 'large', 0, '486577666');
    $this->assertEquals($image_url, 'http://cdn.marketplaceimages.windowsphone.com/v8/images/d30646e5-841d-4a2c-93ef-7538b38f83c3?hw=486577666&imagetype=ws_screenshot_large&rotation=0');
  }
}