<?php

class MockDBResult extends ArrayIterator
{
    public function fetchAll()
    {
        $arr = [];
        foreach ($this as $value) {
            $arr[] = $value;
        }
        return $arr;
    }
}
