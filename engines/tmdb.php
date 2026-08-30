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
 * @param   float   $rating  The minimum rating for the recommended movies.
 * @param   int     $year    The minimum year for the recommended movies.
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
 * @param   string  title   The search string
 * @return  array           Associative array with id and title
 */
function tmdbSearch($title)
{
    global $tmdbIdPrefix;
    global $CLIENTERROR;
    global $cache;
    global $config;

    $url = tmdbSearchUrl($title);

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

    $data = [];
    /*
     {
      "adult": false,
      "backdrop_path": "/ffdqHMWkh1h9MABwIfbfRJhgFW6.jpg",
      "id": 218,
      "title": "The Terminator",
      "original_title": "The Terminator",
      "overview": "In the post-apocalyptic future, reigning tyrannical supercomputers teleport a cyborg assassin known as the \"Terminator\" back to 1984 to kill Sarah Connor, whose unborn son is destined to lead insurgents against 21st century mechanical hegemony. Meanwhile, the human-resistance movement dispatches a lone warrior to safeguard Sarah. Can he stop the virtually indestructible killing machine?",
      "poster_path": "/qvktm0BHcnmDpul4Hz01GIazWPr.jpg",
      "media_type": "movie",
      "original_language": "en",
      "genre_ids": [28, 53, 878],
      "popularity": 34.0101,
      "release_date": "1984-10-26",
      "softcore": false,
      "video": false,
      "vote_average": 7.689,
      "vote_count": 15008
    },
    {
      "adult": false,
      "backdrop_path": "/woH18JkZMYhMSWqtHkPA4F6Gd1z.jpg",
      "id": 239287,
      "name": "Terminator Zero",
      "original_name": "Terminator Zero",
      "overview": "A warrior from a post-apocalyptic future travels to 1997 to protect an AI scientist being hunted by an unfeeling — and indestructible — cyborg.",
      "poster_path": "/v4sbn6IsJGAIZNHjdB4CprvS7zo.jpg",
      "media_type": "tv",
      "original_language": "en",
      "genre_ids": [16, 10765, 10759],
      "popularity": 17.0942,
      "first_air_date": "2024-08-29",
      "softcore": false,
      "vote_average": 7.02,
      "vote_count": 229,
      "origin_country": ["US", "JP"]
    }
    */
    // add encoding
    $data['encoding'] = $resp['encoding'];
    $json = json_decode($resp['data']);
    if (!empty($json)) {
        foreach($json->results as $result) {
            $info = [];
            $info['id'] = $tmdbIdPrefix . $result->id;
            if ($result->media_type == 'tv') {
                $info['title'] = $result->name;
                $info['year'] = substr($result->first_air_date, 0, 4);
                $data[] = $info;
            } elseif ($result->media_type == 'movie') {
                $info['title'] = $result->title;
                $info['year'] = substr($result->release_date, 0, 4);
                $data[] = $info;
            }
        }
        return $data;
    }
    return [];
}

/**
 * Fetches the data for a given IMDB-ID
 *
 * @author  Tiago Fonseca <t_r_fonseca@yahoo.co.uk>
 * @author  Victor La <cyridian@users.sourceforge.net>
 * @author  Roland Obermayer <robelix@gmail.com>
 * @param   int   IMDB-ID
 * @return  array Result data
 */
function tmdbData($tmdbID)
{
    global $tmdbServer;
    global $tmdbIdPrefix;
    global $CLIENTERROR;
    global $cache;
    global $config;

    $tmdbID = preg_replace('/^'.$tmdbIdPrefix.'/', '', $tmdbID);
    $data = []; // result
    $ary = []; // temp

    // fetch mainpage
    $apikey = $config['tmdbapikey'];
    $param = [ 'header' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '. $apikey,
        'Content-Type' => 'application/json'
    ]
    ];

    $resp = httpClient($tmdbServer.'/3/movie/'.$tmdbID, $cache, $param);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // add encoding
    $data['encoding'] = $resp['encoding'];

    $json = json_decode($resp['data']);

