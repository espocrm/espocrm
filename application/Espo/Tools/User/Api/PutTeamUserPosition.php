<?php
/**LICENSE**/

namespace Espo\Tools\User\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\EntityProvider;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * @noinspection PhpUnused
 */
class PutTeamUserPosition implements Action
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private EntityProvider $entityProvider,
        private EntityManager $entityManager,
    ) {}

    public function process(Request $request): Response
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $teamId = $request->getRouteParam('id') ?? throw new BadRequest();
        $userId = $request->getParsedBody()->id;
        $position = $request->getParsedBody()->position;

        if (!is_string($userId) || !$userId) {
            throw new BadRequest("Bad userId.");
        }

        if (!is_string($position) && $position !== null) {
            throw new BadRequest("Bad position.");
        }

        $user = $this->entityProvider->getByClass(User::class, $userId);
        $team = $this->entityProvider->getByClass(Team::class, $teamId);

        if (!$this->acl->checkEntityEdit($user)) {
            throw new Forbidden();
        }

        if ($position !== null && !in_array($position, $team->getPositionList())) {
            throw new BadRequest("Not allowed position.");
        }

        $this->entityManager
            ->getRelation($team, 'users')
            ->updateColumns($user, ['role' => $position]);

        return ResponseComposer::json(true);
    }
}
