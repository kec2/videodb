<?php
/**
 * test_google.php
 *
 * google.com images engine test case
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 * @version $Id: test_google.php,v 1.5 2013/02/02 11:38:59 andig2 Exp $
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestGoogle extends TestCase
{

    protected function setUp(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');
    }

    function testSearch()
    {
        // http://images.google.com/images?q=terminator
        $id = 'Terminator';
        
        $data = engineSearch($id, 'google');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) >= 16);
        
#       dump($data);
    }
    
    function testSearch2()
    {
        // http://images.google.com/images?q=Out+of+Time
        $id = 'Out of Time';
        
        $data = engineSearch($id, 'google');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) >= 16);
        
#       dump($data);
    }
}

?>
