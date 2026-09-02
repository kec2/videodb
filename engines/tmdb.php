<?php
/**
 * IMDB Parser
 *
 * Parses data from the Internet Movie Database
 *
 * @package Engines
 * @author  Andreas Gohr    <a.gohr@web.de>
 * @link    http://www.tmdb.com  Internet Movie Database
 */
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$GLOBALS['tmdbServer'] = 'https://api.themoviedb.org';
$GLOBALS['tmdbIdPrefix'] = 'tmdb:';
define('TMDB_API_KEY', 'apikey');

/**
 *  Get meta information about the engine
 *
 * @todo Include image search capabilities etc in meta information
 *
 * @return (int|string|string[])[]
 *
 * @psalm-return array{name: 'IMDb', stable: 1, php: '8.1.0', capabilities: array{0: 'movie', 1: 'image'}}
 */
function tmdbMeta(): array {
    return array('name' => 'TMDB', 'stable' => 1, 'php' => '8.1.0', 'capabilities' => array('movie', 'image'),
        'config' => array(
            array('opt' => TMDB_API_KEY, 'name' => 'TMDB API key',
                'desc' => 'To use the TMDB search engine you need to obtain your own THDM API key <a href="https://imdb-api.com">here</a>).')
        ));
}


/**
 * Get Url to search TMDB for a movie
 *
 * @author  Andreas Goetz <cpuidle@gmx.de>
 * @param   string    The search string
 * @return  string    The search URL (GET)
 */
function tmdbSearchUrl($title)
{
    global $tmdbServer;

    return $tmdbServer.'/3/search/multi?query='.rawurlencode($title);
}

/**
 * Get Url to visit TMDB for a specific movie
 *
 * @author  Andreas Goetz <cpuidle@gmx.de>
 * @param   string  $id The movie's external id
 * @return  string      The visit URL
 */
function tmdbContentUrl($id)
{
    global $tmdbIdPrefix;

    $id = preg_replace('/^'.$tmdbIdPrefix.'/', '', $id);

    return 'https://www.themoviedb.org/movie/'.$id;
}

/**
 * Get TMDB recommendations for a specific movie that meets the requirements
 * of rating and release year.
 *
 * @author  Klaus Christiansen <klaus_edwin@hotmail.com>
 * @param   int     $id      The external movie id.
 * @param   float   $required_rating  The minimum rating for the recommended movies.
 * @param   int     $required_year    The minimum year for the recommended movies.
 * @return  array            Associative array with: id, title, rating, year.
 *                           If error: $CLIENTERROR contains the http error and blank is returned.
 */
function tmdbRecommendations($id, $required_rating, $required_year)
{
    global $CLIENTERROR;
    global $tmdbIdPrefix;
    global $config;
    global $tmdbServer;

    $tmdbId = preg_replace('/^'.$tmdbIdPrefix.'/', '', $id);
    $url = $tmdbServer.'/3/movie/'.$tmdbId.'/recommendations';

    $apikey = $config['tmdbapikey'];
    $param = [ 'header' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '. $apikey,
        'Content-Type' => 'application/json'
    ]
    ];

    $resp = httpClient($url, true, $param);
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    $json = json_decode($resp['data']);

    $recommendations = [];
    foreach ($json->results as $result) {
        $rating =  $result->vote_average;
        $year = substr($result->release_date, 0, 4);

        // matching at least required rating?
        if (empty($required_rating) || (float) $rating < $required_rating) continue;

        // matching at least required year?
        if (empty($required_year) || (int) $year < $required_year) continue;

        $data = [];
        $data['id']     = $tmdbIdPrefix . $result->id;
        $data['rating'] = $rating;
        $data['title']  = $result->title;
        $data['year']   = $year;

        $recommendations[] = $data;
    }

    return $recommendations;
}

/**
 * Search a Movie
 *
 * Searches for a given title on the IMDB and returns the found links in
 * an array
 *
 * @author  Tiago Fonseca <t_r_fonseca@yahoo.co.uk>
 * @author  Charles Morgan <cmorgan34@yahoo.com>
 * @param   string  $title   The search string
 * @return  array           Associative array with id and title
 */
function tmdbSearch($title)
{
    global $tmdbIdPrefix;
    global $CLIENTERROR;
    global $cache;
    global $config;

    $url = tmdbSearchUrl($title);
    $json = callTmdbApi($url);

    $data = [];

    // add TMDB uses utf-8 encoding
    $data['encoding'] = 'utf-8';
    foreach($json->results as $result) {
        $info = [];
        if ($result->media_type == 'tv') {
            $info['id'] = $tmdbIdPrefix . $result->id .'TV';
            $info['title'] = $result->name;
            $info['year'] = substr($result->first_air_date, 0, 4);
            if (isset($result->poster_path)) {
                $info['imgsmall'] = 'https://image.tmdb.org/t/p/original/'.$result->poster_path;
            }
            $data[] = $info;
        } elseif ($result->media_type == 'movie') {
            $info['id'] = $tmdbIdPrefix . $result->id;
            $info['title'] = $result->title;
            $info['year'] = substr($result->release_date, 0, 4);
            if (isset($result->poster_path)) {
                $info['imgsmall'] = 'https://image.tmdb.org/t/p/original/'.$result->poster_path;
            }
            $data[] = $info;
        }
    }
    return $data;
}

