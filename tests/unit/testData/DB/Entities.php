<?php

namespace tests\unit\testData\DB;

use Espo\ORM\BaseEntity;

class TestEntity extends BaseEntity
{
    public ?string $id = null;
}

class Account extends TestEntity
{


}

class Team extends TestEntity
{

}

class EntityTeam extends TestEntity
{

}

class Contact extends TestEntity
{

}

class Post extends TestEntity
{

}

class Comment extends TestEntity
{

}

class PostData extends TestEntity
{

}

class Tag extends TestEntity
{

}

class PostTag extends TestEntity
{

}

class Note extends TestEntity
{

}


class Article extends TestEntity
{

}

class Job extends TestEntity
{
    public function getFromContainerOriginal(string $attribute)
    {
        return $this->getFromContainer($attribute);
    }
}

class Test extends TestEntity
{

}

class Dependee extends TestEntity
{

}

class TestWhere extends TestEntity
{

}

class TestSelect extends TestEntity
{
}

class TestSelectRight extends TestEntity
{
}
