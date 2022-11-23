<?php

namespace Espo\Modules\Crm\Tools\Meeting;

class Invitee
{
    public function __construct(private string $entityType, private string $id)
    {}

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
