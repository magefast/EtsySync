<?php
    
namespace breakpoint\etsy\Resources;

require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyObject.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyResults.php';
require_once BP . '/app/code/Strekoza/EtsySync/Model/EtsyApi/V3/Classes/EtsyRequest.php';

use breakpoint\etsy\Classes\EtsyObject;
use breakpoint\etsy\Classes\EtsyResults;
use breakpoint\etsy\Classes\EtsyRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents methods available at: https://www.etsy.com/developers/documentation/reference/taxonomynodeproperty
 *
 * Class TaxonomyNodeProperty
 * @package breakpoint\etsy
 */
class TaxonomyNodeProperty extends EtsyRequest {

    /**
     * Get the possible properties of a taxonomy node
     *
     * @param array $parameters
     * @return EtsyResults|ResponseInterface
     * @throws \Exception
     */
    public function getTaxonomyNodeProperties(array $parameters = []) {
        return $this->requestCollection('GET', '/taxonomy/seller/:taxonomy_id/properties', $parameters);
    }

}