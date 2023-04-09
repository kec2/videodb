<?php
/**
 * IMDB Parser
 *
 * Parses data from the Internet Movie Database via API
 *
 * @package Engines
 * @link    http://www.imdb.com  Internet Movie Database
 */
$GLOBALS['imdbServer'] = 'https://www.imdb.com';
$GLOBALS['imdbApiServer'] = 'https://imdb-api.com';
$GLOBALS['imdbApiIdPrefix'] = 'imdbapi:';
$GLOBALS['imdbApiLanguage'] = 'en'; // default english
define('IMDB_KEY', 'apikey');


/**
 *  Get meta information about the engine
 *
 * @todo Include image search capabilities etc in meta information
 *
 * @return ((string|string[])[]|int|string)[]
 *
 * @psalm-return array{name: 'IMDb API', stable: 1, php: '8.1.0', capabilities: array{0: 'movie', 1: 'image'}, config: array{0: array{opt: 'apikey', name: 'IMDb API key', desc: 'To use the IMDb API search engine you need to obtain your own IMDb API key <a href="https://imdb-api.com">here</a>).'}}}
 */
function imdbapiMeta(): array {
    return array('name' => 'IMDb API', 'stable' => 1, 'php' => '8.1.0', 'capabilities' => array('movie', 'image'),
         'config' => array(
                array('opt' => IMDB_KEY, 'name' => 'IMDb API key',
                      'desc' => 'To use the IMDb API search engine you need to obtain your own IMDb API key <a href="https://imdb-api.com">here</a>).')
        ));
}


/**
 * Get Url to search IMDB for a movie or series.
 *
 * @param   string    The search string
 * @return  string    The search URL (GET)
 */
function imdbapiSearchUrl($title) {
    global $imdbApiServer;
    global $config;

    $lang = $config['imdbApiLanguage'] ?? 'en';
    $apikey = $config['imdbapiapikey'];

    $title = join('%20', explode(' ', $title));

    $url = $imdbApiServer.'/'.$lang.'/api/SearchTitle/'.$apikey.'/'.$title;

    return $url;
}

/**
 * Get Url to visit IMDB for a specific movie or series
 *
 * @param   string  $id The movie's external id
 * @return  string      The visit URL
 */
function imdbapiContentUrl($id) {
    global $imdbApiIdPrefix;
    global $imdbApiLanguage;
    global $imdbApiServer;
    global $config;

    $lang = $config['imdbApiLanguage'] ?? 'en';
    $apikey = $config['imdbapiapikey'];
    $id = preg_replace('/^'.$imdbApiIdPrefix.'/', '', $id);

    $url = $imdbApiServer.'/'.$lang.'/api/Title/'.$apikey.'/'.$id.'/FullActor';

    return $url;
}

/**
 * Get IMDB recommendations for a specific movie that meets the requirements
 * of rating and release year.
 *
 * @param   int     $id      The external movie id.
 * @param   float   $rating  The minimum rating for the recommended movies.
 * @param   int     $year    The minimum year for the recommended movies.
 * @return  array            Associative array with: id, title, rating, year.
 *                           If error: $CLIENTERROR contains the http error and blank is returned.
 */
function imdbapiRecommendations($id, $required_rating, $required_year)
{
    global $CLIENTERROR;

    $url = imdbContentUrl($id);
    $resp = httpClient($url, true);

    $recommendations = array();
    preg_match_all('/<a class="ipc-lockup-overlay ipc-focusable" href="\/title\/tt(\d+)\/\?ref_=tt_sims_tt_i_\d+" aria-label="View title page for.+?">/si', $resp['data'], $ary, PREG_SET_ORDER);

    foreach ($ary as $recommended_id) {
        $rec_resp = getApiRecommendationData($recommended_id[1]);
        $imdbId = $recommended_id[1];
        $title  = $rec_resp['title'];
        $year   = $rec_resp['year'];
        $rating = $rec_resp['rating'];

        // matching at least required rating?
        if (empty($required_rating) || (float) $rating < $required_rating) continue;

        // matching at least required year?
        if (empty($required_year) || (int) $year < $required_year) continue;

        $data = array();
        $data['id']     = $imdbId;
        $data['rating'] = $rating;
        $data['title']  = $title;
        $data['year']   = $year;

        $recommendations[] = $data;
    }
    return $recommendations;
}

