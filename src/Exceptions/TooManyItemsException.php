<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 06/10/15
 * Time: 13:25
 */

namespace CultuurNet\UDB3SilexEntryAPI\Exceptions;

class TooManyItemsException extends InvalidCdbXmlException
{
    public function __construct()
    {
        parent::__construct(
            'Too many items in your messages.'
        );
    }
}
