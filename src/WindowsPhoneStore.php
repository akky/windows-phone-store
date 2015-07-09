<?php namespace Akky\WindowsPhoneStore;

class WindowsPhoneStoreException extends \Exception {
}

class WindowsPhoneStore {
  const APP_ENDPOINT = 'http://marketplaceedgeservice.windowsphone.com/v8/catalog/apps/';
  const IMAGE_ENDPOINT = 'http://cdn.marketplaceimages.windowsphone.com/v8/images/';
  const STORE_URL_FORMAT = 'https://www.windowsphone.com/%1$s-%2$s/store/app/%3$s/%4$s';

  const HARDWARE_LUMIA_620 = 486577666;
  const HARDWARE_LUMIA_1520 = 520293381;

  public static function getAppInfoUrl(
    $app_guid,
    $os = '8.0.10211.204',
    $cc = 'US',
    $lang = 'en-US',
    $hw = self::HARDWARE_LUMIA_1520)
  {
    $params = array(
        'os' => $os,
        'cc' => $cc,
        'lang' => $lang,
        'hw' => $hw
    );
    return self::APP_ENDPOINT . $app_guid . '?'
             . http_build_query($params);
  }

  public static function getAppInfo(
    $app_guid,
    $os = '8.0.10211.204',
    $cc = 'US',
    $lang = 'en-US',
    $hw = self::HARDWARE_LUMIA_1520)
  {
    $url = self::getAppInfoUrl($app_guid, $os, $cc, $lang, $hw);
    
    // as a Windows browser
    $streamOptions = array(
      'http'=>array(
        'method'=>"GET",
        'header' => 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        'ignore_errors' => true // suppress file_get_contents() errors to be displayed
      )
    );
    $context = stream_context_create($streamOptions);
    $xmlString = file_get_contents($url, false, $context);
    // by ignore_errors, HTTP error is returned in text like "404 Not Found"
    if (preg_match('/\A(?P<status>\d{3})/', $xmlString, $matched)) {
      throw new WindowsPhoneStoreException($matched['status']);
    }
    if (!$xmlString) {
      throw new WindowsPhoneStoreException('Failed to fetch app page');
    }
    $xml = simplexml_load_string($xmlString);
    if (!$xml) {
      throw new WindowsPhoneStoreException('Failed to read page as xml');
    }
    $a = $xml->children("http://www.w3.org/2005/Atom");
    $aentry = $a->entry->children();
//var_dump($xml, $a, $aentry);
    return self::convertXml2Array($xml, $a, $aentry);
  }

