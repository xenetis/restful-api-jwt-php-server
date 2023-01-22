<?php

class AbstractModel {
    private ?string $table = null;

    public function __construct()
    {
        $this->table = strtolower(str_replace("Model", "", get_class($this)));
    }

    /**
     * @return string
     */
    public function getKeyField(): string
    {
        return 'id';
    }

}