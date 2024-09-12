<?php
/**LICENSE**/

namespace Espo\Core\Select\Applier\AdditionalAppliers;

use Espo\Core\Select\Applier\AdditionalApplier;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Metadata;
use Espo\Entities\StarSubscription;
use Espo\Entities\User;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\SelectBuilder;

/**
 * @noinspection PhpUnused
 */
class IsStarred implements AdditionalApplier
{
    public function __construct(
        private string $entityType,
        private User $user,
        private Metadata $metadata,
    ) {}

    public function apply(SelectBuilder $queryBuilder, SearchParams $searchParams): void
    {
        if (!$this->metadata->get("scopes.$this->entityType.stars")) {
            return;
        }

        $queryBuilder
            ->select(Expr::isNotNull(Expr::column('starSubscription.id')), 'isStarred')
            ->leftJoin(StarSubscription::ENTITY_TYPE, 'starSubscription', [
                'userId' => $this->user->getId(),
                'entityType' => $this->entityType,
                'entityId:' => 'id',
            ]);
    }
}