  protected static function convertXml2Array(\SimpleXmlElement $xml, \SimpleXmlElement $a, \SimpleXmlElement $aentry)
  {
    $screenshots = array();
    foreach ($xml->screenshots->screenshot as $screenshot) {
        $screenshots[] = array(
            'id' => (string)($screenshot->id),
            'orientation' => (integer)($screenshot->orientation),
        );
    }

    $categories = array();
    if (isset($xml->categories)) {
        foreach ($xml->categories->category as $category) {
            $curCategory = array(
                'id' => (string)($category->id),
                'title' => (string)($category->title),
                'isRoot' => ($category->isRoot === 'True'),
            );
            if (isset($category->parentId)) {
                $curCategory['parentId'] = (string)($category->parentId);
            }
            $categories[] = $curCategory;
        }
    }

    $tags = array();
    if (isset($xml->tags)) {
        foreach ($xml->tags->tag as $tag) {
            $tags[] = (string)$tag;
        }
    }

//    $offers = array();
//    $stringfiedOffers = (string)($xml->offers);
    if (isset($xml->offers)) {
        foreach ($xml->offers->offer as $offer) {
            $clientTypes = array();
            foreach ($offer->clientTypes as $clientType) {
                $clientTypes[] = (string)($clientType);
            }
            $paymentTypes = array();
            foreach ($offer->paymentTypes as $paymentType) {
                $paymentTypes[] = (string)($paymentType);
            }
            $offers[] = array(
                'clientTypes' => $clientTypes,
                'paymentTypes' => $paymentTypes,
                'price' => (integer)($offer->price),
                'displayPrice' => (string)($offer->displayPrice),
                'priceCurrencyCode' => (string)($offer->priceCurrencyCode),
                'licenseRight' => (string)($offer->licenseRight),
            );
        }
    }

    $supportedLanguages = array();
    foreach ($aentry->supportedLanguages->supportedLanguage as $language) {
        $supportedLanguages[] = (string)($language);
    }

    $appInfo = array(
      // ---------------
      // <a>
      // link
      // updated
      'title' => (string)($a->title),
      'id' => (string)($a->id),
      'content' => (string)($a->content),
      // author
      // entry
      // ---------------
      // <a:entry>
      'version' => (string)($aentry->version),
      'payloadId' => (string)($aentry->payloadId),
      'skuId' => (string)($aentry->skuId),
      'skuLastUpdated' => \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', (string)($aentry->skuLastUpdated)),
      'isAvailableInCountry' => ((string)($aentry->isAvailableInCountry)==='true'),
      'isAvailableInStore' => ((string)($aentry->isAvailableInCountry)==='true'),
      //'isClientTypeCompatible'
      //'isHardwareCompatible'
      'isBlacklisted' => ((string)($aentry->isBlacklisted)==='true'),
      //'url' package binary
      'packageSize' => (integer)($aentry->packageSize),
      'installSize' => (integer)($aentry->installSize),
      'supportedLanguages' => $supportedLanguages,

      // ---------------
      'sortTitle' => (string)($xml->sortTitle),
      'releaseDate' => \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', (string)($xml->releaseDate)),
      'publisher' => (string)($xml->publisher),
      'publisherId' => (string)($xml->publisherId),
      'averageUserRating' => (float)($xml->averageUserRating),
      'userRatingCount' => (integer)($xml->userRatingCount),
      'image' => (string)($xml->image->id),
      'categories' => $categories,
      'tags' => $tags,
      'screenshots' => $screenshots,
    );

    if (isset($offers)) {
      $appInfo['offers'] = $offers;
    }

//    var_dump($appInfo);exit();
    return $appInfo;
  }

  protected static function getRawImageGuid($guid)
  {
    if (strncmp($guid, 'urn:uuid:', strlen('urn:uuid:')) === 0) {
        $guid = substr($guid, strlen('urn:uuid:'));
    }
    return $guid;
  }

  public static function getIconUrl(
    $image_guid,
    $size = 'large',
    $hw = self::HARDWARE_LUMIA_1520)
  {
    $params = array(
        'hw' => $hw,
        'imagetype' => 'icon_' . $size
    );
    return self::IMAGE_ENDPOINT . self::getRawImageGuid($image_guid) . '?'
             . http_build_query($params);
  }

  public static function getScreenshotUrl(
    $image_guid,
    $size = 'large',
    $rotation = 0,
    $hw = self::HARDWARE_LUMIA_1520)
  {
    $params = array(
        'hw' => $hw,
        'imagetype' => 'ws_screenshot_' . $size,
        'rotation' => (integer)$rotation
    );
    return self::IMAGE_ENDPOINT . self::getRawImageGuid($image_guid) . '?'
             . http_build_query($params);
  }

  /**
   * get clean Windows Phone Store URL by app ID
   */
  public static function getStoreUrl(
    $app_guid,
    $slug,
    $lang = 'en',
    $region = 'us')
  {
    return sprintf(self::STORE_URL_FORMAT, $lang, $region, $slug, $app_guid);
  }

  public static function renderAppInfoInText($appInfo) {
    $texts = '';

    $texts .= 'title: ' . $appInfo['sortTitle'] . PHP_EOL;
    $texts .= 'publisher: ' . $appInfo['publisher'] . PHP_EOL;

    // screenshots
    $texts .= 'image: ' . self::getIconUrl($appInfo['image'], 'large') . PHP_EOL;
    // screenshots
    $texts .= 'Screenshots:' . PHP_EOL;
    foreach ($appInfo['screenshots'] as $screenshot) {
      $screenshotUrl = self::getScreenshotUrl($screenshot['id'], 'large', $screenshot['orientation']);
      $texts .= $screenshotUrl . PHP_EOL;
    }
    return $texts;
  }
}