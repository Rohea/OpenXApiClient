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
/*
if (!@include('XML/RPC.php')) {
    die('Error: cannot load the PEAR XML_RPC class');
}
*/
namespace OpenXApiClient;

//require_once 'XmlRpcUtils.php';

// Include the info-object files
use AdvertiserInfo;
use AgencyInfo;
use BannerInfo;
use CampaignInfo;
use PublisherInfo;
use TargetingInfo;
use UserInfo;
use ZoneInfo;

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

    protected $host;
    protected $basepath;
    protected $port;
    protected $ssl;
    protected $timeout;
    protected $username;
    protected $password;
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

    /**
     * Constructor
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

    /**
     * A private method to return an XML_RPC_Client to the API service
     *
     * @return XML_RPC_Client
     */
    private function getClient()
    {
        $oClient = &new XML_RPC_Client($this->basepath . '/' . $this->debug, $this->host);
        return $oClient;
    }

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
        return $this->send($method, array_merge(array($this->sessionId), $data));
    }

    /**
     * This function sends a method call to a specified service.
     *
     * @param string $method  The name of the remote method to call.
     * @param mixed  $data    The data to send to the web service.
     * @return mixed The response from the server or false in the event of failure.
     */
    private function send($method, $data)
    {
        $dataMessage = array();
        foreach ($data as $element) {
            if (is_object($element) && is_subclass_of($element, 'OA_Info')) {
                $dataMessage[] = XmlRpcUtils::getEntityWithNotNullFields($element);
            } else {
                $dataMessage[] = XML_RPC_encode($element);
            }
        }
        $message = new XML_RPC_Message($method, $dataMessage);

        $client = $this->getClient();

        // Send the XML-RPC message to the server.
        $response = $client->send($message, $this->timeout, $this->ssl ? 'https' : 'http');

        // Check for an error response.
        if ($response && $response->faultCode() == 0) {
            $result = XML_RPC_decode($response->value());
        } else {
            trigger_error('XML-RPC Error (' . $response->faultCode() . '): ' . $response->faultString() .
                ' in method ' . $method . '()', E_USER_ERROR);
        }
        return $result;
    }

    /**
     * This method logs on to web services.
     *
     * @return boolean "Was the remote logon() call successful?"
     */
    private function logon()
    {
        $this->sessionId = $this->send('ox.logon', array($this->username, $this->password));
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
     * @param Pear::Date  $oStartDate
     * @param Pear::Date  $oEndDate
     * @return array  result data
     */
    private function callStatisticsMethod($methodName, $entityId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $dataArray = array((int) $entityId);
        if (is_object($oStartDate)) {
            $dataArray[] = XML_RPC_iso8601_encode($oStartDate->getDate(DATE_FORMAT_UNIXTIME));

            if (is_object($oEndDate)) {
                $dataArray[] = XML_RPC_iso8601_encode($oEndDate->getDate(DATE_FORMAT_UNIXTIME));
            }
        }

        $dataArray[] = (bool) $useManagerTimezone;
        $statisticsData = $this->sendWithSession($methodName, $dataArray);

        return $statisticsData;
    }

    /**
     * This method sends a call to the AgencyXmlRpcService and
     * passes the AgencyInfo with the session to add an agency.
     *
     * @param OA_Dll_AgencyInfo $oAgencyInfo
     * @return  method result
     */
    public function addAgency(&$oAgencyInfo)
    {
        return (int) $this->sendWithSession('ox.addAgency', array(&$oAgencyInfo));
    }

    /**
     * This method sends a call to the AgencyXmlRpcService and
     * passes the AgencyInfo object with the session to modify an agency.
     *
     * @param OA_Dll_AgencyInfo $oAgencyInfo
     * @return  method result
     */
    public function modifyAgency(&$oAgencyInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyAgency', array(&$oAgencyInfo));
    }

    /**
     * This method  returns the AgencyInfo for a specified agency.
     *
     * @param int $agencyId
     * @return OA_Dll_AgencyInfo
     */
    public function getAgency($agencyId)
    {
        $dataAgency = $this->sendWithSession('ox.getAgency', array((int) $agencyId));
        $oAgencyInfo = new OA_Dll_AgencyInfo();
        $oAgencyInfo->readDataFromArray($dataAgency);

        return $oAgencyInfo;
    }

    /**
     * This method returns AgencyInfo for all agencies.
     *
     * @param int $agencyId
     * @return array  array OA_Dll_AgencyInfo objects
     */
    public function getAgencyList()
    {
        $dataAgencyList = $this->sendWithSession('ox.getAgencyList');
        $returnData = array();
        foreach ($dataAgencyList as $dataAgency) {
            $oAgencyInfo = new OA_Dll_AgencyInfo();
            $oAgencyInfo->readDataFromArray($dataAgency);
            $returnData[] = $oAgencyInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyDailyStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.agencyDailyStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);

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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyAdvertiserStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyAdvertiserStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns the campaign statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyCampaignStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyCampaignStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns the banner statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyBannerStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyBannerStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns the publisher statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyPublisherStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyPublisherStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns the zone statistics for an agency for a specified time period.
     *
     * @param int $agencyId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function agencyZoneStatistics($agencyId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.agencyZoneStatistics', $agencyId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method adds an advertiser.
     *
     * @param OA_Dll_AdvertiserInfo $oAdvertiserInfo
     *
     * @return  method result
     */
    public function addAdvertiser(&$oAdvertiserInfo)
    {
        return (int) $this->sendWithSession('ox.addAdvertiser', array(&$oAdvertiserInfo));
    }

    /**
     * This method modifies an advertiser.
     *
     * @param OA_Dll_AdvertiserInfo $oAdvertiserInfo
     *
     * @return  method result
     */
    public function modifyAdvertiser(&$oAdvertiserInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyAdvertiser', array(&$oAdvertiserInfo));
    }

    /**
     * This method returns AdvertiserInfo for a specified advertiser.
     *
     * @param int $advertiserId
     *
     * @return OA_Dll_AdvertiserInfo
     */
    public function getAdvertiser($advertiserId)
    {
        $dataAdvertiser = $this->sendWithSession('ox.getAdvertiser', array((int) $advertiserId));
        $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
        $oAdvertiserInfo->readDataFromArray($dataAdvertiser);

        return $oAdvertiserInfo;
    }

    /**
     * This method returns a list of advertisers by Agency ID.
     *
     * @param int $agencyId
     *
     * @return array  array OA_Dll_AgencyInfo objects
     */
    public function getAdvertiserListByAgencyId($agencyId)
    {
        $dataAdvertiserList = $this->sendWithSession('ox.getAdvertiserListByAgencyId', array((int) $agencyId));
        $returnData = array();
        foreach ($dataAdvertiserList as $dataAdvertiser) {
            $oAdvertiserInfo = new OA_Dll_AdvertiserInfo();
            $oAdvertiserInfo->readDataFromArray($dataAdvertiser);
            $returnData[] = $oAdvertiserInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function advertiserDailyStatistics($advertiserId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.advertiserDailyStatistics', $advertiserId, $oStartDate, $oEndDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns campaign statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function advertiserCampaignStatistics($advertiserId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserCampaignStatistics', $advertiserId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns banner statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function advertiserBannerStatistics($advertiserId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserBannerStatistics', $advertiserId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function advertiserPublisherStatistics($advertiserId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserPublisherStatistics', $advertiserId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns zone statistics for an advertiser for a specified period.
     *
     * @param int $advertiserId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function advertiserZoneStatistics($advertiserId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.advertiserZoneStatistics', $advertiserId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method adds a campaign to the campaign object.
     *
     * @param OA_Dll_CampaignInfo $oCampaignInfo
     *
     * @return  method result
     */
    public function addCampaign(&$oCampaignInfo)
    {
        return (int) $this->sendWithSession('ox.addCampaign', array(&$oCampaignInfo));
    }

    /**
     * This method modifies a campaign.
     *
     * @param OA_Dll_CampaignInfo $oCampaignInfo
     *
     * @return  method result
     */
    public function modifyCampaign(&$oCampaignInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyCampaign', array(&$oCampaignInfo));
    }

    /**
     * This method returns CampaignInfo for a specified campaign.
     *
     * @param int $campaignId
     *
     * @return OA_Dll_CampaignInfo
     */
    public function getCampaign($campaignId)
    {
        $dataCampaign = $this->sendWithSession('ox.getCampaign', array((int) $campaignId));
        $oCampaignInfo = new OA_Dll_CampaignInfo();
        $oCampaignInfo->readDataFromArray($dataCampaign);

        return $oCampaignInfo;
    }

    /**
     * This method returns a list of campaigns for an advertiser.
     *
     * @param int $campaignId
     *
     * @return array  array OA_Dll_CampaignInfo objects
     */
    public function getCampaignListByAdvertiserId($advertiserId)
    {
        $dataCampaignList = $this->sendWithSession('ox.getCampaignListByAdvertiserId', array((int) $advertiserId));
        $returnData = array();
        foreach ($dataCampaignList as $dataCampaign) {
            $oCampaignInfo = new OA_Dll_CampaignInfo();
            $oCampaignInfo->readDataFromArray($dataCampaign);
            $returnData[] = $oCampaignInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function campaignDailyStatistics($campaignId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.campaignDailyStatistics', $campaignId, $oStartDate, $oEndDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns banner statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function campaignBannerStatistics($campaignId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignBannerStatistics', $campaignId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function campaignPublisherStatistics($campaignId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignPublisherStatistics', $campaignId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns zone statistics for a campaign for a specified period.
     *
     * @param int $campaignId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function campaignZoneStatistics($campaignId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.campaignZoneStatistics', $campaignId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method adds a banner to the banner object.
     *
     * @param OA_Dll_BannerInfo $oBannerInfo
     *
     * @return  method result
     */
    public function addBanner(&$oBannerInfo)
    {
        return (int) $this->sendWithSession('ox.addBanner', array(&$oBannerInfo));
    }

    /**
     * This method modifies a banner.
     *
     * @param OA_Dll_BannerInfo $oBannerInfo
     *
     * @return  method result
     */
    public function modifyBanner(&$oBannerInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyBanner', array(&$oBannerInfo));
    }

    /**
     * This method returns BannerInfo for a specified banner.
     *
     * @param int $bannerId
     *
     * @return OA_Dll_BannerInfo
     */
    public function getBanner($bannerId)
    {
        $dataBanner = $this->sendWithSession('ox.getBanner', array((int) $bannerId));
        $oBannerInfo = new OA_Dll_BannerInfo();
        $oBannerInfo->readDataFromArray($dataBanner);

        return $oBannerInfo;
    }

    /**
     * This method returns TargetingInfo for a specified banner.
     *
     * @param int $bannerId
     *
     * @return OA_Dll_TargetingInfo
     */
    public function getBannerTargeting($bannerId)
    {
        $dataBannerTargetingList = $this->sendWithSession('ox.getBannerTargeting', array((int) $bannerId));
        $returnData = array();
        foreach ($dataBannerTargetingList as $dataBannerTargeting) {
            $oBannerTargetingInfo = new OA_Dll_TargetingInfo();
            $oBannerTargetingInfo->readDataFromArray($dataBannerTargeting);
            $returnData[] = $oBannerTargetingInfo;
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
    public function setBannerTargeting($bannerId, &$aTargeting)
    {
        $aTargetingInfoObjects = array();
        foreach ($aTargeting as $aTargetingArray) {
            $oTargetingInfo = new OA_Dll_TargetingInfo();
            $oTargetingInfo->readDataFromArray($aTargetingArray);
            $aTargetingInfoObjects[] = $oTargetingInfo;
        }
        return (bool) $this->sendWithSession('ox.setBannerTargeting', array((int) $bannerId, $aTargetingInfoObjects));
    }

    /**
     * This method returns a list of banners for a specified campaign.
     *
     * @param int $banenrId
     *
     * @return array  array OA_Dll_CampaignInfo objects
     */
    public function getBannerListByCampaignId($campaignId)
    {
        $dataBannerList = $this->sendWithSession('ox.getBannerListByCampaignId', array((int) $campaignId));
        $returnData = array();
        foreach ($dataBannerList as $dataBanner) {
            $oBannerInfo = new OA_Dll_BannerInfo();
            $oBannerInfo->readDataFromArray($dataBanner);
            $returnData[] = $oBannerInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function bannerDailyStatistics($bannerId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.bannerDailyStatistics', $bannerId, $oStartDate, $oEndDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns publisher statistics for a banner for a specified period.
     *
     * @param int $bannerId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function bannerPublisherStatistics($bannerId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.bannerPublisherStatistics', $bannerId, $oStartDate, $oEndDate, $useManagerTimezone);

    }

    /**
     * This method returns zone statistics for a banner for a specified period.
     *
     * @param int $bannerId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function bannerZoneStatistics($bannerId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.bannerZoneStatistics', $bannerId, $oStartDate, $oEndDate, $useManagerTimezone);

    }

    /**
     * This method adds a publisher to the publisher object.
     *
     * @param OA_Dll_PublisherInfo $oPublisherInfo
     * @return  method result
     */
    public function addPublisher(&$oPublisherInfo)
    {
        return (int) $this->sendWithSession('ox.addPublisher', array(&$oPublisherInfo));
    }

    /**
     * This method modifies a publisher.
     *
     * @param OA_Dll_PublisherInfo $oPublisherInfo
     * @return  method result
     */
    public function modifyPublisher(&$oPublisherInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyPublisher', array(&$oPublisherInfo));
    }

    /**
     * This method returns PublisherInfo for a specified publisher.
     *
     * @param int $publisherId
     * @return OA_Dll_PublisherInfo
     */
    public function getPublisher($publisherId)
    {
        $dataPublisher = $this->sendWithSession('ox.getPublisher', array((int) $publisherId));
        $oPublisherInfo = new OA_Dll_PublisherInfo();
        $oPublisherInfo->readDataFromArray($dataPublisher);

        return $oPublisherInfo;
    }

    /**
     * This method returns a list of publishers for a specified agency.
     *
     * @param int $agencyId
     * @return array  array OA_Dll_PublisherInfo objects
     */
    public function getPublisherListByAgencyId($agencyId)
    {
        $dataPublisherList = $this->sendWithSession('ox.getPublisherListByAgencyId', array((int) $agencyId));
        $returnData = array();
        foreach ($dataPublisherList as $dataPublisher) {
            $oPublisherInfo = new OA_Dll_PublisherInfo();
            $oPublisherInfo->readDataFromArray($dataPublisher);
            $returnData[] = $oPublisherInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function publisherDailyStatistics($publisherId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.publisherDailyStatistics', $publisherId, $oStartDate, $oEndDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns zone statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function publisherZoneStatistics($publisherId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherZoneStatistics', $publisherId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns advertiser statistics for a specified period.
     *
     * @param int $publisherId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function publisherAdvertiserStatistics($publisherId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherAdvertiserStatistics', $publisherId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns campaign statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function publisherCampaignStatistics($publisherId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherCampaignStatistics', $publisherId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns banner statistics for a publisher for a specified period.
     *
     * @param int $publisherId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function publisherBannerStatistics($publisherId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.publisherBannerStatistics', $publisherId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method adds a user to the user object.
     *
     * @param OA_Dll_UserInfo $oUserInfo
     * @return  method result
     */
    public function addUser(&$oUserInfo)
    {
        return (int) $this->sendWithSession('ox.addUser', array(&$oUserInfo));
    }

    /**
     * This method modifies a user.
     *
     * @param OA_Dll_UserInfo $oUserInfo
     * @return  method result
     */
    public function modifyUser(&$oUserInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyUser', array(&$oUserInfo));
    }

    /**
     * This method returns UserInfo for a specified user.
     *
     * @param int $userId
     * @return OA_Dll_UserInfo
     */
    public function getUser($userId)
    {
        $dataUser = $this->sendWithSession('ox.getUser', array((int) $userId));
        $oUserInfo = new OA_Dll_UserInfo();
        $oUserInfo->readDataFromArray($dataUser);

        return $oUserInfo;
    }

    /**
     * This method returns a list of users by Account ID.
     *
     * @param int $accountId
     *
     * @return array  array OA_Dll_UserInfo objects
     */
    public function getUserListByAccountId($accountId)
    {
        $dataUserList = $this->sendWithSession('ox.getUserListByAccountId', array((int) $accountId));
        $returnData = array();
        foreach ($dataUserList as $dataUser) {
            $oUserInfo = new OA_Dll_UserInfo();
            $oUserInfo->readDataFromArray($dataUser);
            $returnData[] = $oUserInfo;
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
     * @param OA_Dll_ZoneInfo $oZoneInfo
     * @return  method result
     */
    public function addZone(&$oZoneInfo)
    {
        return (int) $this->sendWithSession('ox.addZone', array(&$oZoneInfo));
    }

    /**
     * This method modifies a zone.
     *
     * @param OA_Dll_ZoneInfo $oZoneInfo
     * @return  method result
     */
    public function modifyZone(&$oZoneInfo)
    {
        return (bool) $this->sendWithSession('ox.modifyZone', array(&$oZoneInfo));
    }

    /**
     * This method returns ZoneInfo for a specified zone.
     *
     * @param int $zoneId
     * @return OA_Dll_ZoneInfo
     */
    public function getZone($zoneId)
    {
        $dataZone = $this->sendWithSession('ox.getZone', array((int) $zoneId));
        $oZoneInfo = new OA_Dll_ZoneInfo();
        $oZoneInfo->readDataFromArray($dataZone);

        return $oZoneInfo;
    }

    /**
     * This method returns a list of zones for a specified publisher.
     *
     * @param int $publisherId
     * @return array  array OA_Dll_ZoneInfo objects
     */
    public function getZoneListByPublisherId($publisherId)
    {
        $dataZoneList = $this->sendWithSession('ox.getZoneListByPublisherId', array((int) $publisherId));
        $returnData = array();
        foreach ($dataZoneList as $dataZone) {
            $oZoneInfo = new OA_Dll_ZoneInfo();
            $oZoneInfo->readDataFromArray($dataZone);
            $returnData[] = $oZoneInfo;
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
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function zoneDailyStatistics($zoneId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        $statisticsData = $this->callStatisticsMethod('ox.zoneDailyStatistics', $zoneId, $oStartDate, $oEndDate, $useManagerTimezone);

        foreach ($statisticsData as $key => $data) {
            $statisticsData[$key]['day'] = date('Y-m-d',XML_RPC_iso8601_decode(
                                            $data['day']));
        }

        return $statisticsData;
    }

    /**
     * This method returns advertiser statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function zoneAdvertiserStatistics($zoneId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.xzoneAdvertiserStatistics', $zoneId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns campaign statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function zoneCampaignStatistics($zoneId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.zoneCampaignStatistics', $zoneId, $oStartDate, $oEndDate, $useManagerTimezone);
    }

    /**
     * This method returns publisher statistics for a zone for a specified period.
     *
     * @param int $zoneId
     * @param Pear::Date $oStartDate
     * @param Pear::Date $oEndDate
     * @return array  result data
     */
    public function zoneBannerStatistics($zoneId, $oStartDate = null, $oEndDate = null, $useManagerTimezone = false)
    {
        return $this->callStatisticsMethod('ox.zoneBannerStatistics', $zoneId, $oStartDate, $oEndDate, $useManagerTimezone);
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

