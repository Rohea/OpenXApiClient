<?php
/*
+---------------------------------------------------------------------------+
| Revive Adserver API Client                                                |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

namespace OpenXApiClient;

/**
 * The Info class is the base class for all info classes.
 */
abstract class Info
{
    /**
     * This function must be defined in all subclasses
     */
    public abstract function getFieldsTypes();

    public function getFieldType($fieldName)
    {
        $fieldTypes = $this->getFieldsTypes();
        if (!isset($fieldTypes) || !is_array($fieldTypes)) {
            throw new \InvalidArgumentException('Please provide field types array for Info object creation');
        }

        if (!array_key_exists($fieldName, $fieldTypes)) {
            throw new \InvalidArgumentException("Unknown type for field $fieldName.");
        }
        return $fieldTypes[$fieldName];
    }

    public function readDataFromArray(array $entityData)
    {
        $fieldTypes = $this->getFieldsTypes();
        foreach($fieldTypes as $fieldName => $fieldType) {
            if (array_key_exists($fieldName, $entityData)) {
                $this->$fieldName = $entityData[$fieldName];
            }
        }
    }

    public function toArray()
    {
        return array_filter(get_object_vars($this), function ($v) {
            return isset($v);
        });
    }
}
