<?php
/**
 *
 * IMDB API engine test case
 *
 * @package Test
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestIMDbApi extends TestCase
{
    private static string $origImdbApiLanguage;

    protected function setUp(): void
    {
        // use english as default language.
        global $config;
        $config['imdbApiLanguage'] = 'en';
    }

    public static function setUpBeforeClass(): void
    {
        global $config;
        self::$origImdbApiLanguage = $config['imdbApiLanguage'] ?? 'en';
    }

    public static function tearDownAfterClass(): void
    {
        global $config;
        $config['imdbApiLanguage'] = self::$origImdbApiLanguage;
    }

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
        $config['imdbApiLanguage'] = 'de';
        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/

        $data = engineGetData('tt0120915', 'imdbapi');
        $this->assertNotEmpty($data);

        //$this->printData($data);

        $this->assertNotContains('istv', $data);
        $this->assertEquals('Star Wars: Episode I', $data['title']);
        $this->assertEquals('The Phantom Menace', $data['subtitle']);
//         origtitle seams to only work for foreign movies.
        $this->assertEmpty('', $data['origtitle']);
        $this->assertEquals(1999, $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);

        # For non-english movies it seams to be a number
        $this->assertEquals('PG', $data['mpaa']);
        $this->assertEquals(136, $data['runtime']);
        $this->assertEquals('George Lucas', $data['director']);
        $this->assertEquals('George Lucas', $data['writer']);
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 7);
        $this->assertEquals('Vereinigte Staaten von Amerika', $data['country']);
        $this->assertEquals('Englisch, Sanskrit', $data['language']);
        $this->assertEquals('Aktion, Abenteuer, Fantasie', join(', ', $data['genres']));

        # cast tests changed to be independent of order
        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Liam Neeson::Qui-Gon Jinn::imdbapi:nm0000553', $cast));
        $this->assertTrue(in_array('Ewan McGregor::Obi-Wan Kenobi::imdbapi:nm0000191', $cast));
        $this->assertTrue(in_array('Natalie Portman::Queen Amidala / Padmé::imdbapi:nm0000204', $cast));
        $this->assertTrue(in_array('Anthony Daniels::C-3PO (voice)::imdbapi:nm0000355', $cast));
        $this->assertTrue(in_array('Kenny Baker::R2-D2::imdbapi:nm0048652', $cast));
        $this->assertTrue(sizeof($cast) > 90);

        $this->assertMatchesRegularExpression('/Two Jedi escape a hostile blockade to find allies and come across a young boy who may bring balance to the Force, but the long dormant Sith resurface to claim their original glory./', $data['plot']);
    }

    function testEnglishLanguageWithAmericanMovie(): void
    {
        // Star Wars: Episode I
        // https://imdb.com/title/tt0120915/
        $data = engineGetData('tt0120915', 'imdbapi');
        $this->assertNotEmpty($data);

        //$this->printData($data);

        $this->assertNotContains('istv', $data);
        $this->assertEquals('Star Wars: Episode I', $data['title']);
        $this->assertEquals('The Phantom Menace', $data['subtitle']);
        $this->assertEmpty($data['origtitle']);
        $this->assertEquals(1999, $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);

        $this->assertEquals('PG', $data['mpaa']);
        $this->assertEquals(136, $data['runtime']);
        $this->assertEquals('George Lucas', $data['director']);
        $this->assertEquals('George Lucas', $data['writer']);
        $this->assertTrue($data['rating'] >= 6);
        $this->assertTrue($data['rating'] <= 7);
        $this->assertEquals('USA', $data['country']);
        $this->assertEquals('English, Sanskrit', $data['language']);
        $this->assertEquals('Action, Adventure, Fantasy', join(', ', $data['genres']));

        # cast tests changed to be independent of order
        $cast = explode("\n", $data['cast']);
        $this->assertTrue(in_array('Liam Neeson::Qui-Gon Jinn::imdbapi:nm0000553', $cast));
        $this->assertTrue(in_array('Ewan McGregor::Obi-Wan Kenobi::imdbapi:nm0000191', $cast));
        $this->assertTrue(in_array('Natalie Portman::Queen Amidala / Padmé::imdbapi:nm0000204', $cast));
        $this->assertTrue(in_array('Anthony Daniels::C-3PO (voice)::imdbapi:nm0000355', $cast));
        $this->assertTrue(in_array('Kenny Baker::R2-D2::imdbapi:nm0048652', $cast));
        $this->assertTrue(sizeof($cast) > 90);

        $this->assertMatchesRegularExpression('/Two Jedi escape a hostile blockade to find allies and come across a young boy who may bring balance to the Force/', $data['plot']);
    }

    function testMovie2(): void
    {
        // Harold & Kumar Escape from Guantanamo Bay
        // https://www.imdb.com/title/tt0481536/
        $data = engineGetData('tt0481536', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertNotContains('istv', $data);
        $this->assertMatchesRegularExpression('/After being mistaken for terrorists and thrown into Guantánamo Bay, stoners Harold and Kumar escape and return to the U.S./', $data['plot']);
    }

    function testMovieWithoutImage(): void
    {
        // Can We Talk?
        // https://www.imdb.com/title/tt1486604/
        $data = engineGetData('tt1486604', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        // There is no cover image in imdb
        $this->assertNotContains('coverurl', $data);
    }

    function testMovieMultipleDirectors(): void
    {
        // Astérix aux jeux olympiques (2008)
        // https://www.imdb.com/title/tt0463872/
        $data = engineGetData('tt0463872', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        // multiple directors
        $this->assertEquals('Frédéric Forestier, Thomas Langmann', $data['director']);
    }

    function testMoviePlot(): void {
        // Amélie
        // https://www.imdb.com/title/tt0211915/
        // added for bug #2914077 - charset of plot
        $data = engineGetData('tt0211915', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertMatchesRegularExpression('/Amélie is an innocent and naive girl/', $data['plot']);
    }

    function testMovie8(): void {
        // Cars (2006)
        // https://www.imdb.com/title/tt0317219/
        // added for bug #3399788 - title & year
        $data = engineGetData('tt0317219', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertEquals($data['title'],'Cars');
        $this->assertEquals($data['year'], 2006);
    }

    function testMovieDanish(): void {
        // Test that Danish language works.
        Global $config;
        $config['imdbApiLanguage'] = 'da';

        // Biler (2006)
        // https://www.imdb.com/title/tt0317219/
        $data = engineGetData('tt0317219', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals('Cars', $data['title']);
        $this->assertEquals('USA', $data['country']);
        $this->assertEquals('engelsk, italiensk, japansk, Jiddisch', $data['language']);
        $this->assertEquals('Animation, Eventyr, Komedie', join(', ', $data['genres']));
        $this->assertEquals('G', $data['mpaa']);
        $this->assertMatchesRegularExpression('/Racerbilen Lynet McQueen er fuld af selvtillid. Han er nemlig på vej til/', $data['plot']);
        $this->assertEquals(2006, $data['year']);
    }

    /**
     *  Case added for bug 1675281
     *
     *  https://sourceforge.net/tracker/?func=detail&atid=586362&aid=1675281&group_id=88349
     */
    function testSeries(): void {
        // Scrubs
        // https://imdb.com/title/tt0285403/
        $data = engineGetData('tt0285403', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertMatchesRegularExpression("/Zach Braff::Dr. John 'J.D.' Dorian.+?::imdbapi:nm0103785.+?Mona Weiss::Nurse \(uncredited\) .+?::imdbapi:nm2032293/is", $data['cast']);
        $this->assertMatchesRegularExpression('/Sacred Heart Hospital/i', $data['plot']);
    }

    /**
     *  Case added for "24" - php seems to have issues with matching large patterns...
     */
    function testSeriesWithALargeCast(): void
    {
        // 24
        // https://imdb.com/title/tt0285331/
        $data = engineGetData('tt0285331', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertTrue(sizeof(preg_split('/\n/', $data['cast'])) > 400);
    }

    function testSeries3MainPage(): void
    {
        // Bis in die Spitzen
        // https://imdb.com/title/tt0461620/
        $data = engineGetData('tt0461620', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertNotContains('plot', $data);
        $this->assertEmpty($data['runtime']);
        $this->assertTrue($data['rating'] >= 7 && $data['rating'] <= 8);
        $this->assertEquals('Bis in die Spitzen', $data['title']);
    }

    function testSeriesEpisodeWithoutRuntime(): void
    {
        // Bis in die Spitzen: Folge #1.1
        // https://imdb.com/title/tt0872606/
        $data = engineGetData('tt0872606', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals('tt0461620', $data['tvseries_id']);
        $this->assertEmpty($data['plot']);
        $this->assertEmpty($data['runtime']);
        $this->assertEmpty($data['rating']);
        $this->assertEquals('Bis in die Spitzen', $data['title']);
        $this->assertEquals('Episode #1.1', $data['subtitle']);
    }

    function testSeriesEpisode(): void
    {
        // get German version.
        Global $config;
        $config['imdbApiLanguage'] = 'de';

        // Star Trek TNG Episode "Q Who"
        // https://www.imdb.com/title/tt0708758/
        $data = engineGetData('tt0708758', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals('tt0092455', $data['tvseries_id']);
        $this->assertEquals('Star Trek: The Next Generation', $data['title']);
        $this->assertEquals('Q Who', $data['subtitle']);
        $this->assertEquals('1989', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals('Rob Bowman', $data['director']);
        $this->assertEquals('Gene Roddenberry, Maurice Hurley, Melinda M. Snodgrass', $data['writer']);
        $this->assertEquals('TV-PG', $data['mpaa']);
        $this->assertTrue($data['rating'] > 8 && $data['rating'] < 9);
        $this->assertEquals('Vereinigte Staaten von Amerika', $data['country']);
        $this->assertEquals('Englisch', $data['language']);
        $this->assertEquals('Aktion, Abenteuer, Science-Fiction', join(', ', $data['genres']));

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Patrick Stewart::Captain Jean-Luc Picard::imdbapi:nm0001772', $cast));
        $this->assertTrue(in_array("Jonathan Frakes::Commander William Thomas 'Will' Riker::imdbapi:nm0000408", $cast));
        $this->assertTrue(in_array('Marina Sirtis::Counselor Deanna Troi::imdbapi:nm0000642', $cast));
        $this->assertTrue(in_array('John de Lancie::Q::imdbapi:nm0209496', $cast));
        $this->assertTrue(in_array('Rob Bowman::Borg::imdbapi:nm0101385', $cast));

        $this->assertTrue(sizeof($cast) > 15 && sizeof($cast) < 30);
        $this->assertEquals('46', $data['runtime']);

        $this->assertMatchesRegularExpression('/Q tries to prove that Picard needs him as part of their crew by hurling the Enterprise 7,000 light years/', $data['plot']);
    }

    function testSeriesEpisode2(): void
    {
        // The Inspector Lynley Mysteries - Episode: Playing for the Ashes
        // https://www.imdb.com/title/tt0359476
        $data = engineGetData('tt0359476', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEquals('tt0988820', $data['tvseries_id']);
        $this->assertEquals('The Inspector Lynley Mysteries', $data['title']);
        $this->assertEquals('Playing for the Ashes', $data['subtitle']);
        $this->assertEquals('2003', $data['year']);
        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data['coverurl']);
        $this->assertEquals('Richard Spence', $data['director']);
        $this->assertTrue($data['rating'] >= 5 && $data['rating'] <= 8);
        $this->assertEquals('UK', $data['country']);
        $this->assertEquals('English', $data['language']);
        $this->assertEquals('Crime, Drama, Mystery', join(', ', $data['genres']));

        $cast = explode("\n", $data['cast']);

        $this->assertTrue(in_array('Clare Swinburne::Gabriella Patten::imdbapi:nm0842673', $cast));
        $this->assertTrue(in_array('Mark Anthony Brighton::Kenneth Waring::imdbapi:nm1347940', $cast));
        $this->assertTrue(in_array('Nathaniel Parker::Thomas Lynley::imdbapi:nm0662511', $cast));
        $this->assertTrue(in_array('Andrew Clover::Hugh Patten::imdbapi:nm0167249', $cast));
        $this->assertTrue(in_array('Anjalee Patel::Hadiyyah::imdbapi:nm1347125', $cast));
        $this->assertTrue(sizeof($cast) > 12 && sizeof($cast) < 30);

        $this->assertMatchesRegularExpression('/Lynley seeks the help of profiler Helen Clyde when he investigates the asphyxiation death of superstar cricketer with a dysfunctional personal life./', $data['plot']);
    }

    function testSeriesEpisode3(): void {
        // Pushing Daisies - Episode 3
        // https://www.imdb.com/title/tt1039379/
        $data = engineGetData('tt1039379', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        // was not detected as tv episode
        $this->assertEquals(1, $data['istv']);
        $this->assertEquals(42, $data['runtime']);
    }

    function testGetActorUrlByName(): void {
        $url = engineGetActorUrl('Arnold Schwarzenegger', null, 'imdbapi');
        $this->assertEquals('https://www.imdb.com/find/?s=nm&q=Arnold+Schwarzenegger', $url);
    }

    function testGetActorUrlById(): void {
        $url = engineGetActorUrl(null, 'nm0000216', 'imdbapi');
        $this->assertEquals('https://www.imdb.com/name/nm0000216/', $url);
    }

    function testGetActorUrlByNameAndId(): void {
        $url = engineGetActorUrl('Arnold Schwarzenegger', 'nm0000216', 'imdbapi');
        $this->assertEquals('https://www.imdb.com/name/nm0000216/', $url);
    }

    function testTVSeriesExactOneHourLong(): void
    {
        // Terminator: The Sarah Connor Chronicles
        // https://www.imdb.com/title/tt0851851/
        $data = engineGetData('tt0851851', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertEquals(1, $data['istv']);
        $this->assertEmpty($data['runtime']);
    }

    function testActorImageWithNameAndId(): void {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor('William Shatner', 'nm0000638', 'imdbapi');
        //$this->printData($data);

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorImageByName(): void {
        // William Shatner
        // https://www.imdb.com/name/nm0000638/
        $data = engineActor(null, 'nm0000638', 'imdbapi');

        $this->assertNotEmpty($data);
        //$this->printData($data);

        $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $data[0][1]);
    }

    function testActorWithoutImage(): void {
        // Oscar Pearce
        // https://www.imdb.com/name/nm0668994/
        $data = engineActor('Oscar Pearce', 'nm0668994', 'imdbapi');

        //$this->printData($data);

        $this->assertEmpty($data);
    }

    function testSearch(): void
    {
        // Clerks II
        // https://imdb.com/find?q=clerks 2
        $data = engineSearch('Clerks 2', 'imdbapi');

        $this->assertNotEmpty($data);
        // $this->printData($data);

        $data = $data[0];

        $this->assertEquals('imdbapi:tt0424345', $data['id']);
        $this->assertEquals('Clerks II', $data['title']);
        $this->assertEquals("2006 Brian O'Halloran, Jeff Anderson", $data['details']);
    }

    /**
     *  Check fur UTF-8 encoded search and aka search
     */
    function testSearch2(): void
    {
        // Das Streben nach Glück | The Pursuit of Happyness
        // https://www.imdb.com/find?s=all&q=Das+Streben+nach+Gl%FCck

        Global $config;
        $config['imdbApiLanguage'] = 'de';

        $data = engineSearch('Das Streben nach Glück', 'imdbapi');

        // $this->printData($data);

        $this->assertNotEmpty($data);

        $found = false;
        foreach ($data as $row) {
            if ($row['id'] == 'imdbapi:tt0454921') {
                $this->assertEquals('The Pursuit of Happyness', $row['title']);
                $this->assertEmpty($row['subtitle']);
                $this->assertEquals('2006 Will Smith, Thandiwe Newton', $row['details']);
                $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $row['imgsmall']);
                $this->assertMatchesRegularExpression('#https://m.media-amazon.com/images/M/.+?.jpg#', $row['coverurl']);

                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     *  Make sure matching is correct and no HTML tags are included
     */
    function testPartialSearch(): void
    {
        // Serpico
        // https://imdb.com/find?s=all&q=serpico
        $data = engineSearch('serpico', 'imdbapi');

        //$this->printData($data);
        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $t = strip_tags($item['title']);
            $this->assertEquals($item['title'], $t);
        }
    }
}

?>
