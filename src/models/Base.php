<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

abstract class Base extends Model
{

    protected abstract function getSpecialProperties(): Array;

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $arr = parent::toArray($fields, $expand, $recursive);
        $mapped = [];

        $specialProps = $this->getSpecialProperties();

        foreach ($arr as $key => $value) {
            if (in_array($key, $specialProps)) {
                $mapped["\${$key}"] = $value;
            } else {
                $mapped[$key] = $value;
            }
        }

        return $mapped;
    }
}
