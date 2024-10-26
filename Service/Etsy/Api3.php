<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Service\Etsy;

require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/EtsyClient.php';

use breakpoint\etsy\EtsyClient;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Strekoza\EtsySync\Service\Settings;

class Api3
{
    private $api;
    private $accessToken = null;
    private $skus = null;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllProducts(): ?array
    {
//        if (!empty($this->skus)) {
//            return $this->skus;
//        }

        $api = $this->connectApi();

        if ($api != null) {
            $this->readListing('active', $api);
            $this->readListing('inactive', $api);
            $this->readListing('draft', $api);
            $this->readListing('featured', $api);
            $this->readListing('expired', $api);
            /**
             * Add sold out ?
             */
        }

        return $this->skus;
    }

    /**
     * @return EtsyClient|null
     */
    private function connectApi(): ?EtsyClient
    {
        if (!empty($this->api)) {
            return $this->api;
        }

        if ($this->api == null) {
            $consumerSecret = $this->scopeConfig->getValue('etsysync_sync/api/consumer_secret');
            $accessTokenSecret = $this->scopeConfig->getValue('etsysync_sync/api/access_token_secret');
            $apiKey = $this->scopeConfig->getValue('etsysync_sync/api/consumer_key');
            $accessToken = $this->getAccessToken();

            $this->api = new EtsyClient(
                $apiKey, $consumerSecret, $accessToken, $accessTokenSecret
            );
        }

        return $this->api;
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        $apiKey = $this->scopeConfig->getValue('etsysync_sync/api/consumer_key');
        $apiTokenOlvV1V2 = $this->scopeConfig->getValue('etsysync_sync/api/access_token');

        $data = [
            'grant_type' => 'token_exchange',
            'client_id' => $apiKey,
            'legacy_token' => $apiTokenOlvV1V2
        ];

        $cURLConnection = curl_init('https://api.etsy.com/v3/public/oauth/token');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $data);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $apiResponse = curl_exec($cURLConnection);
        curl_close($cURLConnection);

        $jsonArrayResponse = json_decode($apiResponse, true);

        if (isset($jsonArrayResponse['access_token'])) {
            $this->accessToken = $jsonArrayResponse['access_token'];
        }

        return $this->accessToken;
    }

    /**
     * @param $type
     * @param EtsyClient $api
     * @param array $params
     * @param bool $isLoop
     * @throws Exception
     */
    private function readListing($type, EtsyClient $api, array $params = [], $isLoop = false)
    {
        $params['shop_id'] = Settings::SHOP_ID;
        $params['limit'] = Settings::LIMIT_API_LISTING;

        if (!empty($page)) {
            $params['page'] = (int)$page;
        }

        if ($type == 'active') {
            $paramsQuery = $params;
            $paramsQuery['state'] = 'active';
            $result = $api->listing->findAllShopListingsActive($paramsQuery);
        } elseif ($type == 'inactive') {
            $paramsQuery = $params;
            $paramsQuery['state'] = 'inactive';
            $result = $api->listing->findAllShopListings($paramsQuery);
        } elseif ($type == 'featured') {
            $result = $api->listing->findAllShopListingsFeatured($params);
        } elseif ($type == 'expired') {
            $paramsQuery = $params;
            $paramsQuery['state'] = 'expired';
            $result = $api->listing->findAllShopListings($paramsQuery);
        } elseif ($type == 'draft') {
            $paramsQuery = $params;
            $paramsQuery['state'] = 'draft';
            $result = $api->listing->findAllShopListings($paramsQuery);
        }

        if (isset($result['results']) && is_array($result['results'])) {
            foreach ($result['results'] as $r) {
                if (isset($r['skus'])) {
                    foreach ($r['skus'] as $sku) {
                        $this->skus[$r['listing_id']] = $sku;
                    }
                } else {
                    $this->skusNotExist[$r['listing_id']] = $r['listing_id'];
                }
            }
        }

        if ($isLoop != true) {
            $count = intval($result['count']);
            $loopCount = round($count / 100);

            for ($x = 1; $x <= $loopCount; $x++) {
                if (isset($params['offset'])) {
                    $params['offset'] = $params['offset'] - 100;
                } else {
                    $params['offset'] = $count - 100;
                }

                if (0 > $params['offset']) {
                    break;
                }

                $this->readListing($type, $api, $params, true);
            }
        }
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function addListing($data)
    {
        $params['shop_id'] = Settings::SHOP_ID;
        $api = $this->connectApi();

        $result = $api->listing->createListing($params, $data);

        if (isset($result['listing_id'])) {
            return $result['listing_id'];
        }
    }

    /**
     * @param $listingId
     * @param $sku
     * @param $price
     * @return bool
     * @throws Exception
     */
    public function addSkuToNewListing($listingId, $sku, $price): bool
    {
        $api = $this->connectApi();

        $params = [];
        $params['listing_id'] = $listingId;

        $valueJson = '{"products":[{"sku":"' . (string)$sku . '","offerings":[{"price":' . $price . ',"quantity":1,"is_enabled":true}]}]}';
        $resultUpdate = $api->listinginventory->updateInventory($params, $valueJson);

        if (
            is_array($resultUpdate)
            && isset($resultUpdate['products'])
            && isset($resultUpdate['products'][0])
            && isset($resultUpdate['products'][0]['sku'])
        ) {
            if ($resultUpdate['products'][0]['sku'] == $sku) {
                return true;
            }
        }

        throw new Exception('API ERROR. ' . 'SKU not added to Listing' . (string)$listingId);
    }

    /**
     * @param $listingId
     * @param $data
     * @throws Exception
     */
    public function uploadListingImage($listingId, $data)
    {
        $api = $this->connectApi();
        $params = [
            'listing_id' => $listingId,
            'shop_id' => Settings::SHOP_ID
        ];

        $result = $api->listingimage->uploadListingImageCurl($params, $data);
    }

    /**
     * @param $listingId
     * @param $data
     * @throws Exception
     */
    public function updateInventory($listingId, $data)
    {
        $api = $this->connectApi();
        $params = [
            'listing_id' => $listingId,
            'shop_id' => Settings::SHOP_ID
        ];

        $valueJson = '{"products":[{"sku":"' . (string)$data['sku'] . '","offerings":[{"price":' . $data['price'] . ',"quantity":' . $data['qty'] . ',"is_enabled":' . $data['enabled'] . '}]}]}';

        $result = $api->listinginventory->updateInventory($params, $valueJson);
    }
}