//    $data['istv'] = tmdbIsTV($json);
//    if ($data['istv']) {
//        // get the id from the main tv show. Not the episode
//        $data['tvseries_id'] = tmdbGetSeriesId($resp['data'], $json);
//    }

    $data['year'] = substr($json->release_date, 0, 4);

//    $titles = tmdbGetTitleAndSubtitle($json->title);
    $data['title'] = $json->title;
//    $data['subtitle'] = $titles['subtitle'];
    $data['origtitle'] = $json->original_title;

    // Cover URL
    $data['coverurl'] = 'https://image.tmdb.org/t/p/original/'.$json->poster_path;

    // MPAA Rating
//    $data['mpaa'] = tmdbGetParentalGuide($resp['data'], $json);

    // Runtime
    $data['runtime'] = $json->runtime;

    $completeCast = tmdbGetCastV2($tmdbID);
    $data['cast'] = $completeCast['cast'];
    $data['director'] = $completeCast['director'];
    $data['creator'] = $completeCast['creator'];
    $data['writer'] = $completeCast['writer'];

    // Rating
    $data['rating'] = $json->vote_average;

    // Countries
    $data['country'] = tmdbGetCountries($json);

    // Languages
    $data['language'] = tmdbGetLanguages($json);

    // Genres (as Array)
    $data['genres'] = tmdbGetGenres($json);

    // Plot
    $data['plot'] = $json->overview;

    return $data;
}

function getPagePropsJson2($tmdbID, $data) {
    if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">([^<]*)<\/script>/si', $data, $ary)) {
        try {
            $json = json_decode($ary[1]);
//         dlog("Found json for $tmdbID");
            return $json->props->pageProps;
        }   catch (Exception $e) {
            dlog("Json error, tmdbid: $tmdbID, ". json_last_error() .", " . json_last_error_msg());
            dlog($ary[1]);
        }
    } else {
        dlog("Did not find any json for $tmdbID");
    }
    return null;
}

function tmdbGetCoverURL($tmdbID) {
    global $tmdbServer;
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

    $resp = httpClient($tmdbServer.'/3/movie/'.$tmdbID.'/image', $cache, $param);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    $json = json_decode($resp['data']);
    return $json->backdrops[0]->file_path;
}

function tmdbGetOriginalTitleV2($json) {
    if (isset($json->originalTitleText)) {
        return $json->originalTitleText->text;
    }
    return null;
}

function tmdbIsTV($json) {
    if (isset($json)
            && isset($json->aboveTheFoldData->titleType)
            && ($json->aboveTheFoldData->titleType->isSeries
                || $json->aboveTheFoldData->titleType->isEpisode)) {
        return 1;
    }
    return 0;
}

function tmdbGetSeriesId($data, $json) {
    // going through main page gives: $json->aboveTheFoldData->series
    // going through full credits gives: $json->contentData->data->title->series
    if (isset($json)) {
//         dlog('got series id from json');
        if ($json->aboveTheFoldData->series) {
            // get the id from the main tv show. Not the episode
            return str_replace('tt', '', $json->aboveTheFoldData->series->series->id);
        }

        // Id for the episode
        return str_replace('tt', '', $json->aboveTheFoldData->id);
    } else {
//         dlog('got series id from html');
        if (preg_match('/<meta property="tmdb\:pageConst" content="tt(\d+)">/', $data, $ary)) {
            // Get id for the series.
            return $ary[1];
        } elseif (preg_match('/<a .+? data-testid="hero-title-block__series-link" href="\/title\/tt(\d+)\/\?ref_=tt_ov_inf">/si', $data, $ary)) {
            // get id for the episode
            // <meta property="og:type" content="video.tv_show">
            // <meta property="og:type" content="video.episode">
            // <meta property="og:type" content="video.tv_show"/>

            return $ary[1];
        }
    }

    return null;
}

