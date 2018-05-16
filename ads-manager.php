<?php

require_once __DIR__ . '/facebook-api/vendor/autoload.php';

// logger module to handle error & tracer
include_once("logger.php");

// list of vendors used to interface with Facebook ads API
use FacebookAds\Api;

use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;

use FacebookAds\Object\Targeting;
use FacebookAds\Object\Fields\TargetingFields;

use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Values\AdSetBillingEventValues;
use FacebookAds\Object\Values\AdSetOptimizationGoalValues;

use FacebookAds\Object\AdImage;
use FacebookAds\Object\Fields\AdImageFields;

use FacebookAds\Object\AdCreative;
use FacebookAds\Object\AdCreativeLinkData;
use FacebookAds\Object\Fields\AdCreativeLinkDataFields;
use FacebookAds\Object\AdCreativeObjectStorySpec;
use FacebookAds\Object\Fields\AdCreativeObjectStorySpecFields;
use FacebookAds\Object\Fields\AdCreativeFields;

use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdFields;

// Facebook Configuration cf README
$accessToken = 'xxxx';
$appId = 'xxxx';
$appSecret = '';
$accountId = 'act_xxxx';
$pageId = 'xxxx';
$campaignId = 'xxxx';
// end Facebook Configuration

define('SDK_DIR', __DIR__ . '/facebook-api'); // Path to the SDK directory

/**
 * Connection to facebook API
 */

try{
    Api::init($appId, $appSecret, $accessToken);
    $api = Api::instance();

} catch (Exception $e) {
    errorLogger($e);
}

/**
 * Set break to an existing ad
 */

function setBreakAd($facebookAdSet){

   try {
      if($facebookAdSet != NULL){

          $adset = new AdSet($facebookAdSet);

          $adset->read(array(
            AdSetFields::NAME,
            AdSetFields::CONFIGURED_STATUS,
            AdSetFields::EFFECTIVE_STATUS,
          ));

          $adset->update(array(
              AdSet::STATUS_PARAM_NAME => AdSet::STATUS_PAUSED,
            ));
      }

    } catch (Exception $e) {
         errorLogger('update adset() Error: ' .$e->getErrorUserTitle());
         errorLogger($e->getErrorUserMessage());
         errorLogger($e->getMessage());
         errorLogger('Error Code: ' .$e->getCode());
    }
}

/**
 * Create an ad within an existing campaign
 */

function facebookAdCreation(){

    // variables' settings
    $geolocLatitude = 'xxxx';
    $geolocLongitude = 'xxxx';
    $bidAmount = 100;
    $lifetimeBudget = 1000;
    $dateStart = new DateTime();

    // make the ad lasting 25 hours
    $dateEnd = clone $dateStart;
    $dateEnd->modify('+25 hour');

    // time format
    $startTime = $dateStart->format(DateTime::ISO8601);
    $endTime = $dateEnd->format(DateTime::ISO8601);

    /**
     * Search Targeting
     */

    $targeting = new Targeting();
    $targeting->setData(array(
          TargetingFields::PUBLISHER_PLATFORMS => array('facebook'),
          TargetingFields::GEO_LOCATIONS =>
            array(
                'location_types' => array('recent'),
                'custom_locations' => array(
                                        array(
                                            'latitude' => $geolocLatitude,
                                            'longitude' => $geolocLongitude,
                                            'radius' => '6',
                                            'distance_unit' => 'kilometer')),
            ),
          TargetingFields::LOCALES => array(1003),
          TargetingFields::AGE_MIN => 18,
          TargetingFields::AGE_MAX => 55,
    ));

    /**
     * Create an adSet
     */

    try{

        $adSet = new AdSet(null, $accountId);
        $adSet->setData(array(
          AdSetFields::NAME => 'My adSet name',
          AdSetFields::OPTIMIZATION_GOAL => AdSetOptimizationGoalValues::LINK_CLICKS,
          AdSetFields::BILLING_EVENT => AdSetBillingEventValues::LINK_CLICKS,
          AdSetFields::BID_AMOUNT => $bidAmount,
          AdSetFields::LIFETIME_BUDGET => $lifetimeBudget,
          AdSetFields::PACING_TYPE => array('no_pacing'),
          AdSetFields::CAMPAIGN_ID => $campaignId,
          AdSetFields::TARGETING => $targeting,
          AdSetFields::START_TIME => $startTime,
          AdSetFields::END_TIME => $endTime,
        ));

        $adSet->create(array(
            AdSet::STATUS_PARAM_NAME => AdSet::STATUS_ACTIVE,
        ));


    } catch (Exception $e) {
        errorLogger('new AdSet() Error: ' .$e->getErrorUserTitle());
        errorLogger($e->getErrorUserMessage());
        errorLogger($e->getMessage());
        errorLogger('Error Code: ' .$e->getCode());
    }

    /**
     * Create an AdImage
     */

    try {
        $image = new AdImage(null, $accountId);
        $image->{AdImageFields::FILENAME}
            = SDK_DIR.'/images/mypicture.jpg';
        $image->create();
    }
    catch (Exception $e) {
        errorLogger('new AdImage() Error: ' .$e->getErrorUserTitle());
        errorLogger($e->getErrorUserMessage());
        errorLogger($e->getMessage());
        errorLogger('Error Code: ' .$e->getCode());
    }

    /**
     * Create an AdCreative
     */

     try {

        $linkData = new AdCreativeLinkData();
        $linkData->setData(array(
          AdCreativeLinkDataFields::MESSAGE => "My message",
          AdCreativeLinkDataFields::LINK => 'http://mylink.com',
          AdCreativeLinkDataFields::DESCRIPTION => 'My description',
          AdCreativeLinkDataFields::NAME => "My name",
          AdCreativeLinkDataFields::IMAGE_HASH => $image->hash,
        ));

        $objectStorySpec = new AdCreativeObjectStorySpec();
        $objectStorySpec->setData(array(
          AdCreativeObjectStorySpecFields::PAGE_ID => $pageId,
          AdCreativeObjectStorySpecFields::LINK_DATA => $linkData,
        ));

        $creative = new AdCreative(null, $accountId);

        $creative->setData(array(
          AdCreativeFields::NAME => 'My creative name'] ,
          AdCreativeFields::OBJECT_STORY_SPEC => $objectStorySpec,
        ));

        $creative->create();

        $adData = array(
          AdFields::NAME => 'My ad name',
          AdFields::ADSET_ID => $adSet->id,
          AdFields::CREATIVE => array(
            'creative_id' => $creative->id,
          ),
        );

    } catch (Exception $e) {
        errorLogger('new AdCreative() Error: ' .$e->getErrorUserTitle());
        errorLogger($e->getErrorUserMessage());
        errorLogger($e->getMessage());
        errorLogger('Error Code: ' .$e->getCode());
    }

    /**
     * Create an Ad
     */

    try {

        $ad = new Ad(null, $accountId);
        $ad->setData($adData);

        $ad->create(array(
          Ad::STATUS_PARAM_NAME => Ad::STATUS_ACTIVE,
        ));

      } catch (Exception $e) {
        errorLogger('new Ad() Error: ' .$e->getCode());
        errorLogger($e->getMessage());
    }
}

?>