/**
 * Fetches the data for a given TMDB-ID
 *
 * @author  Tiago Fonseca <t_r_fonseca@yahoo.co.uk>
 * @author  Victor La <cyridian@users.sourceforge.net>
 * @author  Roland Obermayer <robelix@gmail.com>
 * @param   string   $tmdbID (tmdb:123[TV])
 * @return  array Result data
 */
function tmdbData($tmdbID)
{
    global $tmdbServer;
    global $tmdbIdPrefix;

    $data = []; // result

    $isTv = 0;
    if (str_ends_with($tmdbID, 'TV')) {
        $isTv = 1;
        $tmdbID = str_replace('TV', '', $tmdbID);
    }

    $tmdbID = preg_replace('/^'.$tmdbIdPrefix.'/', '', $tmdbID);
    if ($isTv) {
        $url = $tmdbServer.'/3/tv/'.$tmdbID;
    } else {
        $url = $tmdbServer.'/3/movie/'.$tmdbID;
    }
    $json = callTmdbApi($url);
    // add encoding
    $data['encoding'] = 'utf-8';

    $data['istv'] = $isTv;
    if ($isTv) {
        $data['year'] = substr($json->first_air_date, 0, 4);
    } else {
        $data['year'] = substr($json->release_date, 0, 4);
    }

    $titles = getTmdbTitles($json);
    $data['title'] = $titles['title'];
    $data['subtitle'] = $titles['subtitle'];
    $data['origtitle'] = $titles['origtitle'];

    // Cover URL
    $data['coverurl'] = 'https://image.tmdb.org/t/p/original/'.$json->poster_path;

    // MPAA Rating
//    $data['mpaa'] = tmdbGetParentalGuide($resp['data'], $json);

    // Runtime
    $data['runtime'] = $json->runtime;

    if ($isTv) {
        $completeCast = getTmdbTvCast($tmdbID);
    } else {
        $completeCast = getTmdbMovieCast($tmdbID);
    }

    $data['cast'] = $completeCast['cast'];
    $data['director'] = $completeCast['director'];
    $data['creator'] = $completeCast['creator'];
    $data['writer'] = $completeCast['writer'];

    // Rating
    $data['rating'] = $json->vote_average;

    // Countries
    $data['country'] = getTmdbCountries($json);

    // Languages
    $data['language'] = getTmdbLanguages($json);

    // Genres (as Array)
    $data['genres'] = getTmdbGenres($json);

    // Plot
    $data['plot'] = $json->overview;

    return $data;
}

function getTmdbMovieCast($tmdbId) {
    global $tmdbIdPrefix;
    global $tmdbServer;

    $url =  $tmdbServer.'/3/movie/'.$tmdbId.'/credits';
    $json = callTmdbApi($url);

    $completeCast = [];
    if (isset($json)) {
        $fullCast = '';
        $directors = [];
        $writers = [];
        $creators = [];

        foreach ($json->cast as $cast) {
            if ($cast->known_for_department == 'Acting') {
                $actorId = $cast->id;
                $actor = $cast->name;
                $role = $cast->character;

                // make spaces, tabs and newlines into spaces
                $role = preg_replace('/\s/', ' ', $role);
                // change HTML brake space into space.
                $role = preg_replace('/&nbsp;/', ' ', $role);
                // make multiple spaces into a single space
                $role = preg_replace('/\s+/', ' ', $role);
                // replace U+0092 : <control> PRIVATE USE TWO [PU2] with single quote
                $role = preg_replace('/[\x00\x92]/u', '&#039;', $role);
                // sometimes appearing in series (e.g. Scrubs)
                $role = preg_replace('#/ ... #', '', $role);
                $role = trim(strip_tags($role));

                $fullCast .= "$actor::$role::$tmdbIdPrefix$actorId\n";
            }

            $completeCast['cast'] = $fullCast;
        }

        foreach ($json->crew as $crew) {
            if ($crew->department == 'Directing' && $crew->job == 'Director') {
                $directors[] = $cast->name;
            } elseif ($crew->department == 'Writing' && $crew->job == 'Screenplay') {
                $writers[] = $crew->name;
            }
        }
        $dirs = implode(', ', $directors);
        if (strlen($dirs) > 250) {
            dlog("WARNING: Directors string to long(250). $tmdbId");
        }
        $completeCast['director'] = substr($dirs, 0, 250);
        $completeCast['Writing'] = implode(', ', $writers);
        $completeCast['creator'] = implode(', ', $creators);
    }

    return $completeCast;
}

