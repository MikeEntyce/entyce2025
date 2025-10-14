<?php

namespace Duplicator\Models;

use Duplicator\Core\Models\AbstractEntitySingleton;
use Exception;

class DynamicGlobalEntity extends AbstractEntitySingleton
{
    /**
     * @var array<string,scalar> Entity data
     */
    protected $data = [];

    /**
     * Class constructor
     */
    protected function __construct()
    {
    }

    /**
     * Retrieve the value of a key
     *
     * @param string      $key     Option name
     * @param null|scalar $default Default value to return if the key doesn't exist
     *
     * @return null|scalar
     */
    public function getVal($key, $default = null)
    {
        if (!isset($this->data[$key])) {
            return $default ;
        }
        return $this->data[$key];
    }

    /**
     * Set option value
     *
     * @param string      $key   Option name
     * @param null|scalar $value Option value
     * @param bool        $save  Save on DB
     *
     * @return bool
     */
    public function setVal($key, $value = null, $save = false)
    {
        if (strlen($key) == 0) {
            throw new Exception('Invalid key');
        }
        $this->data[$key] = $value;
        return ($save ? $this->save() : true);
    }

    /**
     * Delete option value
     *
     * @param string $key  Option name
     * @param bool   $save Save on DB
     *
     * @return bool
     */
    public function removeVal($key, $save = false)
    {
        if (!isset($this->data[$key])) {
            return true;
        }

        unset($this->data[$key]);
        return ($save ? $this->save() : true);
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'Dynamic_Entity';
    }
}
