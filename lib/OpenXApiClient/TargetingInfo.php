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
 * The OA_Dll_PublisherInfo class extends the base OA_Info class and contains
 * information about publisher
 */
class TargetingInfo extends Info
{
    /**
     * 99% will be "and" or "or", but that's not enforced
     *
     * @var string
     */
    protected $logical;

    /**
     * This is the plugin-component identifier
     *
     * @var string
     */
    protected $type;

    /**
     * String showing the operation to be applied (e.g.: '==', '!=', '>=',
     * 'ne')
     *
     * @var string
     */
    protected $comparison;

    /**
     * The exact structure varies from component to component
     *
     * @var string
     */
    protected $data;

    public function getFieldsTypes()
    {
        return array(
            'logical' => 'string',
            'type' => 'string',
            'comparison' => 'string',
            'data' => 'string'
        );
    }
}
