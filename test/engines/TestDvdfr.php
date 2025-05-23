<?php declare(strict_types=1);
/**
 * test_dvdfr.php
 *
 * DVDfr engine test case
 *
 * @package Test
 * @author Sébastien Koechlin <seb.videodb@koocotte.org>
 * @version $Id: test_dvdfr.php,v 1.9 2013/02/02 11:38:59 andig2 Exp $
 */

require_once './core/functions.php';
require_once './engines/engines.php';

use PHPUnit\Framework\TestCase;

class TestDVDFR extends TestCase
{
    function testGetContentUrl(): void
	{
        $id = 'dvdfr:2869';
        $url = engineGetContentUrl($id, 'dvdfr');

        $this->assertEquals('https://www.dvdfr.com/api/dvd.php?id=2869', $url);
	}

    function testGetSearchUrl(): void
	{
        $title = 'Jojo Rabbit';
        $url = engineGetSearchUrl($title, 'dvdfr');

        $this->assertEquals('https://www.dvdfr.com/api/search.php?title=Jojo+Rabbit', $url);
	}

	function testMovie(): void
	{
		// Star Wars: Episode I
		// https://www.dvdfr.com/dvd/f2869_star_wars_-_episode_i_-_la_menace_fantome.html
		// https://www.dvdfr.com/dvd/dvd.php?id=2869
		$id = 'dvdfr:2869';

		$data = engineGetData($id, 'dvdfr', false);

		$this->assertNotCount(0, $data);

//		var_dump($data);

		$this->assertEquals('Star Wars - Episode I', $data['title']);
		$this->assertEquals('La Menace fantôme', $data['subtitle']);
		$this->assertEquals('Star Wars: Episode I - The Phantom Menace', $data['origtitle']);
		$this->assertArrayNotHasKey('language', $data);
		$this->assertEquals(1999, $data['year']);
		$this->assertEquals('https://www.dvdfr.com/images/dvd/covers/200x280/0cc3a79c3a4b3c5f60accb2b611e2043/2869/old-star_wars_1.0.jpg', $data['coverurl']);
		$this->assertEquals(130, $data['runtime']);
		$this->assertEquals('4,53', $data['rating']);
		$this->assertEquals('George Lucas', $data['director']);
		$this->assertEquals('États-Unis', $data['country']);
		$this->assertArrayNotHasKey('istv', $data);

		$this->assertCount(2, $data['genres']);
		$this->assertContains('Sci-Fi', $data['genres']);
		$this->assertContains('Adventure', $data['genres']);

		$this->assertStringContainsString('Liam Neeson::::dvdfr:10977', $data['cast']);
		$this->assertStringContainsString('Ewan McGregor::::dvdfr:9192', $data['cast']);
		$this->assertStringContainsString('Natalie Portman::::dvdfr:6177', $data['cast']);

		$this->assertMatchesRegularExpression("/Bloqués sur la planète Tatooine après avoir secouru la Reine Amidala \(Natalie Portman\), l'apprenti Jedi Obi-Wan Kenobi \(Ewan McGregor\)/", $data['plot']);
	}

	function testTvSerie(): void
	{
		// Terminator - The Sarah Connor Chronicles - Saison 1 (2008) - Blu-ray
		// https://www.dvdfr.com/dvd/f151323-terminator-the-sarah-connor-chronicles-saison-1.html
		// https://www.dvdfr.com/dvd/dvd.php?id=151323
		$id = 'dvdfr:151323';

		$data = engineGetData($id, 'dvdfr', false);

		$this->assertNotCount(0, $data);

//		var_dump($data);

		$this->assertEquals('Terminator', $data['title']);
		$this->assertEquals('The Sarah Connor Chronicles - Saison 1', $data['subtitle']);
		$this->assertEquals('Terminator: The Sarah Connor Chronicles', $data['origtitle']);
		$this->assertArrayNotHasKey('language', $data);
		$this->assertEquals(2008, $data['year']);
		$this->assertEquals('https://www.dvdfr.com/images/dvd/covers/200x280/c336f6fca8cf8bab696dcdef453a80f0/151323/old-terminator_the_sarah_connor_chronicles_saison_1_br.0.jpg', $data['coverurl']);
		$this->assertEquals(487, $data['runtime']);
		$this->assertEquals('4,00', $data['rating']);
		$this->assertStringContainsString('David Nutter', $data['director']);
		$this->assertEquals('États-Unis', $data['country']);
		$this->assertEquals(1, $data['istv']);

		$this->assertCount(1, $data['genres']);
		$this->assertContains('Sci-Fi', $data['genres']);

		$this->assertStringContainsString('Thomas Dekker::::dvdfr:47911', $data['cast']);
		$this->assertStringContainsString('Summer Glau::::dvdfr:70828', $data['cast']);
		$this->assertStringContainsString('Lena Headey::::dvdfr:13572', $data['cast']);

		$this->assertMatchesRegularExpression("/Après être venus à bout du Terminator, Sarah Connor et son fils John/", $data['plot']);
	}