function getApiRecommendationData($imdbID) {
    global $imdbServer;
    global $imdbApiIdPrefix;
    global $CLIENTERROR;

    $imdbID = preg_replace('/^'.$imdbApiIdPrefix.'/', '', $imdbID);

    // fetch mainpage
    $resp = httpClient($imdbServer.'/title/'.$imdbID.'/', true);     // added trailing / to avoid redirect
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // Titles and Year
    // See for different formats. https://contribute.imdb.com/updates/guide/title_formats
    if ($data['istv']) {
        if (preg_match('/<title>&quot;(.+?)&quot;(.+?)\(TV Episode (\d+)\) - IMDb<\/title>/si', $resp['data'], $ary)) {
            # handles one episode of a TV serie
            $data['title'] = trim($ary[1]);
            $data['year'] = $ary[3];
        } else if (preg_match('/<title>(.+?)\(TV Series (\d+).+?<\/title>/si', $resp['data'], $ary)) {
            $data['title'] = trim($ary[1]);
            $data['year'] = trim($ary[2]);
        }
    } else {
        preg_match('/<title>(.+?)\((\d+)\).+?<\/title>/si', $resp['data'], $ary);
        $data['title'] = trim($ary[1]);
        $data['year'] = trim($ary[2]);
    }

    // Rating
    preg_match('/<div data-testid="hero-rating-bar__aggregate-rating__score" class="sc-.+?"><span class="sc-.+?">(.+?)<\/span><span>\/<!-- -->10<\/span><\/div>/si', $resp['data'], $ary);
    $data['rating'] = trim($ary[1]);

    return $data;
}

/**
 * Search a Movie
 *
 * Searches for a given title on the IMDB and returns the found links in
 * an array
 *
 * @param   string  title   The search string
 * @param   boolean aka     Use AKA search for foreign language titles
 * @return  array           Associative array with id and title
 */
function imdbapiSearch($title) {
    global $imdbApiIdPrefix;
    global $CLIENTERROR;
    global $cache;

    $url = imdbapiSearchUrl($title);
    $resp = httpClient($url, $cache);
    $json = json_decode($resp['data']);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    if (!empty($json->errorMessage)) {
        $CLIENTERROR .= $json->errorMessage."\n";
    }

    $data = [];

    // add encoding
    $data['encoding'] = $resp['encoding'];

    foreach ($json->results as $result) {
        $info             = [];
        $info['id']       = $imdbApiIdPrefix.$result->id;
        $titles = splitTitle($result->title);
        $info['title']    = $titles[0];
        $info['subtitle'] = $titles[1];
//        $info['year']; year is part of the description
        $info['details']  = $result->description;
        $info['imgsmall'] = $result->image;
        $info['coverurl'] = $result->image;
        array_push($data, $info);
    }

    return $data;
}

/**
 * Fetches the data for a given IMDB-ID
 *
 * @param   int   IMDB-ID
 * @return  array Result data
 */
