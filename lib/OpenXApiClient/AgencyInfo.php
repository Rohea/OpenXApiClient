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
 *  The agencyInfo class extends the base Info class and contains information about the agency.
 */
class AgencyInfo extends Info
{
    /**
     * The agencyID variable is the unique ID for the agency.
     *
     * @var integer $agencyId
     */
    protected $agencyId;

    /**
     * This field contains the ID of the agency account.
     *
     * @var integer $accountId
     */
    protected $accountId;

    /**
     * The agencycName variable is the name of the agency.
     *
     * @var string $agencyName
     */
    protected $agencyName;

    /**
     * The password variable is the password for the agency.
     *
     * @var string $password
     */
    protected $password;

    /**
     * The contactName variable is the name of the contact for the agency.
     *
     * @var string $contactName
     */
    protected $contactName;

    /**
     * The emailAddress variable is the email address for the agency contact.
     *
     * @var string $emailAddress
     */
    protected $emailAddress;

    public function getFieldsTypes()
    {
        return array(
            'agencyId' => 'integer',
            'accountId' => 'integer',
            'agencyName' => 'string',
            'contactName' => 'string',
            'password' => 'string',
            'emailAddress' => 'string'
        );
    }
}