	function testMovieWithSubTitle(): void
	{
		// Mobile Fighter G Gundam
		// https://www.dvdfr.com/dvd/f171716-mobile-fighter-g-gundam-premiere-partie.html
		// https://www.dvdfr.com/dvd/dvd.php?id=171716
		$id = 'dvdfr:171716';

		$data = engineGetData($id, 'dvdfr', false);

		$this->assertNotCount(0, $data);

//		var_dump($data);

		$this->assertEquals('Mobile Fighter G Gundam', $data['title']);
		$this->assertEquals('Première partie', $data['subtitle']);
		$this->assertEquals('Kidô butôden ji Gandamu', $data['origtitle']);
		$this->assertEquals(1994, $data['year']);
		$this->assertEquals(625, $data['runtime']);
	}

	function testMovieWithMultipleCounties(): void
	{
		// Jojo Rabit
		// https://www.dvdfr.com/dvd/f167167-jojo-rabbit.html
		// https://www.dvdfr.com/api/dvd.php?id=f167167
		$id = 'dvdfr:167167';
		$data = engineGetData($id, 'dvdfr', false);

		$this->assertNotCount(0, $data);
//		var_dump($data);

		$this->assertEquals('Jojo Rabbit', $data['title']);
		$this->assertEmpty($data['subtitle']);
		$this->assertEmpty($data['origtitle']);
		$this->assertArrayNotHasKey('language', $data);
		$this->assertEmpty($data['rating']);
		$this->assertCount(1, $data['genres']);
		$this->assertContains('Comedy', $data['genres']);
		$this->assertEquals('Nouvelle-Zélande, États-Unis, République tchèque', $data['country']);
	}

	function testSearchStarWars(): void
	{
		// Star Wars
		// http://www.dvdfr.com/api/search.php?title=star%20wars
		// Result: XML with many results
		$search = 'star wars';
		$data = engineSearch($search, 'dvdfr');

//		var_dump($data);

		$obj = array('dvdfr:83682', 'dvdfr:90454', 'dvdfr:163500', 'dvdfr:163501', 'dvdfr:163503', 'dvdfr:300695');
		$this->assertTrue(sizeof($data) > 6);

		// Search each movie in result
		foreach($obj as $search) {
			$found = false;
			foreach ($data as $movie) {
				if ($search == $movie['id']) {
				    $found = true;
                }
			}
			$this->assertTrue($found);
		}
	}

	function testGetActorUrl(): void
	{
	    $name = 'Scarlett Johansson';
	    $id = 'dvdfr:4028';
	    $url = engineGetActorUrl($name, $id, 'dvdfr');

        $this->assertEquals('https://www.dvdfr.com/stars/s4028-scarlett-johansson.html', $url);
	}

	function testEngineActor(): void
	{
	    $name = 'Scarlett Johansson';
	    $id = 'dvdfr:4028';
	    $data = engineActor($name, $id, 'dvdfr');

        $this->assertEquals('/stars/s4028-scarlett-johansson.html', $data[0][0]);
        $this->assertEquals("https://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Scarlett_Johansson_by_Gage_Skidmore_2_%28cropped%29_%28cropped%29.jpg/600px-Scarlett%20Johansson%20by%20Gage%20Skidmore%202%20%28cropped%29%20%28cropped%29.jpg", $data[0][1]);
	}

	function testEngineActorWithoutImage(): void
	{
	    $name = 'Tintrinai Thikhasuk';
	    $id = 'dvdfr:201155';
	    $data = engineActor($name, $id, 'dvdfr');

        $this->assertEquals('/stars/s201155-tintrinai-thikhasuk.html', $data[0][0]);
        $this->assertEmpty($data[0][1]);
	}
}

?>
