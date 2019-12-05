<?php

class PostData
{
    protected $data = [];

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        if (isset($_POST) && is_array($_POST)) {
            $this->data = $_POST;
        }
    }

    public function set($name, $value = null)
    {
        if (!is_array($name)) {
            $name = [
                $name => $value
            ];
        }

        foreach ($name as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return $default;
    }

    public function getAll()
    {
        return $this->data;
    }
}
