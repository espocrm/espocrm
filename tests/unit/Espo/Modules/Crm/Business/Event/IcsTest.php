<?php

use Espo\Modules\Crm\Business\Event\Ics;

class IcsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var IcsTest
     */
    protected $_object = null;

    /**
     * @dataProvider dp_caldata
     * @throws Exception
     */
    public function testGet($attributes, $expectedOutput)
    {
        $this->_object = new Ics("//EspoCRM//EspoCRM Calendar//EN", $attributes);
        $this->assertEquals($expectedOutput, $this->_object->get());
    }

    public function dp_caldata()
    {
        return [
            "Calender requests without new Lines" =>
                [
                    require('tests/unit/testData/Ics/calender_request_oneliner/attributes.php'),
                    str_replace("DTSTAMP:20180404T231354Z", "DTSTAMP:" . date('Ymd\THis\Z', time()) ,file_get_contents("tests/unit/testData/Ics/calender_request_oneliner/output.txt"))
                ],
            "Multiline Description" =>
                [
                    require('tests/unit/testData/Ics/calender_request_multiliner/attributes.php'),
                    str_replace("DTSTAMP:20180404T231354Z", "DTSTAMP:" . date('Ymd\THis\Z', time()) ,file_get_contents("tests/unit/testData/Ics/calender_request_multiliner/output.txt"))
                ]
        ];
    }

    protected function setUp()
    {
        $this->_object = new Ics("myId");
    }
}
