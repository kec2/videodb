<?php declare(strict_types=1);
/**
 * Allocine engine test case
 *
 * @package Test/engines
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestAllocine extends TestCase
{

	function testMovie(): void
	{
		// Star Wars: Episode I
		// https://www.allocine.fr/film/fichefilm_gen_cfilm=20754.html
		$id = '20754';

		$data = engineGetData($id, 'allocine', false);
		// $this->assertNoErrors();
		$this->assertNotEmpty($data);

//        echo '<pre>';
//        dump($data);
//        echo '</pre>';

		$this->assertEquals('allocine:20754', $data['id']);
        $this->assertEquals('Star Wars : Episode I', $data['title']);
		$this->assertEquals('La Menace fantôme de George Lucas', $data['subtitle']);
		$this->assertEquals('Star Wars: Episode I - The Phantom Menace', $data['origtitle']);
		$this->assertEquals(1999, $data['year']);
		$this->assertEquals('https://fr.web.img6.acsta.net/c_310_420/pictures/24/02/19/14/31/0585421.jpg', $data['coverurl']);
		$this->assertEquals(133, $data['runtime']);
		$this->assertEquals('George Lucas', $data['director']);
		$this->assertTrue($data['rating'] >= 5);
		$this->assertTrue($data['rating'] <= 8);
		$this->assertEquals('USA', $data['country']);
		$this->assertEquals('English', $data['language']);
		$this->assertCount(3, $data['genres']);
		$this->assertContains('Adventure', $data['genres']);
		$this->assertContains('Fantasy', $data['genres']);
		$this->assertContains('Sci-Fi', $data['genres']);
		$this->assertMatchesRegularExpression('/Ewan McGregor::Obi-Wan Kenobi::allocine:17043/si', $data['cast']);

      // Number of cast is 79 but only 40 is fetched and Friday 'Lis' Wilson is no. 79
//        $this->assertMatchesRegularExpression('/Friday \'Liz\' Wilson::Eirtaé::allocine:407183/si', $data['cast']);

        $this->assertMatchesRegularExpression('/Avant de devenir un célèbre chevalier Jedi/', $data['plot']);
	}

	function testMovieMultipleDirectors(): void
    {
        // Astérix aux jeux olympiques (2008)
        // https://www.allocine.fr/film/fichefilm_gen_cfilm=61259.html

        $id = '61259';
        $data = engineGetData($id, 'allocine');

        $this->assertNotEmpty($data);
//        echo '<pre>';
//        dump($data);
//        echo '</pre>';

        // multiple directors
        $this->assertEquals('Thomas Langmann, Frédéric Forestier', $data['director']);

        $this->assertEquals('French, Portuguese', $data['language']);
    }

	function testMovie2(): void
	{
		// Star Wars: Episode III
		// https://www.allocine.fr/film/fichefilm_gen_cfilm=40623.html
		$id = "40623";

		$data = engineGetData($id, 'allocine');
		#$this->assertNoErrors();
		$this->assertTrue(sizeof($data) > 0);

//        echo '<pre>';
//        dump($data);
//        echo '</pre>';

		$this->assertEquals('allocine:40623', $data['id']);
        $this->assertEquals('Star Wars : Episode III', $data['title']);
		$this->assertEquals('La Revanche des Sith de George Lucas', $data['subtitle']);
		$this->assertEquals(2005, $data['year']);
		$this->assertEquals("https://fr.web.img6.acsta.net/c_310_420/medias/nmedia/18/35/53/23/18423997.jpg", $data['coverurl']);
		$this->assertEquals(140, $data['runtime']);
		$this->assertEquals('George Lucas', $data['director']);
		$this->assertTrue($data['rating'] >= 8);
		$this->assertTrue($data['rating'] <= 9);
		$this->assertEquals('USA', $data['country']);
		$this->assertEquals('English', $data['language']);

		$this->assertCount(3, $data['genres']);
		$this->assertContains('Action', $data['genres']);
		$this->assertContains('Adventure', $data['genres']);
		$this->assertContains('Sci-Fi', $data['genres']);

		$this->assertMatchesRegularExpression('/Ewan McGregor::Obi-Wan Kenobi::allocine:17043/si', $data['cast']);

        $this->assertMatchesRegularExpression('/La Guerre des Clones fait rage. Une franche hostilité oppose désormais/', $data['plot']);
//        $this->assertMatchesRegularExpression('/Tourné/si', $data['comment']);
	}

    // check search
    function testSearch(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');

        // Clerks 2
        $data = allocineSearch('Clerks 2');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) > 0);

        // echo '<pre>';
        // dump($data);
        // echo '</pre>';

        $data = $data[0];

        $this->assertEquals($data['id'], 'allocine:57999');
        $this->assertEquals($data['title'], 'Clerks II');
    }

    // check for utf8 search
    function testSearch2(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');

        // Cette femme là
        $data = allocineSearch('cette femme là');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) > 0);

        $data = $data[0];

        // echo '<pre>';
        // dump($data);
        // echo '</pre>';

        $this->assertEquals($data['id'], 'allocine:51397');
        $this->assertEquals($data['title'], 'Cette femme-là');
    }

    // check for partial search
    function testSearch3(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');

        // Chacun cherche son chat
        $data = allocineSearch('chacun cherche son');
        #$this->assertNoErrors();
        $this->assertTrue(sizeof($data) > 0);

        $data = $data[0];

        // echo '<pre>';
        // dump($data);
        // echo '</pre>';

        $this->assertEquals($data['id'], 'allocine:14363');
        $this->assertEquals($data['title'], 'Chacun cherche son chat');
    }
}
?>
