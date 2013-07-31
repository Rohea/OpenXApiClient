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
 * @author     Tomi Saarinen <tomi.saarinen@rohea.com>
 */
namespace OpenXApiClient\Exception;

use \InvalidArgumentException;

class UnsupportedXmlRpcTypeException extends InvalidArgumentException
{
    protected $type;

    public function __construct($type, $message, $code = 0, Exception $previous = null) {
        $this->type = $type;
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    public function getType()
    {
        return $this->type;
    }
}
