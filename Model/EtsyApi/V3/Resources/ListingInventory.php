<?php

namespace breakpoint\etsy\Resources;

require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyObject.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyResults.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyRequest.php';

use breakpoint\etsy\Classes\EtsyRequest;
use breakpoint\etsy\Classes\EtsyResults;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents methods available at: https://www.etsy.com/developers/documentation/reference/listinginventory
 *
 * Class ListingInventory
 * @package breakpoint\etsy
 */
class ListingInventory extends EtsyRequest
{
    /**
     * Get the inventory for a listing
     *
     * @param array $parameters
     * @return EtsyResults|ResponseInterface
     * @throws \Exception
     */
    public function getInventory(array $parameters = [])
    {
        return $this->requestCollection('GET', '/listings/:listing_id/inventory', $parameters);
    }

    /**
     * Update the inventory for a listing
     *
     * @param string $token
     * @param string $xApiKey
     * @param array $parameters
     * @param string $value
     * @return bool|ResponseInterface
     * @throws \Exception
     */
    public function updateInventory(array $parameters = [], string $value = '')
    {
        return $this->oauth()->requestPUTinventory('/listings/:listing_id/inventory', $parameters, $value);
    }
}