<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 02/10/15
 * Time: 14:53
 */

namespace CultuurNet\UDB3SilexEntryAPI\Event\Commands;

use CultuurNet\UDB3SilexEntryAPI\EventXmlString;
use ValueObjects\String\String;

/**
 * Provides a command to add an event from CdbXml.
 */
class AddEventFromCdbXml
{
    /**
     * @var EventXmlString
     */
    protected $xml;

    /**
     * @var String|String
     */
    protected $eventId;

    /**
     * @param String $eventId
     * @param EventXmlString $xml
     */
    public function __construct(String $eventId, EventXmlString $xml)
    {
        $this->eventId = $eventId;
        $this->xml = $xml;
    }

    /**
     * @return EventXmlString
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * @return String
     */
    public function getEventId()
    {
        return $this->eventId;
    }
}
