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
 * @author     Heiko Weber <heiko@wecos.de>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 *
 * This file describes the ChannelInfo class.
 */
namespace OpenXApiClient;

use Info;

/**
 *  The channelInfo class extends the base Info class and contains information about the channel.
 */
class ChannelInfo extends Info
{

    /**
     * The channelID variable is the unique ID for the channel.
     *
     * @var integer $channelId
     */
    protected $channelId;

    /**
     * This field contains the ID of the agency account.
     *
     * @var integer $agencyId
     */
    protected $agencyId;

    /**
     * This field contains the ID of the publisher.
     *
     * @var integer $websiteId
     */
    protected $websiteId;

    /**
     * The channelName variable is the name of the channel.
     *
     * @var string $channelName
     */
    protected $channelName;

    /**
     * The description variable is the description for the channel.
     *
     * @var string $description
     */
    protected $description;

    /**
     * The comments variable is the comment for the channel.
     *
     * @var string $comments
     */
    protected $comments;

    /**
     * This method sets all default values when adding a new channel.
     */
    public function getFieldsTypes()
    {
        return array(
            'channelId' => 'integer',
            'agencyId' => 'integer',
            'websiteId' => 'integer',
            'channelName' => 'string',
            'description' => 'string',
            'comments' => 'string',
        );
    }
}
