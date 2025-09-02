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

    function printData($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    function testDutchLanguageWithAmericanMovie(): void
    {
        // get German version.
        global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.9';

        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/
        $id = '0120915';
        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        //$this->printData($data);

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
        $this->assertEquals('George Lucas', $data['director']);
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals('Vereinigte Staaten, Vereinigtes Königreich', $data['country']);
        $this->assertEquals('englisch, sanskrit', $data['language']);
        $this->assertNotNull($data['genres']);
        $this->assertNotEmpty($data['genres']);
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

    function testEnglishLanguageWithAmericanMovie(): void
    {
        // get English version.
        global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/
        $id = '0120915';

        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        // $this->printData($data);

        $this->assertNotContains('istv', $data);
        $this->assertEquals('Star Wars: Episode I', $data['title']);
        $this->assertEquals('The Phantom Menace', $data['subtitle']);

        # new test: origtitle. Only works if accept-language is not english for an english move.
        $this->assertNotContains('origtitle', $data);
        $this->assertEquals(1999, $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);

        $this->assertEquals('PG', $data['mpaa']);
        $this->assertEquals(136, $data['runtime']);
        $this->assertEquals('George Lucas', $data['director']);
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals('United States, United Kingdom', $data['country']);
        $this->assertEquals('english, sanskrit', $data['language']);
        $this->assertEquals('Action, Adventure, Fantasy', join(', ', $data['genres']));

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

    function testMovie2(): void
    {
        // Harold & Kumar Escape from Guantanamo Bay
        // https://www.imdb.com/title/tt0481536/

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0481536';
        $data = engineGetData($id, 'imdb', false);
        $this->assertNotEmpty($data);

        // $this->printData($data);

        $this->assertNotContains('istv', $data);
        $this->assertMatchesRegularExpression('/After being mistaken for terrorists and thrown into Guantánamo Bay, stoners Harold and Kumar escape and return to the U.S./', $data['plot']);
    }

    function testMovieWithoutImage(): void
    {
        // Can We Talk?
        // https://www.imdb.com/title/tt1486604/

        $id = '1486604';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        // There is no cover image in imdb
        $this->assertNotContains('coverurl', $data);
    }

    function testMovieMultipleDirectors(): void
    {
        // Astérix aux jeux olympiques (2008)
        // https://www.imdb.com/title/tt0463872/

        $id = '0463872';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        // multiple directors
        $this->assertEquals('Frédéric Forestier, Thomas Langmann', $data['director']);
    }

    function testMovie5(): void {
        // Role Models
        // https://www.imdb.com/title/tt0430922/
        // added for bug #3114003 - imdb.php does not fetch runtime in certain cases

        $id = '0430922';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(99, $data['runtime']);
    }

    function testMoviePlot(): void {
        // Amélie
            // https://www.imdb.com/title/tt0211915/
        // added for bug #2914077 - charset of plot

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0211915';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertMatchesRegularExpression('/Despite being caught in her imaginative world, young waitress Amelie decides to help people find happiness. Her quest to spread joy leads her on a journey during which she finds true love./', $data['plot']);
    }

    function testMovie8(): void {
        // Cars (2006)
        // https://www.imdb.com/title/tt0317219/
        // added for bug #3399788 - title & year

        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0317219';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals('Cars', $data['title']);
        $this->assertEquals(2006, $data['year']);
    }

    function testMovie9(): void {
        // Biler (2006)
        // https://www.imdb.com/title/tt0317219/
        // Test that Danish language works.

        Global $config;
        $config['http_header_accept_language'] = 'da;q=0.9';

        $id = '0317219';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals('Biler', $data['title']);
        $this->assertEquals(2006, $data['year']);
    }

    function testMovie10(): void {
        // Battleship Potemkin (1925)
        // https://www.imdb.com/title/tt0015648/

        Global $config;
        $config['http_header_accept_language'] = 'en-US;q=0.9';

        $id = '0015648';
        $data = engineGetData($id, 'imdb', false);

//        $this->printData($data);
        $this->assertNotEmpty($data);

        $this->assertEquals('Battleship Potemkin', $data['title']);
        $this->assertEquals(1925, $data['year']);
        $this->assertEquals("In the midst of the Russian Revolution of 1905, the crew of the battleship Potemkin mutiny against the brutal, tyrannical regime of the vessel's officers. The resulting street demonstration in Odessa brings on a police massacre.",
            $data['plot']);
    }

    function testMovieMissingYear(): void {
        // Smokin' Aces 2: Assassins' Ball
        // https://www.imdb.com/title/tt1319743/

        Global $config;
        $config['http_header_accept_language'] = 'en-US;q=0.9';

        $id = '1319743';
        $data = engineGetData($id, 'imdb', false);

//        $this->printData($data);
        $this->assertNotEmpty($data);

        $this->assertEquals("Smokin' Aces 2", $data['title']);
        $this->assertEquals("Assassins' Ball", $data['subtitle']);
        $this->assertEquals(2010, $data['year']);
    }

    function testMovieGenre1(): void {
        // Public Enemies (2009)
        // https://www.imdb.com/title/tt1152836/

        Global $config;
        $config['http_header_accept_language'] = 'en-US;q=0.9';

        $id = '1152836';
        $data = engineGetData($id, 'imdb', false);

//        $this->printData($data);
        $this->assertNotEmpty($data);

        $this->assertEquals('Public Enemies', $data['title']);
        $this->assertEquals(2009, $data['year']);
        $this->assertEquals('Biography, Crime, Drama', join(', ', $data['genres']));
    }
    // the main show does not have a director but a creator.
    // https://www.imdb.com/title/tt2084342/ does not have a primary country. its not uncommon for tv series.
    // https://www.imdb.com/title/tt0303461/ for multiply primary_languge tags
    // https://www.imdb.com/title/tt0971272/ does not have a language

    /**
     *  Case added for bug 1675281
     *
     *  https://sourceforge.net/tracker/?func=detail&atid=586362&aid=1675281&group_id=88349
     */
    function testSeries(): void {
        // Scrubs
        // https://imdb.com/title/tt0285403/

        $id = '0285403';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
//         $this->printData($data);

        $this->assertEquals('english, german, spanish, american sign language', $data['language']);
        $this->assertMatchesRegularExpression("/Zach Braff::Dr. John 'J.D.' Dorian \/ Mrs. Zeebee.+?::imdb:nm0103785.+?Mona Weiss::Nurse \(uncredited\), 2 episodes, 2006::imdb:nm2032293/is", $data['cast']);
        $this->assertMatchesRegularExpression('/Sacred Heart Hospital/i', $data['plot']);
    }

    function testSeriesLanguage(): void {
        // Game of Thrones - Dragonstone
        // https://www.imdb.com/title/tt5654088/ for single language from json

        $id = '5654088';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals('Game of Thrones', $data['title']);
        $this->assertEquals('Dragonstone', $data['subtitle']);
        $this->assertEquals('english', $data['language']);
    }

    function testSeriesWithMissingCountry(): void {
        // DC's Legends of Tomorrow - Shogun
        // https://www.imdb.com/title/tt5584098/ countries are found in json

        $id = '5584098';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
//        $this->printData($data);
// episode id: tt5584098
// series id:  tt4532368
        $this->assertEquals('4532368', $data['tvseries_id']);
        $this->assertEquals("DC's Legends of Tomorrow", $data['title']);
        $this->assertEquals('Shogun', $data['subtitle']);
        $this->assertEmpty($data['country']);
    }

    /**
     *  verify that the character () is chanded to '
     */
    function testCastWithControlChar(): void {
        // Focus
        // https://imdb.com/title/tt2381941/

        $id = '2381941';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        // Unicode \U0092 is 
        $this->assertDoesNotMatchRegularExpression("//", $data['cast']);
        $this->assertDoesNotMatchRegularExpression("/[\x00\x93]/u", $data['cast']);
        $this->assertMatchesRegularExpression("/Steve Kim::Liyuan's Bodyguard::imdb:nm1212115/i", $data['cast']);
    }

    /**
     *  Case added for "24" - php seems to have issues with matching large patterns...
     */
    function testSeries2(): void
    {
        // 24
        // https://imdb.com/title/tt0285331/

        $id = '0285331';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertTrue(sizeof(preg_split('/\n/', $data['cast'])) > 400);
    }

    /**
     *  Bis in die Spitzen
     */
    function testSeries3(): void
    {
        // Bis in die Spitzen
        // https://imdb.com/title/tt0461620/
        $id = '0461620';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertNotContains('plot', $data);
        $this->assertEquals('45', $data['runtime']);
        $this->assertTrue($data['rating'] >= 7);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals('Bis in die Spitzen', $data['title']);
    }

    /**
     *  Bis in die Spitzen
     */
    function testSeries3Episode(): void
    {
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Bis in die Spitzen: Folge #1.1
        // https://imdb.com/title/tt0872606/
        $id = '0872606';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertNotContains('plot', $data);
        $this->assertEquals('0', $data['runtime']);
        $this->assertNotContains('rating', $data);
        $this->assertEquals('Bis in die Spitzen', $data['title']);
        $this->assertEquals('Episode #1.1', $data['subtitle']);
    }

    function testSeriesEpisode(): void
    {
        // Star Trek TNG Episode "Q Who"
        // https://www.imdb.com/title/tt0708758/

        // get German version.
        Global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.9';

        $id = '0708758';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
//         $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals('0092455', $data['tvseries_id']);
        $this->assertEquals('Raumschiff Enterprise: Das nächste Jahrhundert', $data['title']);
        $this->assertEquals('Q Who', $data['subtitle']);
        $this->assertEquals('1989', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals('Rob Bowman', $data['director']);
        $this->assertTrue($data['rating'] >= 8);
        $this->assertTrue($data['rating'] <= 9);
        $this->assertEquals('Vereinigte Staaten', $data['country']);
        $this->assertEquals('englisch', $data['language']);
        $this->assertNotNull($data['genres']);
        $this->assertNotEmpty($data['genres']);
        $this->assertEquals('Action, Abenteuer, Drama', join(', ', $data['genres']));

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Patrick Stewart::Captain Jean-Luc Picard::imdb:nm0001772', $cast));
        $this->assertTrue(in_array("Jonathan Frakes::Commander William Thomas 'Will' Riker::imdb:nm0000408", $cast));
        $this->assertTrue(in_array('Marina Sirtis::Counselor Deanna Troi::imdb:nm0000642', $cast));
        $this->assertTrue(in_array('John de Lancie::Q (as John deLancie)::imdb:nm0209496', $cast));
        $this->assertTrue(in_array('Rob Bowman::Borg (Synchronisation) (Nicht genannt)::imdb:nm0101385', $cast));

        $this->assertTrue(sizeof($cast) > 15);
        $this->assertTrue(sizeof($cast) < 30);

        $this->assertEquals(46, $data['runtime']);

        $this->assertMatchesRegularExpression('/Q bewirbt sich als Crewmitglied, wird aber von Picard abgewiesen./', $data['plot']);
    }

    function testSeriesEpisode2(): void
    {
        // The Inspector Lynley Mysteries - Episode: Playing for the Ashes
        // https://www.imdb.com/title/tt0359476

        // get English version.
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        $id = '0359476';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals('0988820', $data['tvseries_id']);
        $this->assertEquals('The Inspector Lynley Mysteries', $data['title']);
        $this->assertEquals('Playing for the Ashes', $data['subtitle']);
        $this->assertMatchesRegularExpression('/200\d/', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals('Richard Spence', $data['director']);
        $this->assertTrue($data['rating'] >= 5);
        $this->assertTrue($data['rating'] <= 8);
        $this->assertEquals('United Kingdom', $data['country']);
        $this->assertEquals('english', $data['language']);
        $this->assertEquals( 'Crime, Drama, Mystery', join(', ', $data['genres']));

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Clare Swinburne::Gabriella Patten::imdb:nm0842673', $cast));
        $this->assertTrue(in_array('Mark Anthony Brighton::Kenneth Waring (as Mark Brighton)::imdb:nm1347940', $cast));
        $this->assertTrue(in_array('Nathaniel Parker::DI Thomas Lynley::imdb:nm0662511', $cast));
        $this->assertTrue(in_array('Andrew Clover::Hugh Patten::imdb:nm0167249', $cast));
        $this->assertTrue(in_array('Anjalee Patel::Hadiyyah::imdb:nm1347125', $cast));
        $this->assertTrue(sizeof($cast) > 12);
        $this->assertTrue(sizeof($cast) < 30);

        $this->assertMatchesRegularExpression('/Lynley seeks the help of profiler Helen Clyde when he investigates the asphyxiation death of superstar cricketer with a dysfunctional personal life./', $data['plot']);
    }

    function testSeriesEpisode3(): void {
        // Pushing Daisies - Episode 3
        // https://www.imdb.com/title/tt1039379/

        $id = '1039379';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        // was not detected as tv episode
        $this->assertEquals(1, $data['istv']);

        $this->assertEquals(42, $data['runtime']);
    }

    function testTVSeriesExactOneHourLong(): void
    {
        // Terminator: The Sarah Connor Chronicles
        // https://www.imdb.com/title/tt0851851/?ref_=tt_ov_inf

        $id = '0851851';
        $data = engineGetData($id, 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals(60, $data['runtime']);
    }

    function testGetActorUrlByName(): void {
        $url = engineGetActorUrl('Arnold Schwarzenegger', null, 'imdb');
        $this->assertEquals('https://www.imdb.com/find/?s=nm&q=Arnold+Schwarzenegger', $url);
    }

    function testGetActorUrlById(): void {
        $url = engineGetActorUrl(null, 'nm0000216', 'imdb');
        $this->assertEquals('https://www.imdb.com/name/nm0000216/', $url);
    }

    function testGetActorUrlByNameAndId(): void {
        $url = engineGetActorUrl('Arnold Schwarzenegger', 'nm0000216', 'imdb');
        $this->assertEquals('https://www.imdb.com/name/nm0000216/', $url);
    }

    function testActorImage(): void {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor('William Shatner', 'nm0000638', 'imdb', false);
        // $this->printData($data);

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorImageByName(): void {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor(null, 'nm0000638', 'imdb', false);

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorWithoutImage(): void {
        // Oscar Pearce
        // https://www.imdb.com/name/nm0668994/
        $data = engineActor('Oscar Pearce', 'nm0668994', 'imdb', false);

        // $this->printData($data);

        $this->assertEmpty($data);
    }

    function testGetSearchUrl(): void
    {
        $url = engineGetSearchUrl('Clerks 2', 'imdb');

        $this->assertEquals('https://www.imdb.com/find?s=tt&q=Clerks%202', $url);
    }

    function testSearch(): void
    {
        // get english version.
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Clerks 2
        // https://imdb.com/find?s=tt&q=clerks 2
        $data = engineSearch('Clerks 2', 'imdb');
//        $this->printData($data);

        $this->assertNotEmpty($data);
        $data = $data[0];

        $this->assertEquals('imdb:0424345', $data['id']);
        $this->assertEquals('Clerks II', $data['title']);
    }

    /**
     *  Check fur UTF-8 encoded search and aka search
     */
    function testSearch2(): void
    {
        // Das Streben nach Glück | The Pursuit of Happyness
        // https://www.imdb.com/find?s=all&q=Das+Streben+nach+Gl%FCck

        Global $config;
        $config['http_header_accept_language'] = 'de-DE,en;q=0.6';

        $data = engineSearch('Das Streben nach Glück', 'imdb', false);
        $this->assertNotEmpty($data);

        $data = $data[0];
        // $this->printData($data);

        $this->assertEquals('imdb:0454921', $data['id']);
        $this->assertMatchesRegularExpression('/Das Streben nach Glück/', $data['title']);
    }

    /**
     *  Make sure matching is correct and no HTML tags are included
     */
    function testPartialSearch(): void
    {
        // Serpico
        // https://imdb.com/find?s=all&q=serpico

        $data = engineSearch('Serpico', 'imdb');
        // $this->printData($data);
        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $t = strip_tags($item['title']);
            $this->assertEquals($item['title'], $t);
        }
    }

    function testEngineGetRecommendations(): void
    {
        // get english version.
        Global $config;
        $config['http_header_accept_language'] = 'en-US,en;q=0.9';

        // Star Wars: Episode I - The Phantom Menace
        $data = engineGetRecommendations('imdb:0120915', 8, 1980, 'imdb');
        sort($data);
//         $this->printData($data);

        $this->assertEquals(4, count($data));

        $this->assertEquals('0080684', $data[0]['id']);
        $this->assertEquals('8.7', $data[0]['rating']);
        $this->assertEquals('Star Wars: Episode V - The Empire Strikes Back', $data[0]['title']);
        $this->assertEquals('1980', $data[0]['year']);

        $this->assertEquals('0086190', $data[1]['id']);
        $this->assertEquals('8.3', $data[1]['rating']);
        $this->assertEquals('Star Wars: Episode VI - Return of the Jedi', $data[1]['title']);
        $this->assertEquals('1983', $data[1]['year']);

        $this->assertEquals('0458290', $data[2]['id']);
        $this->assertEquals('8.4', $data[2]['rating']);
        $this->assertEquals('Star Wars: The Clone Wars', $data[2]['title']);
        $this->assertEquals('2020', $data[2]['year']);
    }
}

?>
