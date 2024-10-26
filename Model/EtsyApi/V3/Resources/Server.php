<?php
    
namespace breakpoint\etsy\Resources;

require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyObject.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyResults.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyRequest.php';

use breakpoint\etsy\Classes\EtsyObject;
use breakpoint\etsy\Classes\EtsyRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents methods available at:
 *
 * Class Server
 * @package breakpoint\etsy
 */
class Server extends EtsyRequest {

    /**
     * Check that the server is alive.
     *
     * @param array $parameters
     * @return EtsyObject|ResponseInterface
     * @throws \Exception
     */
    public function ping(array $parameters = []) {
        return $this->requestObject('GET', '/openapi-ping', $parameters);
    }

    /**
     * Get server time, in epoch seconds notation.
     *
     * @param array $parameters
     * @return EtsyObject|ResponseInterface
     * @throws \Exception
     */
    public function getServerEpoch(array $parameters = []) {
        return $this->requestObject('GET', '/server/epoch', $parameters);
    }
}