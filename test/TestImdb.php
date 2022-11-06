<?php
/**
 * test_imdb.php
 *
 * IMDB engine test case
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 * @version $Id: test_imdb.php,v 1.32 2013/03/01 09:19:04 andig2 Exp $
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestIMDb extends TestCase
{

    function testDutchLanguageWithAmericanMovie()
    {
        // get German version.
        global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.9';

        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/
        $id = '0120915';
        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertNotContains('istv', $data);
        $this->assertEquals('Star Wars: Episode I', $data['title']);
        $this->assertEquals('Die dunkle Bedrohung', $data['subtitle']);

        # new test: origtitle. Only works if accept-language is not english for an english move.
        $this->assertEquals('Star Wars: Episode I - The Phantom Menace', $data['origtitle']);
        $this->assertEquals(1999, $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);

        # For non-english movies it seams to be a number
        $this->assertEquals('6', $data['mpaa']);
        $this->assertEquals(136, $data['runtime']);
        $this->assertTrue($data['runtime'] >= 133 && $data['runtime'] <= 136);
        $this->assertEquals('George Lucas', $data['director']);
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals('Vereinigte Staaten', $data['country']);
        $this->assertEquals('englisch, sanskrit', $data['language']);
        $this->assertEquals('Action,Abenteuer,Fantasy', join(',', $data['genres']));

        # cast tests changed to be independent of order
        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Liam Neeson::Qui-Gon Jinn::imdb:nm0000553', $cast));
        $this->assertTrue(in_array('Ewan McGregor::Obi-Wan Kenobi::imdb:nm0000191', $cast));
        $this->assertTrue(in_array('Natalie Portman::Queen Amidala / Padmé::imdb:nm0000204', $cast));
        $this->assertTrue(in_array('Anthony Daniels::C-3PO (voice)::imdb:nm0000355', $cast));
        $this->assertTrue(in_array('Kenny Baker::R2-D2::imdb:nm0048652', $cast));
        $this->assertTrue(sizeof($cast) > 90);

        $this->assertMatchesRegularExpression('/Zwei Jedi-Ritter entkommen einer feindlichen Blockade auf der Suche nach Verbündeten/', $data['plot']);
    }

    function testEnglishLanguageWithAmericanMovie()
    {
        // get English version.
        global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/
        $id = '0120915';
        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertNotContains('istv', $data);
        $this->assertEquals($data['title'], 'Star Wars: Episode I');
        $this->assertEquals($data['subtitle'], 'The Phantom Menace');

        # new test: origtitle. Only works if accept-language is not english for an english move.
        $this->assertNotContains('origtitle', $data);
        $this->assertEquals($data['year'], 1999);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);

        $this->assertEquals($data['mpaa'], 'PG');
        $this->assertEquals($data['runtime'], 136);
        $this->assertTrue($data['runtime'] >= 133 && $data['runtime'] <= 136);
        $this->assertEquals($data['director'], 'George Lucas');
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals($data['country'], 'United States');
        $this->assertEquals($data['language'], 'english, sanskrit');
        $this->assertEquals(join(',', $data['genres']), 'Action,Adventure,Fantasy');

        # cast tests changed to be independent of order
        $cast = explode("\n", $data['cast']);
        $this->assertTrue(in_array('Liam Neeson::Qui-Gon Jinn::imdb:nm0000553', $cast));
        $this->assertTrue(in_array('Ewan McGregor::Obi-Wan Kenobi::imdb:nm0000191', $cast));
        $this->assertTrue(in_array('Natalie Portman::Queen Amidala / Padmé::imdb:nm0000204', $cast));
        $this->assertTrue(in_array('Anthony Daniels::C-3PO (voice)::imdb:nm0000355', $cast));
        $this->assertTrue(in_array('Kenny Baker::R2-D2::imdb:nm0048652', $cast));
        $this->assertTrue(sizeof($cast) > 90);

        $this->assertMatchesRegularExpression('/Two Jedi escape a hostile blockade to find allies and come across a young boy who may bring balance to the Force/', $data['plot']);
    }

    function testMovie2()
    {
        // Harold & Kumar Escape from Guantanamo Bay
        // https://www.imdb.com/title/tt0481536/

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0481536';
        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertNotContains('istv', $data);
        $this->assertMatchesRegularExpression('/After being mistaken for terrorists and thrown into Guantánamo Bay, stoners Harold and Kumar escape and return to the U.S./', $data['plot']);
    }

    function testMovieWithoutImage()
    {
        // Can We Talk?
        // https://www.imdb.com/title/tt1486604/

        $id = '1486604';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        // There is no cover image in imdb
        $this->assertNotContains('coverurl', $data);
    }

    function testMovieMultipleDirectors()
    {
        // Astérix aux jeux olympiques (2008)
        // https://www.imdb.com/title/tt0463872/

        $id = '0463872';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        // multiple directors
        $this->assertEquals($data['director'], 'Frédéric Forestier, Thomas Langmann');
    }

    function testMovie5() {
        // Role Models
        // https://www.imdb.com/title/tt0430922/
        // added for bug #3114003 - imdb.php does not fetch runtime in certain cases

        $id = '0430922';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertTrue($data['runtime'] >= 99 && $data['runtime'] <= 101);
    }

    function testMoviePlot() {
        // Amélie
        // https://www.imdb.com/title/tt0211915/
        // added for bug #2914077 - charset of plot

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0211915';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertMatchesRegularExpression('/Amélie is an innocent and naive girl/', $data['plot']);
    }

    function testMovie8() {
        // Cars (2006)
        // https://www.imdb.com/title/tt0317219/
        // added for bug #3399788 - title & year

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0317219';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['title'],'Cars');
        $this->assertEquals($data['year'], 2006);
    }

    function testMovie9() {
        // Biler (2006)
        // https://www.imdb.com/title/tt0317219/
        // Test that Danish language works.

        Global $config;
        $config['http_header_accept_language'] = 'da;q=0.9';

        $id = '0317219';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['title'],'Biler');
        $this->assertEquals($data['year'], 2006);
    }

    /**
     * Case added for bug 1675281
     *
     * https://sourceforge.net/tracker/?func=detail&atid=586362&aid=1675281&group_id=88349
     */
    function testSeries() {
        // Scrubs
        // https://imdb.com/title/tt0285403/

        $id = '0285403';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertMatchesRegularExpression("/Zach Braff::Dr. John 'J.D.' Dorian.+?::imdb:nm0103785.+?Mona Weiss::Nurse \(uncredited\) .+?::imdb:nm2032293/is", $data['cast']);
        $this->assertMatchesRegularExpression('/Sacred Heart Hospital/i', $data['plot']);
    }

    /**
     * Case added for "24" - php seems to have issues with matching large patterns...
     */
    function testSeries2()
    {
        // 24
        // https://imdb.com/title/tt0285331/

        $id = '0285331';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertTrue(sizeof(preg_split('/\n/', $data['cast'])) > 400);
    }

    /**
     * Bis in die Spitzen
     */
    function testSeries3()
    {
        // Bis in die Spitzen
        // https://imdb.com/title/tt0461620/
        $id = '0461620';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['istv'], 1);
        $this->assertNotContains('plot', $data);
        $this->assertEquals($data['runtime'], '45');
        $this->assertTrue($data['rating'] >= 7);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals($data['title'], 'Bis in die Spitzen');
    }

    /**
     * Bis in die Spitzen
     */
    function testSeries3Episode()
    {
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Bis in die Spitzen: Folge #1.1
        // https://imdb.com/title/tt0872606/
        $id = '0872606';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['istv'], 1);
        $this->assertNotContains('plot', $data);
        $this->assertEquals($data['runtime'], '45');
        $this->assertNotContains('rating', $data);
        $this->assertEquals($data['title'], 'Bis in die Spitzen');
        $this->assertEquals($data['subtitle'], 'Episode #1.1');
    }

    function testSeriesEpisode()
    {
        // Star Trek TNG Episode "Q Who"
        // https://www.imdb.com/title/tt0708758/

        // get German version.
        Global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.9';

        $id = '0708758';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['istv'], 1);
        $this->assertEquals($data['tvseries_id'], '0092455');
        $this->assertMatchesRegularExpression('/Raumschiff Enterprise: Das nächste Jahrhundert/', $data['title']);
        $this->assertEquals($data['subtitle'], 'Q Who');
        $this->assertMatchesRegularExpression('/19\d\d/', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals($data['director'], 'Rob Bowman');
        $this->assertTrue($data['rating'] >= 8);
        $this->assertTrue($data['rating'] <= 9);
        $this->assertEquals($data['country'], 'Vereinigte Staaten');
        $this->assertEquals($data['language'], 'englisch');
        $this->assertEquals(join(',', $data['genres']), 'Action,Abenteuer,Science-Fiction');

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Patrick Stewart::Capt. Jean-Luc Picard::imdb:nm0001772', $cast));
        $this->assertTrue(in_array('Jonathan Frakes::Cmdr. William Riker::imdb:nm0000408', $cast));
        $this->assertTrue(in_array('Marina Sirtis::Counselor Deanna Troi::imdb:nm0000642', $cast));
        $this->assertTrue(in_array('John de Lancie::Q (as John deLancie)::imdb:nm0209496', $cast));
        $this->assertTrue(in_array('Rob Bowman::Borg (voice) (uncredited)::imdb:nm0101385', $cast));

        $this->assertTrue(sizeof($cast) > 15);
        $this->assertTrue(sizeof($cast) < 30);

        $this->assertTrue($data['runtime'] >= 40);
        $this->assertTrue($data['runtime'] <= 50);

        $this->assertMatchesRegularExpression('/Q bewirbt sich als Crewmitglied, wird aber von Picard abgewiesen./', $data['plot']);
    }

    function testSeriesEpisode2()
    {
        // The Inspector Lynley Mysteries - Episode: Playing for the Ashes
        // https://www.imdb.com/title/tt0359476

        // get English version.
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';
        
        $id = '0359476';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['istv'], 1);
        $this->assertEquals($data['tvseries_id'], '0988820');
        $this->assertMatchesRegularExpression('/Inspector Lynley/', $data['title']);
        $this->assertEquals($data['subtitle'], 'Playing for the Ashes');
        $this->assertMatchesRegularExpression('/200\d/', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals($data['director'], 'Richard Spence');
        $this->assertTrue($data['rating'] >= 5);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals($data['country'], 'United Kingdom');
        $this->assertEquals($data['language'], 'english');
        $this->assertEquals(join(',', $data['genres']), 'Crime,Drama,Mystery');

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Clare Swinburne::Gabriella Patten::imdb:nm0842673', $cast));
        $this->assertTrue(in_array('Mark Anthony Brighton::Kenneth Waring (as Mark Brighton)::imdb:nm1347940', $cast));
        $this->assertTrue(in_array('Nathaniel Parker::Thomas Lynley::imdb:nm0662511', $cast));
        $this->assertTrue(in_array('Andrew Clover::Hugh Patten::imdb:nm0167249', $cast));
        $this->assertTrue(in_array('Anjalee Patel::Hadiyyah::imdb:nm1347125', $cast));
        $this->assertTrue(sizeof($cast) > 12);
        $this->assertTrue(sizeof($cast) < 30);

        $this->assertMatchesRegularExpression('/Lynley seeks the help of profiler Helen Clyde when he investigates the asphyxiation death of superstar cricketer with a dysfunctional personal life./', $data['plot']);
    }

    function testSeriesEpisode3() {
        // Pushing Daisies - Episode 3
        // https://www.imdb.com/title/tt1039379/

        $id = '1039379';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        // was not detected as tv episode
        $this->assertEquals($data['istv'], 1);

        $this->assertTrue($data['runtime'] >= 40);
        $this->assertTrue($data['runtime'] <= 50);
    }

    function testTVSeriesExactOneHourLong()
    {
        // Terminator: The Sarah Connor Chronicles
        // https://www.imdb.com/title/tt0851851/?ref_=tt_ov_inf

        $id = '0851851';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['istv'], 1);
        $this->assertEquals($data['runtime'], 60);
    }

    function testActorImage() {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor('William Shatner', 'nm0000638', 'imdb', false);
//         echo '<pre>';dump($data);echo '</pre>';

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorImageByName() {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor(null, 'nm0000638', 'imdb', false);

        $this->assertNotEmpty($data);
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorWithoutImage() {
        // Oscar Pearce
        // https://www.imdb.com/name/nm0668994/
        $data = engineActor('Oscar Pearce', 'nm0668994', 'imdb', false);

        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEmpty($data);
    }

    /**
     * https://sourceforge.net/tracker/?func=detail&atid=586362&aid=1675281&group_id=88349
     */
    function testSearch()
    {
        // Clerks 2
        // https://imdb.com/find?q=clerks 2
        $data = engineSearch('Clerks 2', 'imdb');
        $this->assertNotEmpty($data);

        $data = $data[0];
        // echo '<pre>';dump($data);echo '</pre>';

        $this->assertEquals($data['id'], 'imdb:0424345');
        $this->assertEquals($data['title'], 'Clerks II');
    }

    /**
     * Check fur UTF-8 encoded search and aka search
     */
    function testSearch2()
    {
        // Das Streben nach Glück | The Pursuit of Happyness
        // https://www.imdb.com/find?s=all&q=Das+Streben+nach+Gl%FCck

        Global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.6';
        
        $data = engineSearch('Das Streben nach Glück', 'imdb', false);
        $this->assertNotEmpty($data);

        $data = $data[0];
//        echo("<pre>");dump($data);echo("</pre>");

        $this->assertEquals($data['id'], 'imdb:0454921');
        $this->assertMatchesRegularExpression('/Das Streben nach Glück/', $data['title']);
    }

    /**
     * Make sure matching is correct and no HTML tags are included
     */
    function testPartialSearch()
    {
        // Serpico
        // https://imdb.com/find?s=all&q=serpico
        
        $data = engineSearch('Serpico', 'imdb');
        // echo("<pre>");dump($data);echo("</pre>");

        foreach ($data as $item)
        {
            $t = strip_tags($item['title']);
            $this->assertTrue($item['title'] == $t);
        }
    }
}

?>
