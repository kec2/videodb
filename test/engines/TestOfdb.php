<?php
/**
 * test_ofdb.php
 *
 * OFDB engine test case
 *
 * @package Test
 * @author  Chinamann <chinamann@users.sourceforge.net>
 * @version $Id: test_ofdb.php,v 1.8 2013/02/08 15:10:58 andig2 Exp $
 */

require_once './core/functions.php';
require_once './engines/engines.php';

use PHPUnit\Framework\TestCase;

class TestOFDB extends TestCase
{

    protected function setUp(): void
    {
        $this->markTestIncomplete('This engine is broken and tests has been disabled until it is fixed');
    }

	function testMovie(): void
	{
		// Star Wars: Episode I - Die dunkle Bedrohung / Star Wars: Episode I - The Phantom Menace (1999)
		$id = '3129,Star-Wars-Episode-I---Die-dunkle-Bedrohung';
		$data = engineGetData($id, 'ofdb');
		#dump($data);
		$this->assertTrue(sizeof($data) > 0);

		$this->assertEquals($data['title'], 'Star Wars: Episode I');
		$this->assertEquals($data['subtitle'], 'Die dunkle Bedrohung');
		$this->assertEquals($data['orgtitle'], 'Star Wars: Episode I - The Phantom Menace');
		$this->assertEquals($data['year'], 1999);
		$this->assertMatchesRegularExpression('#/film/3/3129.jpg#', $data['coverurl']);
		$this->assertEquals($data['director'], 'George Lucas');
		$this->assertTrue($data['rating'] >= 6);
		$this->assertTrue($data['rating'] <= 8);
		$this->assertEquals($data['country'], 'USA');
		$this->assertEquals($data['runtime'], 130);
		$this->assertEquals($data['fsk'], '6');
		$this->assertEquals($data['language'], 'german, english');
		$this->assertEquals(join(',', $data['genres']), 'Action,Sci-Fi');

		$this->assertMatchesRegularExpression('/Liam Neeson/s', $data['cast']);
		$this->assertMatchesRegularExpression('/Ewan McGregor/s', $data['cast']);
		$this->assertMatchesRegularExpression('/nimmt die Legende ihren Anfang/', $data['plot']);
/*
Array ( [title] => Star Wars: Episode I [subtitle] => Die dunkle Bedrohung [orgtitle] => Star Wars: Episode I - The Phantom Menace [country] => USA [year] => 1999 [coverurl] => http://www.dvd-palace.de/showcover.php?MTIwMjUyNTQyMXwzNzA5#pic.jpg [runtime] => 130 [director] => George Lucas [rating] => 7 [language] => german, english [plot] => Rund dreißig Jahre vor den Ereignissen in Star Wars - Krieg der Sterne ist in der Galaktischen Republik ein Streit über die Besteuerung der Handelsrouten ausgebrochen. Der friedliche Planet Naboo mit seiner Königin Amidala (Natalie Portman) wird von der geldgierigen Handelsföderation angegriffen. Als die Jedi-Ritter Qui-Gon Jinn (Liam Neeson) und Obi-Wan Kenobi (Ewan McGregor) im Auftrag des Obersten Kanzlers der Republik verhandeln wollen, entkommen sie nur knapp einem Attentat. Bei der gemeinsamen Flucht mit Königin Amidala müssen sie auf dem Wüstenplaneten Tatooine notlanden. Hier hilft ihnen der Sklavenjunge Anakin Skywalker (Jake Lloyd), das Raumschiff wieder flott zu machen. Anakin gewinnt ein lebensgefährliches Podrennen und weckt in Qui-Gon die Überzeugung, daß er ausersehen ist, das Gleichgewicht der Macht wiederherzustellen. Als es Königin Amidala in Coruscant, der Hauptstadt der Republik, nicht gelingt, den Senat gegen die Handelsföderation zu mobilisieren, kehrt sie mit den Jedi-Rittern und Anakin nach Naboo zurück, um den Kampf allein fortzusetzen. Eine gewaltige Schlacht beginnt... [fsk] => 6 [genres] => Array ( [0] => Sci-Fi ) [cast] => Liam Neeson Ewan McGregor Natalie Portman Jake Lloyd Pernilla August Frank Oz Ian McDiarmid Oliver Ford Davies Hugh Quarshie Ahmed Best Anthony Daniels Kenny Baker Terence Stamp Brian Blessed Andrew Secombe Ray Park Lewis Macleod Steven Spiers Silas Carson Ralph Brown Celia Imrie Benedict Taylor Karol Cristina da Silva Clarence Smith Samuel L. Jackson Dominic West Liz Wilson Candice Orwell Sofia Coppola Keira Knightley Bronagh Gallagher John Fensom Greg Proops Scott Capurro Margaret Towner Dhruv Chanchani Oliver Walpole Jenna Green Megan Udall Hassani Shapi Gin Clarke Khan Bonfils Michelle Taylor Michaela Cottrell Dipika O'Neill Joti Phil Eason Mark Coulier Katherine Smee Donald Austen David Greenaway Lindsay Duncan Peter Serafinowicz James Taylor Chris Sanders Toby Longworth Marc Silk Tyger Amy Allen Jerome Blake Michonne Bourriague Ben Burtt Doug Chiang Rob Coleman Roman Coppola Warwick Davis C. Michael Easton John Ellis Ira Feiedman Joss Gower Ray Griffiths Nathan Hamill Nifa Hindes Nishan Hindes John Knoll Madison Lloyd Dan Madsen Rick McCallum Alan Ruscoe Steve Sansweet Jeff Shay Christian Simpson Paul Martin Smith Danny Wagner Dwayne Williams Matthew Wood Bob Woods [comment] => 16:9 (2.35:1) anamorph )
*/
	}

    function testMovie2(): void
    {
        // Boogie Nights
        // http://www.ofdb.de/film/1545,Boogie-Nights
        $id = '1545';
        $data = engineGetData($id, 'ofdb');
        # dump($data);
        $this->assertTrue(sizeof($data) > 0);

        $this->assertEquals($data['imdbID'], 'ofdb:1545-210858');
        $this->assertMatchesRegularExpression('/Luis Guzmán/s', $data['cast']);
    }

    function testSearch(): void
    {
        // Clerks 2
        // http://www.ofdb.de/film/102676,Clerks-2---Die-Abh%C3%A4nger
        $data = engineSearch('Clerks 2', 'ofdb');
#        dump($data);

        $this->assertTrue(sizeof($data) > 3);
        $data = $data[4];

        $this->assertEquals($data['imdbID'], 'ofdb:102676');
        $this->assertEquals($data['title'], 'Clerks 2');
        $this->assertMatchesRegularExpression('/Die Abhänger/', $data['subtitle']);
    }
}

?>
