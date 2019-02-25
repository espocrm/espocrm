<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM;

class SthCollection implements \IteratorAggregate
{
    private $entityManager = null;

    private $entityType;

    protected $selectParams = null;

    private $sth = null;

    private $sql = null;

    public function __construct(string $entityType, EntityManager $entityManager = null, array $selectParams = [])
    {
        $this->selectParams = $selectParams;
        $this->entityType = $entityType;
        $this->entityManager = $entityManager;
    }

    public function setSelectParams(array $selectParams)
    {
        $this->selectParams = $selectParams;
    }

    public function setQuery(?string $sql)
    {
        $this->sql = $sql;
    }

    public function executeQuery()
    {
        if ($this->sql) {
            $sql = $this->sql;
        } else {
            $sql = $this->entityManager->getQuery()->createSelectQuery($this->entityType, $this->selectParams);
        }
        $sth = $this->entityManager->getPdo()->prepare($sql);
        $sth->execute();

        $this->sth = $sth;
    }

    public function getIterator()
    {
        return (function () {
            while ($row = $this->fetchRow()) {
                $entity = $this->entityManager->getEntityFactory()->create($this->entityType);
                $entity->set($row);
                $entity->setAsFetched();
                $this->prepareEntity($entity);
                yield $entity;
            }
        })();
    }

    protected function fetchRow()
    {
        if (!$this->sth) {
            $this->executeQuery();
        }
        return $this->sth->fetch(\PDO::FETCH_ASSOC);
    }

    protected function prepareEntity(Entity $entity)
    {
    }
}
