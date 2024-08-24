<?php
/**LICENSE**/

namespace Espo\Classes\FieldProcessing\InboundEmail;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Config;
use Espo\Entities\InboundEmail;
use Espo\ORM\Entity;

/**
 * @implements Loader<InboundEmail>
 */
class IsSystemLoader implements Loader
{
    public function __construct(
        private Config $config,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $isSystem = $entity->getEmailAddress() === $this->config->get('outboundEmailFromAddress');

        $entity->set('isSystem', $isSystem);
    }
}
