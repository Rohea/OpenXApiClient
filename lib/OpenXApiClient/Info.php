<?php
/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

namespace OpenXApiClient;

/**
 * @package    OpenXApiClient
 * @author     Andriy Petlyovanyy <apetlyovanyy@lohika.com>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 *
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
        $aFieldsTypes = $this->getFieldsTypes();
        if (!isset($aFieldsTypes) || !is_array($aFieldsTypes)) {
            throw new \InvalidArgumentException('Please provide field types array for Info object creation');
        }

        if (!array_key_exists($fieldName, $aFieldsTypes)) {
            throw new \InvalidArgumentException("Unknown type for field $fieldName.");
        }
        return $aFieldsTypes[$fieldName];
    }

    public function readDataFromArray($aEntityData)
    {
        $aFieldsTypes = $this->getFieldsTypes();
        foreach($aFieldsTypes as $fieldName => $fieldType) {
            if (array_key_exists($fieldName, $aEntityData)) {
                if ($fieldType == 'date') {
                    $this->$fieldName = new \DateTime($aEntityData[$fieldName]);
                } else {
                    $this->$fieldName = $aEntityData[$fieldName];
                }
            }
        }
    }

    public function toArray()
    {
        return (array)$this;
    }
}