function getTmdbTvCast($tmdbId) :array {
    global $tmdbIdPrefix;
    global $tmdbServer;

    // Fetch credits
    $url =  $tmdbServer.'/3/tv/'.$tmdbId.'/aggregate_credits';
    $json = callTmdbApi($url);

    $completeCast = [];

    if (isset($json)) {
        $fullCast = '';
        $directors = [];
        $writers = [];
        $creators = [];

        foreach ($json->cast as $cast) {
            if ($cast->known_for_department == 'Acting') {
                $actorId = $cast->id;
                $actor = $cast->name;

                $roles = '';
                foreach ($cast->roles as $role) {
                    $roles .= $role->character . ' (' . $role->episode_count . " episodes), ";
                }
                $roles = preg_replace("#, $#", "", $roles);

                // make spaces, tabs and newlines into spaces
                $roles = preg_replace('/\s/', ' ', $roles);
                // change HTML brake space into space.
                $roles = preg_replace('/&nbsp;/', ' ', $roles);
                // make multiple spaces into a single space
                $roles = preg_replace('/\s+/', ' ', $roles);
                // replace U+0092 : <control> PRIVATE USE TWO [PU2] with single quote
                $roles = preg_replace('/[\x00\x92]/u', '&#039;', $roles);
                // sometimes appearing in series (e.g. Scrubs)
                $roles = preg_replace('#/ ... #', '', $roles);
                $roles = trim(strip_tags($roles));

                $fullCast .= "$actor::$roles::$tmdbIdPrefix$actorId\n";
            }

            $completeCast['cast'] = $fullCast;
        }

        foreach ($json->crew as $crew) {
            # dlog("categories:" . $cats->name);
            if ($crew->department == 'Directing') {
                $directors[] = $crew->name . ' (' . $crew->total_episode_count .' episodes)';
            } elseif ($crew->department == 'Writing') {
                $writers[] = $crew->name. ' (' . $crew->total_episode_count .' episodes)';
            }
        }
        $dirs = implode(', ', $directors);
        if (strlen($dirs) > 250) {
            dlog("WARNING: Directors string to long(250). $tmdbId");
        }
        $completeCast['director'] = substr($dirs, 0, 250);
        $completeCast['writing'] = implode(', ', $writers);
        $completeCast['creator'] = implode(', ', $creators);

//        dlog($completeCast);
    }
    return $completeCast;
}

function getTmdbGenres($json): array
{
    $genres = [];
    if (isset($json->genres)) {
        foreach($json->genres as $genre) {
            if (str_contains($genre->name, '&')) {
                $genreList = explode('&', $genre->name);
                foreach ($genreList as $g) {
                    $genres[] = trim($g);
                }
            } else {
                $genres[] = $genre->name;
            }
        }
    }

    return $genres;
}

function getTmdbCountries($json): ?string
{
    if (isset($json)) {
        $countries = [];
        foreach($json->production_countries as $country) {
            $countries[] = $country->name;
        }
        return join(', ', $countries);
    }

    return null;
}

function getTmdbLanguages($json): ?string
{
    $languages = [];
    foreach($json->spoken_languages as $language) {
        $languages[] = strtolower($language->english_name);
    }
    if (sizeof($languages) > 0) {
        return join(', ', $languages);
    }

    return null;
}

function getTmdbTitles($json): array {
    $titles = [];

    if (isset($json->name)) {
        $title = tmdbSplitTitle($json->name);
        $titles['title'] = $title[0];
        $titles['subtitle'] = $title[1];
    }

    if (isset($json->original_name)) {
        $titles['origtitle'] = $json->original_name;
    }

    return $titles;
}

function tmdbSplitTitle($input): array {
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
 * @author  Michael Kollmann <acidity@online.de>
 * @param   string  $name   The actor's name
 * @param   string  $id The actor's external id
 * @return  string      The visit URL
 */
function tmdbActorUrl($name, $id)
{
    return 'https://www.themoviedb.org/person/'.$id;
}

function tmdbGetContentUrl() {

}

/**
 * Parses Actor-Details
 *
 * Find image and detail URL for actor, not sure if this can be made
 * a one-step process?
 *
 * @author                Andreas Goetz <cpuidle@gmx.de>
 * @param  string  $name  Name of the Actor
 * @return array          array with Actor-URL and Thumbnail
 */
function tmdbActor($name, $actorId)
{
    global $tmdbServer;

    $url = $tmdbServer.'/3/person/'.$actorId;
    $json = callTmdbApi($url);

    $actorUrl = [];
    $actorUrl[0][0] = 'https://www.themoviedb.org/person/' . $actorId;
    if (isset($json->profile_path)) {
        $actorUrl[0][1] = 'https://image.tmdb.org/t/p/original/' . $json->profile_path;
    }

    return $actorUrl;
}

function callTmdbApi($url) {
    global $CLIENTERROR;
    global $cache;
    global $config;

    $apikey = $config['tmdbapikey'];
    $param = [ 'header' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '. $apikey,
        'Content-Type' => 'application/json'
    ]
    ];

    $resp = httpClient($url, $cache, $param);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    return json_decode($resp['data']);
}

?>
