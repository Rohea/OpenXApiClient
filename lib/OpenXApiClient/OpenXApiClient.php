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

use fXmlRpc\Client;
use fXmlRpc\Exception\ResponseException;

use \DateTime;

/**
 * A library class to provide XML-RPC routines on
 * a web server to enable it to manipulate objects in OpenX using the web services API.
 *
 * @package    OpenXApiClient
 * @author     Chris Nutting <Chris.Nutting@openx.org>
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class OpenXApiClient
{

    /*
    protected $host;
    protected $basepath;
    protected $port;
    protected $ssl;
    protected $timeout;
    protected $username;
    protected $password;
    */
    /**
     * The sessionId is set by the logon() method called during the constructor.
     *
     * @var string The remote session ID is used in all subsequent transactions.
     */
    protected $sessionId;
    /**
     * Purely for my own use, this parameter lets me pass debug querystring parameters into
     * the remote call to trigger my Zend debugger on the server-side
     *
     * This will be removed before release
     *
     * @var string The querystring parameters required to trigger my remote debugger
     *             or empty for no remote debugging
     */
    protected $debug = '';

    private $client;

    /**
     * Constructor
     *
     * @param Client $client    Fully prepared fXmlRpc client instance
     * @param string $username  The username to authenticate to the web services API.
     * @param string $password  The password for this user.
     */
    public function __construct(Client $client, $username, $password)
    {
        $this->client = $client;
        $this->logon($username, $password);
    }

    /**
     * Constructor REMOVED.
     *
     * @param string $host      The name of the host to which to connect.
     * @param string $basepath  The base path to XML-RPC services.
     * @param string $username  The username to authenticate to the web services API.
     * @param string $password  The password for this user.
     * @param int    $port      The port number. Use 0 to use standard ports which
     *                          are port 80 for HTTP and port 443 for HTTPS.
     * @param bool   $ssl       Set to true to connect using an SSL connection.
     * @param int    $timeout   The timeout period to wait for a response.
     */
    /*
    public function __construct($host, $basepath, $username, $password, $port = 0, $ssl = false, $timeout = 15)
    {
        $this->host = $host;
        $this->basepath = $basepath;
        $this->port = $port;
        $this->ssl  = $ssl;
        $this->timeout = $timeout;
        $this->username = $username;
        $this->password = $password;
        $this->logon();
    }
    */

    /**
     * This private function sends a method call and $data to a specified service and automatically
     * adds the value of the sessionID.
     *
     * @param string $method  The name of the remote method to call.
     * @param mixed  $data    The data to send to the web service.
     * @return mixed The response from the server or false in the event of failure.
     */
    private function sendWithSession($method, $data = array())
    {
        return $this->send($method, $data, array($this->sessionId));
    }

    /**
     * This function sends a method call to a specified service.
     *
     * @param string $method  The name of the remote method to call.
     * @param mixed  $data    The data to send to the web service.
     * @return mixed The response from the server or false in the event of failure.
     */
    private function send($method, $data, $prepend = array())
    {
        try {
            $this->client->prependParams($prepend);
            foreach ($data as &$v) {
                if ($v instanceof \OpenXApiClient\Info) {
                    $v = $v->toArray();
                }
            }
            $response = $this->client->call($method, $data);
        } catch (ResponseException $e) {
            //Do something smarter?
            throw $e;
        }

        return $response;
    }

    /**
     * This method logs on to web services.
     *
     * @return boolean "Was the remote logon() call successful?"
     */
    private function logon($username, $password)
    {
        $this->sessionId = $this->send('ox.logon', array($username, $password));

        return true;
    }

    /**
     * This method logs off from web wervices.
     *
     * @return boolean "Was the remote logoff() call successful?"
     */
    public function logoff()
    {
        return (bool) $this->sendWithSession('ox.logoff');;
    }

    /**
     * This method returns statistics for an entity.
     *
     * @param string  $methodName
     * @param int  $entityId
     * @param DateTime  $startDate
     * @param DateTime  $endDate
     * @return array  result data
     */
    private function callStatisticsMethod($methodName, $entityId, DateTime $startDate = null, DateTime $endDate = null, $useManagerTimezone = false)
    {
        if (! isset($startDate)) {
            $startDate =  new DateTime('1970-01-01 00:00:00');
        }
        if (! isset($endDate)) {
            $endDate = new DateTime();
        }

        $dataArray = array(
            (int) $entityId,
            $startDate,
            $endDate,
            (bool) $useManagerTimezone,
        );

        $statisticsData = $this->sendWithSession($methodName, $dataArray);

        return $statisticsData;
    }

    /**
     * This method sends a call to the AgencyXmlRpcService and
     * passes the AgencyInfo with the session to add an agency.
     *
     * @param AgencyInfo $agencyInfo
     * @return  method result
     */
    public function addAgency($agencyInfo)
    {
        return (int) $this->sendWithSession('ox.addAgency', array($agencyInfo));
    }

    /**
     * This method sends a call to the AgencyXmlRpcService and
     * passes the AgencyInfo object with the session to modify an agency.
     *
     * @param AgencyInfo $agencyInfo
     * @return  method result
     */
    public function modifyAgency($agencyInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyAgency', array($agencyInfo));
    }

    /**
     * This method  returns the AgencyInfo for a specified agency.
     *
     * @param int $agencyId
     * @return AgencyInfo
     */
    public function getAgency($agencyId)
    {
        $dataAgency = $this->sendWithSession('ox.getAgency', array((int) $agencyId));
        $agencyInfo = new AgencyInfo();
        $agencyInfo->readDataFromArray($dataAgency);

        return $agencyInfo;
    }

    /**
     * This method returns AgencyInfo for all agencies.
     *
     * @param int $agencyId
     * @return array  array AgencyInfo objects
     */
    public function getAgencyList()
    {
        $dataAgencyList = $this->sendWithSession('ox.getAgencyList');
        $returnData = array();
        foreach ($dataAgencyList as $dataAgency) {
            $agencyInfo = new AgencyInfo();
            $agencyInfo->readDataFromArray($dataAgency);
            $returnData[] = $agencyInfo;
        }

        return $returnData;
    }

    /**
     * This method deletes a specified agency.
     *
     * @param int $agencyId
     * @return  method result
     */
    public function deleteAgency($agencyId)
    {
        return (bool) $this->sendWithSession('ox.deleteAgency', array((int) $agencyId));
    }

    /**
     * This method returns the daily statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyDailyStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.agencyDailyStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns the advertiser statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyAdvertiserStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyAdvertiserStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns the campaign statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyCampaignStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyCampaignStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns the banner statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyBannerStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyBannerStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns the publisher statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyPublisherStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyPublisherStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns the zone statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function agencyZoneStatistics($agencyId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyZoneStatistics', $agencyId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method adds an advertiser.
     *
     * @param AdvertiserInfo $advertiserInfo
     *
     * @return  method result
     */
    public function addAdvertiser($advertiserInfo)
    {
        return (int) $this->sendWithSession('ox.addAdvertiser', array($advertiserInfo));
    }

    /**
     * This method modifies an advertiser.
     *
     * @param AdvertiserInfo $advertiserInfo
     *
     * @return  method result
     */
    public function modifyAdvertiser($advertiserInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyAdvertiser', array($advertiserInfo));
    }

    /**
     * This method returns AdvertiserInfo for a specified advertiser.
     *
     * @param int $advertiserId
     *
     * @return AdvertiserInfo
     */
    public function getAdvertiser($advertiserId)
    {
        $dataAdvertiser = $this->sendWithSession('ox.getAdvertiser', array((int) $advertiserId));
        $advertiserInfo = new AdvertiserInfo();
        $advertiserInfo->readDataFromArray($dataAdvertiser);

        return $advertiserInfo;
    }

    /**
     * This method returns a list of advertisers by Agency ID.
     *
     * @param int $agencyId
     *
     * @return array  array AgencyInfo objects
     */
    public function getAdvertiserListByAgencyId($agencyId)
    {
        $dataAdvertiserList = $this->sendWithSession('ox.getAdvertiserListByAgencyId', array((int) $agencyId));
        $returnData = array();
        foreach ($dataAdvertiserList as $dataAdvertiser) {
            $advertiserInfo = new AdvertiserInfo();
            $advertiserInfo->readDataFromArray($dataAdvertiser);
            $returnData[] = $advertiserInfo;
        }

        return $returnData;
    }

    /**
     * This method deletes an advertiser.
     *
     * @param int $advertiserId
     * @return  method result
     */
    public function deleteAdvertiser($advertiserId)
    {
        return (bool) $this->sendWithSession('ox.deleteAdvertiser', array((int) $advertiserId));
    }

    /**
     * This method returns daily statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function advertiserDailyStatistics($advertiserId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.advertiserDailyStatistics', $advertiserId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = $data['day']->format('Y-m-d');
        }

        return $statisticsData;
    }

    /**
     * This method returns campaign statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function advertiserCampaignStatistics($advertiserId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserCampaignStatistics', $advertiserId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns banner statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function advertiserBannerStatistics($advertiserId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserBannerStatistics', $advertiserId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function advertiserPublisherStatistics($advertiserId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserPublisherStatistics', $advertiserId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns zone statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function advertiserZoneStatistics($advertiserId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserZoneStatistics', $advertiserId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method adds a campaign to the campaign object.
     *
     * @param CampaignInfo $campaignInfo
     *
     * @return  method result
     */
    public function addCampaign($campaignInfo)
    {
        return (int) $this->sendWithSession('ox.addCampaign', array($campaignInfo));
    }

    /**
     * This method modifies a campaign.
     *
     * @param CampaignInfo $campaignInfo
     *
     * @return  method result
     */
    public function modifyCampaign($campaignInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyCampaign', array($campaignInfo));
    }

    /**
     * This method returns CampaignInfo for a specified campaign.
     *
     * @param int $campaignId
     *
     * @return CampaignInfo
     */
    public function getCampaign($campaignId)
    {
        $dataCampaign = $this->sendWithSession('ox.getCampaign', array((int) $campaignId));

        $campaignInfo = new CampaignInfo();
        $campaignInfo->readDataFromArray($dataCampaign);

        return $campaignInfo;
    }

    /**
     * This method returns a list of campaigns for an advertiser.
     *
     * @param int $campaignId
     *
     * @return array  array CampaignInfo objects
     */
    public function getCampaignListByAdvertiserId($advertiserId)
    {
        $dataCampaignList = $this->sendWithSession('ox.getCampaignListByAdvertiserId', array((int) $advertiserId));
        $returnData = array();
        foreach ($dataCampaignList as $dataCampaign) {
            $campaignInfo = new CampaignInfo();
            $campaignInfo->readDataFromArray($dataCampaign);
            $returnData[] = $campaignInfo;
        }
        return $returnData;
    }

    /**
     * This method deletes a campaign from the campaign object.
     *
     * @param int $campaignId
     * @return  method result
     */
    public function deleteCampaign($campaignId)
    {
        return (bool) $this->sendWithSession('ox.deleteCampaign', array((int) $campaignId));
    }

    /**
     * This method returns daily statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function campaignDailyStatistics($campaignId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.campaignDailyStatistics', $campaignId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = $data['day']->format('Y-m-d');
        }

        return $statisticsData;
    }

    /**
     * This method returns banner statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function campaignBannerStatistics($campaignId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignBannerStatistics', $campaignId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function campaignPublisherStatistics($campaignId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignPublisherStatistics', $campaignId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns zone statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function campaignZoneStatistics($campaignId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignZoneStatistics', $campaignId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method adds a banner to the banner object.
     *
     * @param BannerInfo $bannerInfo
     *
     * @return  method result
     */
    public function addBanner($bannerInfo)
    {
        return (int) $this->sendWithSession('ox.addBanner', array($bannerInfo));
    }

    /**
     * This method modifies a banner.
     *
     * @param BannerInfo $bannerInfo
     *
     * @return  method result
     */
    public function modifyBanner($bannerInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyBanner', array($bannerInfo));
    }

    /**
     * This method returns BannerInfo for a specified banner.
     *
     * @param int $bannerId
     *
     * @return BannerInfo
     */
    public function getBanner($bannerId)
    {
        $dataBanner = $this->sendWithSession('ox.getBanner', array((int) $bannerId));
        $bannerInfo = new BannerInfo();
        $bannerInfo->readDataFromArray($dataBanner);

        return $bannerInfo;
    }

    /**
     * This method returns TargetingInfo for a specified banner.
     *
     * @param int $bannerId
     *
     * @return TargetingInfo
     */
    public function getBannerTargeting($bannerId)
    {
        $dataBannerTargetingList = $this->sendWithSession('ox.getBannerTargeting', array((int) $bannerId));
        $returnData = array();
        foreach ($dataBannerTargetingList as $dataBannerTargeting) {
            $bannerTargetingInfo = new TargetingInfo();
            $bannerTargetingInfo->readDataFromArray($dataBannerTargeting);
            $returnData[] = $bannerTargetingInfo;
        }
        return $returnData;
    }

    /**
     * This method takes an array of targeting info objects and a banner id
     * and sets the targeting for the banner to the values passed in
     *
     * @param integer $bannerId
     * @param array $aTargeting
     */
    public function setBannerTargeting($bannerId, $aTargeting)
    {
        $aTargetingInfoObjects = array();
        foreach ($aTargeting as $aTargetingArray) {
            $targetingInfo = new TargetingInfo();
            $targetingInfo->readDataFromArray($aTargetingArray);
            $aTargetingInfoObjects[] = $targetingInfo->toArray();
        }
        return (bool) $this->sendWithSession('ox.setBannerTargeting', array((int) $bannerId, $aTargetingInfoObjects));
    }

    /**
     * This method returns a list of banners for a specified campaign.
     *
     * @param int $banenrId
     *
     * @return array  array CampaignInfo objects
     */
    public function getBannerListByCampaignId($campaignId)
    {
        $dataBannerList = $this->sendWithSession('ox.getBannerListByCampaignId', array((int) $campaignId));
        $returnData = array();
        foreach ($dataBannerList as $dataBanner) {
            $bannerInfo = new BannerInfo();
            $bannerInfo->readDataFromArray($dataBanner);
            $returnData[] = $bannerInfo;
        }

        return $returnData;
    }

    /**
     * This method deletes a banner from the banner object.
     *
     * @param int $bannerId
     * @return  method result
     */
    public function deleteBanner($bannerId)
    {
        return (bool) $this->sendWithSession('ox.deleteBanner', array((int) $bannerId));
    }

    /**
     * This method returns daily statistics for a banner for a specified period.
     *
     * @param int $bannerId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function bannerDailyStatistics($bannerId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.bannerDailyStatistics', $bannerId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = $data['day']->format('Y-m-d');
        }

        return $statisticsData;
    }

    /**
     * This method returns publisher statistics for a banner for a specified period.
     *
     * @param int $bannerId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function bannerPublisherStatistics($bannerId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.bannerPublisherStatistics', $bannerId, $startDate, $endDate, $useManagerTimezone);

    }

    /**
     * This method returns zone statistics for a banner for a specified period.
     *
     * @param int $bannerId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function bannerZoneStatistics($bannerId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.bannerZoneStatistics', $bannerId, $startDate, $endDate, $useManagerTimezone);

    }

    /**
     * This method adds a publisher to the publisher object.
     *
     * @param PublisherInfo $publisherInfo
     * @return  method result
     */
    public function addPublisher($publisherInfo)
    {
        return (int) $this->sendWithSession('ox.addPublisher', array($publisherInfo));
    }

    /**
     * This method modifies a publisher.
     *
     * @param PublisherInfo $publisherInfo
     * @return  method result
     */
    public function modifyPublisher($publisherInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyPublisher', array($publisherInfo));
    }

    /**
     * This method returns PublisherInfo for a specified publisher.
     *
     * @param int $publisherId
     * @return PublisherInfo
     */
    public function getPublisher($publisherId)
    {
        $dataPublisher = $this->sendWithSession('ox.getPublisher', array((int) $publisherId));
        $publisherInfo = new PublisherInfo();
        $publisherInfo->readDataFromArray($dataPublisher);

        return $publisherInfo;
    }

    /**
     * This method returns a list of publishers for a specified agency.
     *
     * @param int $agencyId
     * @return array  array PublisherInfo objects
     */
    public function getPublisherListByAgencyId($agencyId)
    {
        $dataPublisherList = $this->sendWithSession('ox.getPublisherListByAgencyId', array((int) $agencyId));
        $returnData = array();
        foreach ($dataPublisherList as $dataPublisher) {
            $publisherInfo = new PublisherInfo();
            $publisherInfo->readDataFromArray($dataPublisher);
            $returnData[] = $publisherInfo;
        }

        return $returnData;
    }

    /**
     * This method deletes a publisher from the publisher object.
     *
     * @param int $publisherId
     * @return  method result
     */
    public function deletePublisher($publisherId)
    {
        return (bool) $this->sendWithSession('ox.deletePublisher', array((int) $publisherId));
    }

    /**
     * This method returns daily statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function publisherDailyStatistics($publisherId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.publisherDailyStatistics', $publisherId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = $data['day']->format('Y-m-d');
        }

        return $statisticsData;
    }

    /**
     * This method returns zone statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function publisherZoneStatistics($publisherId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherZoneStatistics', $publisherId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns advertiser statistics for a specified period.
     *
     * @param int $publisherId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function publisherAdvertiserStatistics($publisherId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherAdvertiserStatistics', $publisherId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns campaign statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function publisherCampaignStatistics($publisherId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherCampaignStatistics', $publisherId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns banner statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function publisherBannerStatistics($publisherId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherBannerStatistics', $publisherId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method adds a user to the user object.
     *
     * @param UserInfo $userInfo
     * @return  method result
     */
    public function addUser($userInfo)
    {
        return (int) $this->sendWithSession('ox.addUser', array($userInfo));
    }

    /**
     * This method modifies a user.
     *
     * @param UserInfo $userInfo
     * @return  method result
     */
    public function modifyUser($userInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyUser', array($userInfo));
    }

    /**
     * This method returns UserInfo for a specified user.
     *
     * @param int $userId
     * @return UserInfo
     */
    public function getUser($userId)
    {
        $dataUser = $this->sendWithSession('ox.getUser', array((int) $userId));
        $userInfo = new UserInfo();
        $userInfo->readDataFromArray($dataUser);

        return $userInfo;
    }

    /**
     * This method returns a list of users by Account ID.
     *
     * @param int $accountId
     *
     * @return array  array UserInfo objects
     */
    public function getUserListByAccountId($accountId)
    {
        $dataUserList = $this->sendWithSession('ox.getUserListByAccountId', array((int) $accountId));
        $returnData = array();
        foreach ($dataUserList as $dataUser) {
            $userInfo = new UserInfo();
            $userInfo->readDataFromArray($dataUser);
            $returnData[] = $userInfo;
        }

        return $returnData;
    }

    /**
     * This method updates users SSO User Id
     *
     * @param int $oldSsoUserId
     * @param int $newSsoUserId
     * @return bool
     */
    public function updateSsoUserId($oldSsoUserId, $newSsoUserId)
    {
        return (bool) $this->sendWithSession('ox.updateSsoUserId', array((int)$oldSsoUserId, (int)$newSsoUserId));
    }

    /**
     * This method updates users email by his SSO User Id
     *
     * @param int $ssoUserId
     * @param string $email
     * @return bool
     */
    public function updateUserEmailBySsoId($ssoUserId, $email)
    {
        return (bool) $this->sendWithSession('ox.updateUserEmailBySsoId', array((int)$ssoUserId, $email));
    }

    /**
     * This method deletes a user from the user object.
     *
     * @param int $userId
     * @return  method result
     */
    public function deleteUser($userId)
    {
        return (bool) $this->sendWithSession('ox.deleteUser', array((int) $userId));
    }

    /**
     * This method adds a zone to the zone object.
     *
     * @param ZoneInfo $zoneInfo
     * @return  method result
     */
    public function addZone($zoneInfo)
    {
        return (int) $this->sendWithSession('ox.addZone', array($zoneInfo));
    }

    /**
     * This method modifies a zone.
     *
     * @param ZoneInfo $zoneInfo
     * @return  method result
     */
    public function modifyZone($zoneInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyZone', array($zoneInfo));
    }

    /**
     * This method returns ZoneInfo for a specified zone.
     *
     * @param int $zoneId
     * @return ZoneInfo
     */
    public function getZone($zoneId)
    {
        $dataZone = $this->sendWithSession('ox.getZone', array((int) $zoneId));
        $zoneInfo = new ZoneInfo();
        $zoneInfo->readDataFromArray($dataZone);

        return $zoneInfo;
    }

    /**
     * This method returns a list of zones for a specified publisher.
     *
     * @param int $publisherId
     * @return array  array ZoneInfo objects
     */
    public function getZoneListByPublisherId($publisherId)
    {
        $dataZoneList = $this->sendWithSession('ox.getZoneListByPublisherId', array((int) $publisherId));
        $returnData = array();
        foreach ($dataZoneList as $dataZone) {
            $zoneInfo = new ZoneInfo();
            $zoneInfo->readDataFromArray($dataZone);
            $returnData[] = $zoneInfo;
        }

        return $returnData;
    }

    /**
     * This method deletes a zone from the zone object.
     *
     * @param int $zoneId
     * @return  method result
     */
    public function deleteZone($zoneId)
    {
        return (bool) $this->sendWithSession('ox.deleteZone', array((int) $zoneId));
    }

    /**
     * This method returns daily statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function zoneDailyStatistics($zoneId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.zoneDailyStatistics', $zoneId, $startDate, $endDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = $data['day']->format('Y-m-d');
        }

        return $statisticsData;
    }

    /**
     * This method returns advertiser statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function zoneAdvertiserStatistics($zoneId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.xzoneAdvertiserStatistics', $zoneId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns campaign statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function zoneCampaignStatistics($zoneId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.zoneCampaignStatistics', $zoneId, $startDate, $endDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array  result data
     */
    public function zoneBannerStatistics($zoneId, $startDate = null, $endDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.zoneBannerStatistics', $zoneId, $startDate, $endDate, $useManagerTimezone);
    }

    public function linkBanner($zoneId, $bannerId)
    {
        return (bool) $this->sendWithSession('ox.linkBanner', array((int)$zoneId, (int)$bannerId));
    }

    public function linkCampaign($zoneId, $campaignId)
    {
        return (bool) $this->sendWithSession('ox.linkCampaign', array((int)$zoneId, (int)$campaignId));
    }

    public function unlinkBanner($zoneId, $bannerId)
    {
        return (bool) $this->sendWithSession('ox.unlinkBanner', array((int)$zoneId, (int)$bannerId));
    }

    public function unlinkCampaign($zoneId, $campaignId)
    {
        return (bool) $this->sendWithSession('ox.unlinkCampaign', array((int)$zoneId, (int)$campaignId));
    }

    public function generateTags($zoneId, $codeType, $aParams = null)
    {
        if (!isset($aParams)) {
            $aParams = array();
        }
        return $this->sendWithSession('ox.generateTags', array((int)$zoneId, $codeType, $aParams));
    }
}

