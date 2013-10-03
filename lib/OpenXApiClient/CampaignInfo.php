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
 * @author     Andriy Petlyovanyy <apetlyovanyy@lohika.com>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 *
 * This file describes the CampaignInfo class.
 */
namespace OpenXApiClient;

use OpenXApiClient\Info;

/**
 *  The campaignInfo class extends the base Info class and contains information about the campaign.
 */
class CampaignInfo extends Info
{
    /**
     * The campaignId variable is the unique ID for the campaign.
     *
     * @var integer $campaignId
     */
    protected $campaignId;

    /**
     * The advertiserID variable is the ID of the advertiser associated with the campaign.
     *
     * @var integer $advertiserId
     */
    protected $advertiserId;

    /**
     * The campaignName variable is the name of the campaign.
     *
     * @var string $campaignName
     */
    protected $campaignName;

    /**
     * The startDate variable is the date the campaign will start.
     *
     * @var date $startDate
     */
    protected $startDate;

    /**
     * The endDate variable is the date the campaign will end.
     *
     * @var date $endDate
     */
    protected $endDate;

    /**
     * The impressions variable is the number of impressions booked for the campaign.
     *
     * @var integer $impressions
     */
    protected $impressions = -1;

    /**
     * The clicks variable is the number of clicks booked for the campaign.
     *
     * @var integer $clicks
     */
    protected $clicks = -1;

    /**
     * The priority variable is the priority level set for the campaign.
     *
     * @var integer $priority
     */
    protected $priority = 0;

    /**
     * The weight variable is the weight set for the campaign.
     *
     * @var integer $weight
     */
    protected $weight = 1;

    /**
     *
     * @var integer $targetImpressions
     */
    protected $targetImpressions = 0;

    /**
     *
     * @var integer $targetClick
     */
    protected $targetClicks = 0;

    /**
     *
     * @var integer $targetConversions
     */
    protected $targetConversions = 0;

    /**
     * Revenue amount, eg 1.55.
     *
     * @var double $revenue
     */
    protected $revenue = 0.0;

    /**
     * Revenue type (CPM, CPA, etc) as defined in constants.php.
     * Eg, define('MAX_FINANCE_CPM',    1);
     *
     * @var integer $revenueType
     */
    protected $revenueType = 1;

    /**
     * Frequency capping: total views per user.
     *
     * @var integer $capping
     */
    protected $capping = 0;

    /**
     * Frequency capping: total views per period.
     * (defined in seconds by "block").
     *
     * @var integer $sessionCapping
     */
    protected $sessionCapping = 0;

    /**
     * Frequency capping: reset period, in seconds.
     *
     * @var integer $block
     */
    protected $block = 0;


    /**
     * This field provides any additional comments to be stored.
     *
     * @var string $comments
     */
    protected $comments;

    /**
     * This function sets all default values when adding new campaign.
     */
    public function setDefaultForAdd()
    {
        if (is_null($this->impressions)) {
            $this->impressions = -1;
        }

        if (is_null($this->clicks)) {
            $this->clicks = -1;
        }

        if (is_null($this->priority)) {
            $this->priority = 0;
        }

        if (is_null($this->weight)) {
            $this->weight = 1;
        }

        if (is_null($this->targetImpressions)) {
            $this->targetImpressions = 0;
        }

        if (is_null($this->targetClicks)) {
            $this->targetClicks = 0;
        }

        if (is_null($this->targetConversions)) {
            $this->targetConversions = 0;
        }

        if (is_null($this->revenue)) {
            // Leave null
        }

        if (is_null($this->revenueType)) {
            // Leave null
        }
        if (is_null($this->capping)) {
            // Leave null
        }

        if (is_null($this->sessionCapping)) {
            // Leave null
        }

        if (is_null($this->block)) {
            // Leave null
        }
    }

    /**
     * This method returns an array of fields with their corresponding types.
     *
     * @return array
     */
    public function getFieldsTypes()
    {
        return array(
            'campaignId' => 'integer',
            'advertiserId' => 'integer',
            'campaignName' => 'string',
            'startDate' => 'date',
            'endDate' => 'date',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'priority' => 'integer',
            'weight' => 'integer',
            'targetImpressions' => 'integer',
            'targetClicks' => 'integer',
            'targetConversions' => 'integer',
            'revenue' => 'double',
            'revenueType' => 'integer',
            'capping' => 'integer',
            'sessionCapping' => 'integer',
            'block' => 'integer',
            'comments' => 'string',
        );
    }
}
