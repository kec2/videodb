<?php
/**
 * test_amazon.php
 *
 * amazon.de engine test case
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 * @version $Id: test_amazonaws.php,v 1.4 2013/02/02 11:38:59 andig2 Exp $
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestAmazonAWS extends TestCase
{

    protected function setUp(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');
    }

    function testData()
    {
        // Star Wars: Episode 1
        // http://www.amazon.de/Star-Wars-Episode-Bedrohung-Einzel-DVD/dp/B0009HBEHW/ref=sr_1_2/303-6664842-9566627?ie=UTF8&s=dvd&qid=1185389090&sr=1-2
        $id = 'B0009HBEHW';

        $data = engineGetData($id, 'amazonaws');
        #$this->assertNoErrors();

        $this->assertTrue(sizeof($data) > 0);

//        dump($data);

        $this->assertMatchesRegularExpression('/Star Wars/', $data['title']);
//        $this->assertEquals($data['subtitle'], 'Die dunkle Bedrohung (Einzel-DVD)');
        $this->assertMatchesRegularExpression('#http://.+.images\-amazon.com/images/#', $data['coverurl']);
        $this->assertEquals($data['director'], 'George Lucas');
        $this->assertEquals($data['language'], 'deutsch, englisch');
        $this->assertEquals($data['year'], 2001);
        $this->assertTrue($data['runtime'] > 100);
        $this->assertTrue($data['rating'] >= 6);
//        [genres] =>
        $this->assertMatchesRegularExpression('/Ewan McGregor/', $data['cast']);
        $this->assertMatchesRegularExpression('/Naboo/', $data['plot']);
    }

    function testSearch()
    {
        $id = 'Star Wars: Episode 1';

        $data = engineSearch($id, 'amazonaws', 'DVD', 'com');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) > 0);

//        dump($data);
    }
}

?>
