<?php
/**
 * testEngines.php
 *
 * Engines test case
 *
 * @package Test
 */

require_once './core/functions.php';
require_once './engines/engines.php';
use PHPUnit\Framework\TestCase;

class TestEngines extends TestCase {

    function testGetEngineFromId(): void {
        $engineName = engineGetEngine('imdb:tt1234567');
        $this->assertEquals('imdb', $engineName);

        $engineName = engineGetEngine('imdbapi:tt1234567');
        $this->assertEquals('imdbapi', $engineName);

        $engineName = engineGetEngine('ABCDE12345');
        $this->assertEquals('amazonaws', $engineName);

        $engineName = engineGetEngine('default ot imdb');
        $this->assertEquals('imdb', $engineName);
    }

    function testEngineGetActorEngine(): void {
        $engineName = engineGetActorEngine('imdb:nm1234567');
        $this->assertEquals('imdb', $engineName);

        $engineName = engineGetActorEngine('imdbapi:nm1234567');
        $this->assertEquals('imdbapi', $engineName);

        $engineName = engineGetActorEngine('default to imdb');
        $this->assertEquals('imdb', $engineName);
    }

    function testEngineGetActorUrl(): void {
        $actorName = 'Mads Mikkelsen';
        $actorId = 'nm0586568';
        $engine = 'imdbapi';

        $actorUrl = engineGetActorUrl($actorName, $actorId, $engine);
        $expectedUrl = "https://www.imdb.com/name/nm0586568/";
        $this->assertEquals($expectedUrl, $actorUrl);
    }

    function testEngineGetActorUrlNoId(): void {
        $actorName = 'Mads Mikkelsen';
        $engine = 'imdbapi';

        $actorUrl = engineGetActorUrl($actorName, null, $engine);
        $expectedUrl = "https://www.imdb.com/find/?s=nm&q=Mads+Mikkelsen";
        $this->assertEquals($expectedUrl, $actorUrl);
    }

    function testEngineActorImdbapi(): void {
        $actorName = 'Mads Mikkelsen';
        $actorId = 'nm0586568';
        $engine = 'imdbapi';

        $actor = engineActor($actorName, $actorId, $engine, false);
        $expectedActorUrl = "/name/nm0586568/";
        $expectedActorImageUrl = "https://m.media-amazon.com/images/M/MV5BMTcyMTU5MzgxMF5BMl5BanBnXkFtZTYwMDI0NjI1._V1_QL75_UX140_CR0,1,140,207_.jpg";

        $this->assertEquals($expectedActorUrl, $actor[0][0]);
        $this->assertEquals($expectedActorImageUrl, $actor[0][1]);
    }

    function testEngineActorImdb(): void {
        $actorName = 'Mads Mikkelsen';
        $actorId = 'nm0586568';
        $engine = 'imdb';

        $actor = engineActor($actorName, $actorId, $engine, false);
        $expectedActorUrl = "/name/nm0586568/";
        $expectedActorImageUrl = "https://m.media-amazon.com/images/M/MV5BMTcyMTU5MzgxMF5BMl5BanBnXkFtZTYwMDI0NjI1._V1_QL75_UX140_CR0,1,140,207_.jpg";

        $this->assertEquals($expectedActorUrl, $actor[0][0]);
        $this->assertEquals($expectedActorImageUrl, $actor[0][1]);
    }
}

?>