function tmdbGetGenres($json): ?array
{
    if (isset($json->genres)) {
        $genres = [];
        foreach($json->genres as $genre) {
            $genres[] = $genre->name;
        }
        return $genres;
    }

    return null;
}

/*
 * Get movie content rating.
 * This differs from country to country.
 * https://en.wikipedia.org/wiki/Motion_picture_content_rating_system
 *
 * @param   string  $data   IMDB Page data
 * @return  string          The movie content rating score or null.
 */
function tmdbGetParentalGuide($data, $json) {
    // going through main page gives all rating
    // going through full credits gives all rating
    if (isset($json) && isset($json->aboveTheFoldData->certificate)) {
//         dlog('get certification from json');
        return $json->aboveTheFoldData->certificate->rating;
    } elseif (preg_match('#<a .+? href="/title/tt\d+/parentalguide/certificates\?ref_=tt_ov_pg">(.+?)</a>#is', $data, $ary)) {
//         dlog('get certification from html');
        return trim($ary[1]);
    }

    return null;
}

function tmdbGetCountries($json) {
    if (isset($json)) {
//         dlog('got countries from json');
        $countries = [];
        foreach($json->production_countries as $country) {
            $countries[] = $country->name;
        }
        return join(', ', $countries);
    }

    return null;
}

/*
 * @param string $tmdbID    is the is the ID of the movie
 */
function tmdbGetCastV2($tmdbID) {
    global $tmdbIdPrefix;
    global $tmdbServer;
    global $cache;
    global $CLIENTERROR;
    global $config;

    // Fetch credits

    $apikey = $config['tmdbapikey'];
    $param = [ 'header' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '. $apikey,
        'Content-Type' => 'application/json'
    ]
    ];

    $resp = httpClient($tmdbServer.'/3/movie/'.$tmdbID.'/credits', $cache, $param);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error'].'\n';
    }

    $completeCast = [];
    $json = json_decode($resp['data']);

    if (isset($json)) {
        $cast = '';
        $directors = [];
        $writers = [];
        $creators = [];

        foreach ($json->cast as $cats) {
            # dlog("categories:" . $cats->name);
            if ($cats->known_for_department == 'Acting') {
                $actorId = $cats->id;
                $actor = $cats->name;
                $role = $cats->character;

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

                $cast .= "$actor::$role::$tmdbIdPrefix$actorId\n";
            }

            $completeCast['cast'] = $cast;
        }

        foreach ($json->crew as $cats) {
            # dlog("categories:" . $cats->name);
            if ($cats->department == 'Directing' && $cats->job == 'Director') {
                $directors[] = $cats->name;
            } elseif ($cats->department == 'Writing' && $cats->job == 'Screenplay') {
                $writers[] = $cats->name;
            }
        }
        $dirs = implode(', ', $directors);
        if (strlen($dirs) > 250) {
            dlog("WARNING: Directors string to long(250). $tmdbID");
        }
        $completeCast['director'] = substr($dirs, 0, 250);
        $completeCast['Writing'] = implode(', ', $writers);
        $completeCast['creator'] = implode(', ', $creators);

        dlog($completeCast);
    }
    return $completeCast;
}

/*
 * @param string $tmdbID    is the is the ID of the movie
 */
