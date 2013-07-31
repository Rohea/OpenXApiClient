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

/**
 * @package    OpenXApiClient
 * @author     Ivan Klishch <iklishch@lohika.com>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 *
 * This file describes the AdvertiserInfo class.
 */
namespace OpenXApiClient;

use Info;

/**
 *  The AdvertiserInfo class extends the base Info class and contains information about advertisers.
 */
class AdvertiserInfo extends Info
{

    /**
     * The advertiserID variable is the unique ID for the advertiser.
     *
     * @var integer $advertiserId
     */
    protected $advertiserId;

    /**
     * This field contains the ID of the agency account.
     *
     * @var integer $accountId
     */
    protected $accountId;

    /**
     * The agencyID variable is the ID of the agency to which the advertiser is associated.
     *
     * @var integer $agencyId
     */
    protected $agencyId;

    /**
     * The advertiserName variable is the name of the advertiser.
     *
     * @var string $advertiserName
     */
    protected $advertiserName;

    /**
     * The contactName variable is the name of the contact.
     *
     * @var string $contactName
     */
    protected $contactName;

    /**
     * The emailAddress variable is the email address for the contact.
     *
     * @var string $emailAddress
     */
    protected $emailAddress;

    /**
     * This field provides any additional comments to be stored.
     *
     * @var string $comments
     */
    protected $comments;

    public function getFieldsTypes()
    {
        return array(
            'advertiserId' => 'integer',
            'accountId' => 'integer',
            'agencyId' => 'integer',
            'advertiserName' => 'string',
            'contactName' => 'string',
            'emailAddress' => 'string',
            'comments' => 'string',
        );
    }
}
