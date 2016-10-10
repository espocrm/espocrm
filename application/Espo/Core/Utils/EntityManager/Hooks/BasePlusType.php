<?php

namespace Espo\Core\Utils\EntityManager\Hooks;

class BasePlusType extends Base
{
    public function afterCreate($name, $params)
    {
        $activitiesEntityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        $historyEntityTypeList = $this->getConfig()->get('historyEntityList', []);
        $entityTypeList = array_merge($activitiesEntityTypeList, $historyEntityTypeList);
        $entityTypeList[] = 'Task';
        $entityTypeList = array_unique($entityTypeList);

        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'])) continue;

            $list = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'], []);
            if (!in_array($name, $list)) {
                $list[] = $name;
                $data = array(
                    'fields' => array(
                        'parent' => array(
                            'entityList' => $list
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityType, $data);
            }
        }

        $this->getMetadata()->save();
    }

    public function afterRemove($name)
    {
        $activitiesEntityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        $historyEntityTypeList = $this->getConfig()->get('historyEntityList', []);
        $entityTypeList = array_merge($activitiesEntityTypeList, $historyEntityTypeList);
        $entityTypeList[] = 'Task';
        $entityTypeList = array_unique($entityTypeList);

        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'])) continue;

            $list = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'parent', 'entityList'], []);
            if (in_array($name, $list)) {
                $key = array_search($name, $list);
                unset($list[$key]);
                $list = array_values($list);
                $data = array(
                    'fields' => array(
                        'parent' => array(
                            'entityList' => $list
                        )
                    )
                );
                $this->getMetadata()->set('entityDefs', $entityType, $data);
            }
        }

        $this->getMetadata()->save();
    }
}