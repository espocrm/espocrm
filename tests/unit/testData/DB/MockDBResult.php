<?php

class MockDBResult extends ArrayIterator
{
    public function fetchAll()
    {
        $arr = array();
        foreach ($this as $value) {
            $arr[] = $value;
        }
        return $arr;
    }
}
