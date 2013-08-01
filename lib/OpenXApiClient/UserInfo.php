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
 * @author     Matteo Beccati <matteo.beccati@openx.org>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 */
namespace OpenXApiClient;

use OpenXApiClient\Info;

/**
 * The OA_Dll_UserInfo class extends the base OA_Info class and
 * contains information about the user.
 */
class UserInfo extends Info
{
    /**
     * This fields provides the ID of the user
     *
     * @var int
     */
    protected $userId;

    /**
     * This option provides the name of the contact for the user.
     *
     * @var string $contactName
     */
    protected $contactName;

    /**
     * This field provides the email address of the user.
     *
     * @var string $emailAddress
     */
    protected $emailAddress;

    /**
     * This option provides the username of the user.
     *
     * @var string $username
     */
    protected $username;

    /**
     * This field provides the password of the user.
     *
     * @var string $password
     */
    protected $password;

    /**
     * This field provides the default account ID of the user.
     *
     * @var int $defaultAccountId
     */
    protected $defaultAccountId;

    /**
     * This field provides the status of the user.
     *
     * @var int $active
     */
    protected $active;

    public function getFieldsTypes()
    {
        return array(
            'userId' => 'integer',
            'contactName' => 'string',
            'emailAddress' => 'string',
            'username' => 'string',
            'password' => 'string',
            'defaultAccountId' => 'integer',
            'active' => 'integer',
        );
    }
}