function tmdbGetCast($tmdbID, $data) {
    global $tmdbIdPrefix;
    global $cache;

    $cast = '';
    $after = '';

    do {
        $url = 'https://caching.graphql.tmdb.com/?operationName=TitleCreditSubPagePagination&variables={"after":"'.$after.'","category":"cast","const":"tt'.$tmdbID.'","first":250,"locale":"en-US","originalTitleText":false,"tconst":"tt'.$tmdbID.'"}&extensions={"persistedQuery":{"sha256Hash":"716fbcc1b308c56db263f69e4fd0499d4d99ce1775fb6ca75a75c63e2c86e89c","version":1}}';
//         dlog("Calling: $url");
        $param = [ 'header' => [
              'Accept' => 'application/json',
              'User-Agent' => 'Mozilla/5.0',
              'Content-Type' => 'application/json',
              ]
        ];
        $resp = httpClient($url, $cache, $param);
        if (!$resp['success']) {
            $CLIENTERROR .= $resp['error'].'\n';
        }

        $json = json_decode($resp['data']);
        $credits = $json->data->title->credits;

        foreach($credits->edges as $edge) {
            $actorId = $edge->node->name->id;
            $actor = $edge->node->name->nameText->text;
            $role;
            if (is_array($edge->node->characters)) {
                $characterNames = array_map(function ($char) {
                    return $char->name;
                }, $edge->node->characters);
                $role = implode(' / ', $characterNames);

                if ($edge->node->attributes) {
                    foreach($edge->node->attributes as $attr) {
                        $role .= " (" . $attr->text . ")";
                    }
                }
            } else {
                $role = $edge->node->attributes->text;
            }
            if ($edge->node->episodeCredits && $edge->node->episodeCredits->total > 0) {
                $total = $edge->node->episodeCredits->total;
                $from = $edge->node->episodeCredits->yearRange->year;
                $to = $edge->node->episodeCredits->yearRange->endYear;

                $role .= ", $total episodes, $from";
                if ($to) {
                    $role .= "-$to";
                }
            }

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

            $cast .= "$actor::$role::$tmdbIdPrefix$actorId\n";
        }
        $after = $credits->pageInfo->endCursor;
    } while ($credits->pageInfo->hasNextPage);

    return $cast;
}


function tmdbGetLanguages($json) {
//     dlog("tmdbId: " .$json->aboveTheFoldData->id);
    if (isset($json)) {
        // might not be there for an serie episode
        $languages = [];
        foreach($json->spoken_languages as $language) {
            $languages[] = strtolower($language->english_name);
        }
        if (sizeof($languages) > 0) {
//             dlog('got languages from json');
            return join(', ', $languages);
        }
    }

    return null;
}

function tmdbGetRating($data, $json) {
    if (isset($json) && isset($json->aboveTheFoldData->ratingsSummary)) {
//         dlog('got rating from json');
        return $json->aboveTheFoldData->ratingsSummary->aggregateRating;
    } elseif (preg_match('/<div data-testid="hero-rating-bar__aggregate-rating__score" class="sc-.+?"><span class="sc-.+?">(.+?)<\/span><span>\/<!-- -->10<\/span><\/div>/si', $data, $ary)) {
//         dlog('got rating from html');
        return trim($ary[1]);
    }
    return null;
}

function tmdbGetDirectors($data, $json) {
    if (isset($json)) {
//         dlog('got directors from json');
        $cast = [];

        foreach($json->mainColumnData->directors as $director) {
            foreach($director->credits as $credit) {
                $cast[] = $credit->name->nameText->text;
            }
        }
        return join(', ', $cast);
    }

//     dlog('got directors from html');
    // Director
    if (preg_match_all('/ref_=tt_cl_dr_\d+">(.+?)<\/a>/i', $data, $ary, PREG_PATTERN_ORDER)) {
        return trim(join(', ', $ary[1]));
    }
    return null;
}


