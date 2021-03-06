<?php
namespace Splot\FrameworkExtraModule\DataBridge;

use MD\Foundation\Utils\ArrayUtils;

class DataBridge
{

    /**
     * Data container.
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Sets a value under a key.
     * 
     * @param string $key Key for the value.
     * @param mixed $value The value.
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Returns a value from under the key.
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->data[$key];
    }

    /**
     * Removes a value.
     * 
     * @param string $key
     */
    public function remove($key) {
        unset($this->data[$key]);
    }

    /**
     * Gets all data.
     * 
     * @return array
     */
    public function getAll() {
        return $this->data;
    }

    /**
     * Clears the whole bridge.
     */
    public function clear() {
        $this->data = array();
    }

    /**
     * Transforms the whole data to JSON format.
     * 
     * @return string
     */
    public function toJson() {
        return json_encode(ArrayUtils::fromObject($this->data));
    }

    /**
     * Serializes all data.
     * 
     * @return string
     */
    public function serialize() {
        return serialize($this->data);
    }

    /**
     * Converts the data to JSON string.
     * 
     * @return string
     */
    public function __toString() {
        return $this->toJson();
    }

}