function imdbapiData($imdbID) {
    global $imdbApiIdPrefix;
    global $CLIENTERROR;
    global $cache;

    // remove imdbapi: from id
    $id = preg_replace('/^'.$imdbApiIdPrefix.'/', '', $imdbID);
    $url = imdbapiContentUrl($id);

    // make API call
    $resp = httpClient($url, $cache);
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // result
    $data = [];

    // add encoding
    $data['encoding'] = $resp['encoding'];

    $json = json_decode($resp['data']);

    //$data['id']        = $json->id;

    $title = splitTitle($json->title);
    $data['title']     = $title[0];
    $data['subtitle']  = $title[1];
    $data['origtitle'] = $json->originalTitle;
    $data['fulltitle'] = $json->fullTitle;
    $data['type']      = $json->type;
    $data['year']      = $json->year;
    $data['coverurl']  = $json->image;
    $data['runtime']   = $json->runtimeMins;

    if (!empty($json->plotLocal)) {
        $data['plot']      = $json->plotLocal;
    } else {
        $data['plot']      = $json->plot;
    }

    $data['director']  = $json->directors;
    $data['writer']    = $json->writers;
    foreach($json->genreList as $genre) {
        $data['genres'][] = $genre->value;
    }
    $data['country']    = $json->countries;
    $data['language']  = $json->languages;
    $data['mpaa']      = $json->contentRating;
    $data['rating']    = $json->imDbRating;

    if ($json->type == 'TVSeries' || $json->type == 'TVEpisode') {
        $data['istv'] = 1;

        if ($json->type == 'TVEpisode') {
            $data['tvseries_id'] = $json->tvEpisodeInfo->seriesId;

            // hvordan skal vi genne title på en serie episode:
            // 1) bare bruge title: A Knight of the Seven Kingdoms
            // 2) bruge Series title som title og title som subtitle: Game of Thrones - A Knight of the Seven Kingdoms
            $data['title']    = $json->tvEpisodeInfo->seriesTitle;
            $data['subtitle'] = $json->title;
        } else {
            $data['tvseries_id'] = $json->id;
        }
    }

    $cast = '';
    foreach($json->actorList as $actor) {
        $actorid   = $actor->id;
        $imgurl    = $actor->image;
        $character = $actor->asCharacter;
        $actor     = $actor->name;
        $cast .= "$actor::$character::$imdbApiIdPrefix$actorid\n";

        // TODO - we really should use the $imgurl in stead of looking it up with getActorUrl.
    }
    // remove html entities and replace &nbsp; with simple space
    $data['cast'] = html_clean_utf8($cast);

    // sometimes appearing in series (e.g. Scrubs)
    $data['cast'] = preg_replace('#/ ... #', '', $data['cast']);

    return $data;
}


/**
 * @return string[]
 *
 * @psalm-return array{0: string, 1: string}
 */
function splitTitle($input): array {
    list($title, $subtitle) = array_pad(explode(' - ', $input, 2), 2, '');

    // no dash, lets try colon
    if (empty($subtitle)) {
        list($title, $subtitle) = array_pad(explode(': ', $input, 2), 2, '');
    }
    $data = [];
    $data[0] = trim($title);
    $data[1] = trim($subtitle);

    return $data;
}

/**
 * Get Url to visit IMDB for a specific actor
 *
 * @param   string  $name   The actor's name
 * @param   string  $id The actor's external id
 * @return  string      The visit URL
 */
function imdbapiActorUrl($name, $id) {
    global $imdbServer;

    if ($id) {
        $path = 'name/'.urlencode($id).'/';
    } else {
        $path = 'find/?s=nm&q='.urlencode($name);
    }

    return $imdbServer.'/'.$path;
}

/**
 * Parses Actor-Details
 *
 * Find image and detail URL for actor, not sure if this can be made
 * a one-step process?
 *
 * @param  string  $name  Name of the Actor
 * @return array          array with Actor-URL and Thumbnail
 */
function imdbapiActor($name, $actorid) {
    global $imdbServer;
    global $cache;

    // search directly by id or via name?
    $actorUrl = imdbapiActorUrl($name, $actorid);
    $resp = httpClient($actorUrl, $cache);

    // if not direct match load best match
    if (preg_match('#<b>Popular Names</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)
            || preg_match('#<b>Names \(Exact Matches\)</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)
            || preg_match('#<b>Names \(Approx Matches\)</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)) {

        if (!preg_match('/http/i', $m[1])) {
            $m[1] = $imdbServer.$m[1];
        }
        $resp = httpClient($m[1], true);
    }

    // now we should have loaded the best match

    $ary = [];
    if (preg_match('/<div class=".+? ipc-poster--baseAlt .+?<img.+?src="(https.+?)".+?href="(\/name\/nm\d+\/)/si', $resp['data'], $m)) {
        $ary[0][0] = $m[2]; // /name/nm12345678/
        $ary[0][1] = $m[1]; // img url
    }

    return $ary;
}

?>