function tmdbGetTitleAndSubtitleV2($json): array {
    $titles;

    if (isset($json->titleText)) {
        $title = tmdbSplitTitle($json->titleText->text);
        $titles['title'] = $title[0];
        $titles['subtitle'] = $title[1];
    }

    if (isset($json->originalTitleText)) {
        $titles['origtitle'] = $json->originalTitleText->text;
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

function tmdbGetTitleAndSubtitle($data) {
    $titles = [
        'title' => null,
        'subtitle' => null,
        'origtitle' => null
    ];

    // See for different formats. https://contribute.tmdb.com/updates/guide/title_formats
    if (preg_match('/<title>&quot;(.+?)&quot; (.+?) \(.+?\) - IMDb<\/title>/si', $data, $ary)) {
        // handles one episode of a TV serie
        $titles['title'] = $ary[1];
        $titles['subtitle'] = $ary[2];
    } elseif (preg_match('/<title>(.+?) \(.+?\) - IMDb<\/title>/si', $data, $ary)
            || preg_match('/<title>&quot;(.+?)&quot; (.+?) \(.+?\) - IMDb<\/title>/si', $data, $ary)) {

        // split title - subtitle
        list($t, $s) = array_pad(explode(' - ', $ary[1], 2), 2, '');

        // no dash, lets try colon
        if (empty($s)) {
            list($t, $s) = array_pad(explode(': ', $ary[1], 2), 2, '');
        }

        $titles['title'] = trim($t);
        $titles['subtitle'] = trim($s);
    } else {
        preg_match('/<title>(.+?)<\/title>/si', $data, $ary);
        dlog('failed to find title for ' . $ary[1]);
    }

    // orig. title
    if (preg_match('/<div class="sc-.+?">Originaltitel: (.+?)<\/div>/si', $data, $ary)) {
        $titles['origtitle'] = trim($ary[1]);
    }

    return $titles;
}

/**
 * Get Url of Cover Image
 *
 * @author  Roland Obermayer <robelix@gmail.com>
 * @param   string  $data   IMDB Page data
 * @return  string          Cover Image URL
 */
function tmdbGetCoverURL2($data, $json) {
    global $tmdbServer;
    global $CLIENTERROR;
    global $cache;

    if (isset($json)) {
        // dlog('get cover image url from json');
        $url = '';
        if (isset($json->aboveTheFoldData->primaryImage)) {
            $url = $json->aboveTheFoldData->primaryImage->url;
            // dlog("tmdb movie cover: $url");
            $url = str_replace('.jpg', 'QL95_UY600_.jpg', $url);
            // dlog("tmdb movie cover: $url");
        }
        return $url;
    }

    // find cover image url
    if (preg_match('/<a class="ipc-lockup-overlay ipc-focusable" href="(\/title\/tt\d+\/mediaviewer\/\??rm.+?)" aria-label=".*?Poster.*?"><div class="ipc-lockup-overlay__screen"><\/div><\/a>/s', $data, $ary)) {
        // Fetch the image page
        $resp = httpClient($tmdbServer.$ary[1], $cache);

        if ($resp['success']) {
            // get big cover image.
            preg_match('/<div style=".+?" class=".+?"><img src="(.+?)"/si', $resp['data'], $ary);
//             dlog('get cover image url from html');
            // If you want the image to scaled to a certain size you can do this.
            // UX800 sets the width of the image to 800 with correct aspect ratio with regard to height.
            // UY800 set the height to 800 with correct aspect ratio with regard to width.
            return str_replace('.jpg', 'UY800_.jpg', $ary[1]);
            //return trim($ary[1]);
        }
        $CLIENTERROR .= $resp['error'].'\n';
        dlog('no cover url');
        return '';
    }
    // src look something like: src="https://images-na.ssl-images-amazon.com/images/M/MV5BMTc0MDMyMzI2OF5BMl5BanBnXkFtZTcwMzM2OTk1MQ@@._V1_UX214_CR0,0,214,317_AL_.jpg"
    // The last part ._V1_UX214.....jpg seams to be an function that scales the image. Just remove that we want the full size.
    else if (preg_match('/<div.*?class="poster".*?<img.*?src="(.*?\.)_v.*?"/si', $data, $ary)) {
        dlog('get cover image url from html');
        return $ary[1] . '_V1_SY600_CR0,0,600_AL_.jpg';
    }

    // no image
    dlog('no cover url');
    return '';
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

    $resp = httpClient($tmdbServer.'/3/person/'.$actorId, $cache, $param);

    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    $json = json_decode($resp['data']);

    $actorUrl = [];
    $actorUrl[0][0] = 'https://www.themoviedb.org/person/' . $actorId;
    if (isset($json->profile_path)) {
        $actorUrl[0][1] = 'https://image.tmdb.org/t/p/original/' . $json->profile_path;
    }

    return $actorUrl;
}

?>
