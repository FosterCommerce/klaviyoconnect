<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

abstract class Base extends Model
{
    public $extra;

    protected abstract function getSpecialProperties(): Array;

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $arr = parent::toArray($fields, $expand, $recursive);
        $mapped = [];

        $specialProps = $this->getSpecialProperties();

        foreach ($arr as $key => $value) {
            if ($value) {
                if (in_array($key, $specialProps)) {
                    $mapped["\${$key}"] = $value;
                } else {
                    if ($key === 'extra') {
                        foreach ($value as $extraKey => $extraValue) {
                            $mapped[$extraKey] = $extraValue;
                        }
                    } else {
                        $mapped[$key] = $value;
                    }
                }
            }
        }

        return $mapped;
    }
}
