<?php

namespace tests\unit\testData\DB;

use Espo\ORM\BaseEntity;

class TEntity extends BaseEntity
{

}

class Account extends TEntity
{


}

class Team extends TEntity
{

}

class EntityTeam extends TEntity
{

}

class Contact extends TEntity
{

}

class Post extends TEntity
{

}

class Comment extends TEntity
{

}

class PostData extends TEntity
{

}

class Tag extends TEntity
{

}

class PostTag extends TEntity
{

}

class Note extends TEntity
{

}


class Article extends TEntity
{

}

class Job extends TEntity
{
    public function getFromContainerOriginal(string $attribute)
    {
        return $this->valuesContainer[$attribute] ?? null;
    }
}

class Test extends TEntity
{

}

class Dependee extends TEntity
{

}

class TestWhere extends TEntity
{

}

class TestSelect extends TEntity
{
}

class TestSelectRight extends TEntity
{
}