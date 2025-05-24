<?php
/**
 * IMDB Parser
 *
 * Parses data from the Internet Movie Database
 *
 * @package Engines
 * @author  Andreas Gohr    <a.gohr@web.de>
 * @link    http://www.imdb.com  Internet Movie Database
 */

$GLOBALS['imdbServer'] = 'https://www.imdb.com';
$GLOBALS['imdbIdPrefix'] = 'imdb:';

/**
 *  Get meta information about the engine
 *
 * @todo Include image search capabilities etc in meta information
 *
 * @return (int|string|string[])[]
 *
 * @psalm-return array{name: 'IMDb', stable: 1, php: '8.1.0', capabilities: array{0: 'movie', 1: 'image'}}
 */
function imdbMeta(): array {
    return array('name' => 'IMDb', 'stable' => 1, 'php' => '8.1.0', 'capabilities' => array('movie', 'image'));
}


/**
 * Get Url to search IMDB for a movie
 *
 * @author  Andreas Goetz <cpuidle@gmx.de>
 * @param   string    The search string
 * @return  string    The search URL (GET)
 */
function imdbSearchUrl($title)
{
    global $imdbServer;
    return $imdbServer.'/find?s=tt&q='.urlencode($title);
}

/**
 * Get Url to visit IMDB for a specific movie
 *
 * @author  Andreas Goetz <cpuidle@gmx.de>
 * @param   string  $id The movie's external id
 * @return  string      The visit URL
 */
function imdbContentUrl($id)
{
    global $imdbServer;
    global $imdbIdPrefix;

    $id = preg_replace('/^'.$imdbIdPrefix.'/', '', $id);

    return $imdbServer.'/title/tt'.$id.'/';
}

/**
 * Get IMDB recommendations for a specific movie that meets the requirements
 * of rating and release year.
 *
 * @author  Klaus Christiansen <klaus_edwin@hotmail.com>
 * @param   int     $id      The external movie id.
 * @param   float   $rating  The minimum rating for the recommended movies.
 * @param   int     $year    The minimum year for the recommended movies.
 * @return  array            Associative array with: id, title, rating, year.
 *                           If error: $CLIENTERROR contains the http error and blank is returned.
 */
function imdbRecommendations($id, $required_rating, $required_year)
{
    global $CLIENTERROR;

    $url = imdbContentUrl($id);
    $resp = httpClient($url, true);

    $recommendations = array();

    // get sections of recommendation
    preg_match('/<section data-testid="MoreLikeThis"(.+?)<\/section>/si', $resp['data'], $rec_block);
    preg_match_all('/<a class="ipc-lockup-overlay ipc-focusable" href="\/title\/tt(\d+)\/\?ref_=tt_sims_tt_i_\d+" aria-label="View title page for.+?">/si', $rec_block[1], $ary, PREG_SET_ORDER);

    foreach ($ary as $recommended_id) {
        $imdbId = $recommended_id[1];

        $rec_resp = getRecommendationData($imdbId);
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

function getRecommendationData($imdbID) {
    global $imdbServer;
    global $imdbIdPrefix;
    global $CLIENTERROR;

    // fetch mainpage
    $resp = httpClient($imdbServer.'/title/tt'.$imdbID.'/', true);     // added trailing / to avoid redirect
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // Titles and Year
    // See for different formats. https://contribute.imdb.com/updates/guide/title_formats
    if (isset($data['istv'])) {
        if (preg_match('/<title>&quot;(.+?)&quot;(.+?)\(TV Episode (\d+)\) - IMDb<\/title>/si', $resp['data'], $ary)) {
            // handles one episode of a TV serie
            $data['title'] = trim($ary[1]);
            $data['year'] = $ary[3];
        } else if (preg_match('/<title>(.+?)\(TV Series (\d+).+?<\/title>/si', $resp['data'], $ary)) {
            $data['title'] = trim($ary[1]);
            $data['year'] = trim($ary[2]);
        }
    } else {
        preg_match('/<title>(.+?)\(.*?(\d+)\).+?<\/title>/si', $resp['data'], $ary);
        $data['title'] = trim($ary[1]);
        $data['year'] = trim($ary[2]);
    }

    // Rating
    if (preg_match('/<div data-testid="hero-rating-bar__aggregate-rating__score" class="sc-.+?"><span class="sc-.+?">(.+?)<\/span><span>\/<!-- -->10<\/span><\/div>/si', $resp['data'], $ary)) {
        $data['rating'] = trim($ary[1]);
    }

    return $data;
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
 * @param   boolean aka     Use AKA search for foreign language titles
 * @return  array           Associative array with id and title
 */
function imdbSearch($title, $aka=null)
{
    global $imdbServer;
    global $imdbIdPrefix;
    global $CLIENTERROR;
    global $cache;

    $url = imdbSearchUrl($title);

    if ($aka) {
        $url .= '&site=aka';
    }

    $resp = httpClient($url, $cache);
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    $data = array();

    // add encoding
    $data['encoding'] = $resp['encoding'];

    // direct match (redirecting to individual title)?
    if (preg_match('/^'.preg_quote($imdbServer,'/').'\/[Tt]itle(\?|\/tt)([0-9?]+)\/?/', $resp['url'], $single)) {
        $info = array();
        $info['id'] = $imdbIdPrefix.$single[2];

        // Title
        preg_match('/<title>(.*?) \([1-2][0-9][0-9][0-9].*?\)<\/title>/i', $resp['data'], $m);
        list($t, $s) = explode(' - ', trim($m[1]), 2);
        $info['title'] = trim($t);
        $info['subtitle'] = trim($s);

        $data[] = $info;
    }
    // multiple matches
    elseif (preg_match('/<section data-testid="find-results-section-title"(.+?)<\/section>/si', $resp['data'], $match)) {
        if (preg_match_all('/<a .+? href="\/title\/tt(\d+)\/.+?">(.+?)<\/a><ul .+?"><li .+?><span .+?>(\d+)<\/span><\/li>/i', $match[0], $rows, PREG_SET_ORDER)) {
            foreach ($rows as $row) {
                $info = [];
                $info['id'] = $imdbIdPrefix.$row[1];
                $info['title'] = $row[2];
                $info['year'] = $row[3];
                $data[] = $info;
            }
        }
    }

    return $data;
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
function imdbData($imdbID)
{
    global $imdbServer;
    global $imdbIdPrefix;
    global $CLIENTERROR;
    global $cache;

    $imdbID = preg_replace('/^'.$imdbIdPrefix.'/', '', $imdbID);
    $data = []; // result
    $ary = []; // temp

    // fetch mainpage
    $resp = httpClient($imdbServer.'/title/tt'.$imdbID.'/', $cache); // added trailing / to avoid redirect
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // add encoding
    $data['encoding'] = $resp['encoding'];

    $json = getPagePropsJson($imdbID, $resp['data']);

    $data['istv'] = imdbIsTV($json);
    if ($data['istv']) {
        // get the id from the main tv show. Not the episode
        $data['tvseries_id'] = imdbGetSeriesId($resp['data'], $json);
    }

    $data['year'] = imdbGetYear($resp['data'], $json);

    $titles = imdbGetTitleAndSubtitle($resp['data']);
    $data['title'] = $titles['title'];
    $data['subtitle'] = $titles['subtitle'];
    $data['origtitle'] = $titles['origtitle'];

    // Cover URL
    $data['coverurl'] = imdbGetCoverURL($resp['data'], $json);

    // MPAA Rating
    $data['mpaa'] = imdbGetParentalGuide($resp['data'], $json);

    // Runtime
    $data['runtime'] = getRuntime($resp['data'], $json);

    // Director
    $completeCast = imdbGetCastV2($imdbID, $json);

    $data['director'] = $completeCast['director'];
    $data['creator'] = $completeCast['creator'];
    $data['writer'] = $completeCast['writer'];

    // Rating
    $data['rating'] = imdbGetRating($resp['data'], $json);

    // Countries
    $data['country'] = imdbGetCountries($resp['data'], $json);

    // Languages
    $data['language'] = imdbGetLanguages($resp['data'], $json);

    // Genres (as Array)
    $data['genres'] = imdbGetGenres($resp['data'], $json);

    // Plot
    $data['plot'] = imdbGetPlot($resp['data'], $json);

    // Cast
    $data['cast'] = $completeCast['cast'];

    return $data;
}

function getPagePropsJson($imdbID, $data) {
    if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">([^<]*)<\/script>/si', $data, $ary)) {
        try {
            $json = json_decode($ary[1]);
        }   catch (Exception $e) {
            dlog("Json error, imdbid: $imdbID, ". json_last_error() .", " . json_last_error_msg());
            dlog($ary[1]);
        }
//         dlog("Found json for $imdbID");

        return $json->props->pageProps;
    } else {
        dlog("Did not find any json for $imdbID");
    }
    return null;
}

function imdbGetOriginalTitleV2($json) {
    if (isset($json->originalTitleText)) {
        return $json->originalTitleText->text;
    }
    return null;
}

function imdbIsTV($json) {
    if (isset($json)
            && isset($json->aboveTheFoldData->titleType)
            && ($json->aboveTheFoldData->titleType->isSeries
                || $json->aboveTheFoldData->titleType->isEpisode)) {
        return 1;
    }
    return 0;
}

function imdbGetSeriesId($data, $json) {
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
        if (preg_match('/<meta property="imdb\:pageConst" content="tt(\d+)">/', $data, $ary)) {
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

function imdbGetGenres($data, $json) {
    // going through main page gives all genres
    // going through full credits gives three genres
    if (isset($json) && isset($json->aboveTheFoldData->genres->genres)) {
//         dlog('get genres from json');
        $genres = [];
        foreach($json->aboveTheFoldData->genres->genres as $genre) {
            $genres[] = $genre->text;
        }
        // this is so test dont break
        return array_slice($genres, 0, 3);
    }
//     dlog('get genres from html');

    if (preg_match_all('/<a class=".+?" href="\/search\/title\?genres=.+?"><span class="i.+?">(.+?)<\/span><\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        $genres = [];
        foreach($ary[1] as $genre) {
            $genres[] = trim($genre);
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
function imdbGetParentalGuide($data, $json) {
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

function imdbGetCountries($data, $json) {
    // going through main page gives all countries
    // going through full credits only gets some countries
    if (isset($json)) {
        dlog('got countries from json');
        $countries = [];
        foreach($json->mainColumnData->countriesDetails->countries as $country) {
            $countries[] = $country->text;
        }
        return join(', ', $countries);
    } elseif (preg_match_all('/href="\/search\/title\/\?country_of_origin.+?>(.+?)<\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        dlog('got countries from html');
        return trim(join(', ', $ary[1]));
    }
    return null;
}

/*
 * @param string $imdbID    is the is the ID of the movie
 */
function imdbGetCastV2($imdbID, $json) {
    global $imdbIdPrefix;
    global $imdbServer;
    global $cache;

    // Fetch credits
    $resp = httpClient($imdbServer.'/title/tt'.$imdbID.'/fullcredits', $cache);
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error'].'\n';
    }

    $completeCast = [];

    $json = getPagePropsJson($imdbID, $resp['data']);

    if (isset($json)) {
        foreach($json->contentData->categories as $cats) {
            if ($cats->id == 'cast') {
                $pageSize = $cats->pagination->queryVariables->first;
                $total = $cats->section->total;
                if ($total > $pageSize) {
                    dlog("Not all cast are included");
                    $completeCast['cast'] = imdbGetCast($imdbID, $resp['data']);
                    break;
                }

                $cast = '';
                foreach($cats->section->items as $item) {
                    $actorId = $item->id;
                    $actor = $item->rowTitle;

                    if (is_array($item->characters)) {
                        $role = implode(" / ", $item->characters);
                        if ($item->attributes) {
                            $role .= " " . $item->attributes;
                        }
                    } else {
                        $role = $item->attributes;
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

                    $cast .= "$actor::$role::$imdbIdPrefix$actorId\n";
                }

                $completeCast['cast'] = $cast;
            } elseif ($cats->id == 'director') {
                $directors = [];
                foreach($cats->section->items as $item) {
                   $directors[] = $item->rowTitle;
                }
                $dirs = implode(', ', $directors);
                if (strlen($dirs) > 250) {
                    dlog("WARNING: Directors string to long(250). $imdbID");
                }
               $completeCast['director'] = substr($dirs, 0, 250);
            } elseif ($cats->id == 'writer') {
                $writers = [];
                foreach($cats->section->items as $item) {
                   $writers[] = $item->rowTitle;
                }
               $completeCast['writer'] = implode(', ', $writers);
            } elseif ($cats->id == 'creator') {
                $creators = [];
                foreach($cats->section->items as $item) {
                   $creators[] = $item->rowTitle;
                }
                $completeCast['creator'] = implode(', ', $creators);
            }
        }

        return $completeCast;
    }

    $cast = getImdbCast($imdbID, $resp['data']);

    dlog('Failed to find a cast for:' . $imdbID);
    return $cast;
}

/*
 * @param string $imdbID    is the is the ID of the movie
 */
function imdbGetCast($imdbID, $data) {
    global $imdbIdPrefix;
    global $cache;

    $cast = '';
    $after = '';

    do {
        $url = 'https://caching.graphql.imdb.com/?operationName=TitleCreditSubPagePagination&variables={"after":"'.$after.'","category":"cast","const":"tt'.$imdbID.'","first":250,"locale":"en-US","originalTitleText":false,"tconst":"tt'.$imdbID.'"}&extensions={"persistedQuery":{"sha256Hash":"716fbcc1b308c56db263f69e4fd0499d4d99ce1775fb6ca75a75c63e2c86e89c","version":1}}';

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
            if ($edge->node->episodeCredits) {
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

            $cast .= "$actor::$role::$imdbIdPrefix$actorId\n";
        }
        $after = $credits->pageInfo->endCursor;
    } while ($credits->pageInfo->hasNextPage);

    return $cast;
}

function imdbGetPlot($data, $json) {
    if (isset($json) && isset($json->aboveTheFoldData->plot)) {
//         dlog('get plot from json');
        return $json->aboveTheFoldData->plot->plotText->plainText;
    }
//     dlog('get plot from html');

    // Plot
    // it seams that imdb has three version of the plot: xs_to_m (extra small - medium), l (large) and xl (extra large)
    // this return the first which is proberly xs_to_m.
    if (preg_match('/<p data-testid="plot" .+?>.+?<span role="presentation" data-testid="plot-.+?".+?>(.+?)<\/span></si', $data, $ary)) {
        return $ary[1];
    }
    return null;
}

function imdbGetLanguages($data, $json) {
//     dlog("imdbId: " .$json->aboveTheFoldData->id);
    if (isset($json)) {
        // might not be there for an serie episode
        $languages = [];
        foreach($json->mainColumnData->spokenLanguages->spokenLanguages as $language) {
            $languages[] = strtolower($language->text);
        }
        if (sizeof($languages) > 0) {
//             dlog('got languages from json');
            return join(', ', $languages);
        }
    }

//     dlog('got languages from html');
    if (preg_match_all('/primary_language.+?ref_=tt_dt_ln">(.+?)<\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        return trim(strtolower(join(', ', $ary[1])));
    } elseif (preg_match('/<script .+?"inLanguage":"(.+?)",/si', $data, $ary)) { // this is wrong
        return strtolower($ary[1]);
    }
    return null;
}

function imdbGetRating($data, $json) {
    if (isset($json) && isset($json->aboveTheFoldData->ratingsSummary)) {
//         dlog('got rating from json');
        return $json->aboveTheFoldData->ratingsSummary->aggregateRating;
    } elseif (preg_match('/<div data-testid="hero-rating-bar__aggregate-rating__score" class="sc-.+?"><span class="sc-.+?">(.+?)<\/span><span>\/<!-- -->10<\/span><\/div>/si', $data, $ary)) {
//         dlog('got rating from html');
        return trim($ary[1]);
    }
    return null;
}

function imdbGetDirectors($data, $json) {
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

function imdbGetYear($data, $json) {
    if (isset($json) && isset($json->aboveTheFoldData->releaseYear)) {
//         dlog('get year from json');
        return $json->aboveTheFoldData->releaseYear->year;
    } elseif (preg_match('/<title>.+? \(.*?(\d{4}).*?\) - IMDb<\/title>/', $data, $ary)) {
//         dlog('get year from html');
        return $ary[1];
    }

    return null;
}

function imdbGetTitleAndSubtitleV2($json): array {
    $titles;

    if (isset($json->titleText)) {
        $title = imdbSplitTitle($json->titleText->text);
        $titles['title'] = $title[0];
        $titles['subtitle'] = $title[1];
    }

    if (isset($json->originalTitleText)) {
        $titles['origtitle'] = $json->originalTitleText->text;
    }

    return $titles;
}

function imdbSplitTitle($input): array {
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

function imdbGetTitleAndSubtitle($data) {
    $titles = [
        'title' => null,
        'subtitle' => null,
        'origtitle' => null
    ];

    // See for different formats. https://contribute.imdb.com/updates/guide/title_formats
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
function imdbGetCoverURL($data, $json) {
    global $imdbServer;
    global $CLIENTERROR;
    global $cache;

    if (isset($json) && isset($json->aboveTheFoldData->primaryImage)) {
//         dlog('get cover image url from json');
        return $json->aboveTheFoldData->primaryImage->url;
    }

    // find cover image url
    if (preg_match('/<a class="ipc-lockup-overlay ipc-focusable" href="(\/title\/tt\d+\/mediaviewer\/\??rm.+?)" aria-label=".*?Poster.*?"><div class="ipc-lockup-overlay__screen"><\/div><\/a>/s', $data, $ary)) {
        // Fetch the image page
        $resp = httpClient($imdbServer.$ary[1], $cache);

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

function getRuntime($data, $json) {
    if (isset($json) && isset($json->aboveTheFoldData->runtime)) {
//         dlog('get runtime from json');
        return $json->aboveTheFoldData->runtime->seconds / 60;
    } elseif (preg_match('/<script .+?>{.+?tt.+?,"runtime":{"seconds":(\d+)/', $data, $ary)) {
//         dlog('get runtime from html');
        return $ary[1] / 60;
    }

    return 0;
}

/**
 * Get Url to visit IMDB for a specific actor
 *
 * @author  Michael Kollmann <acidity@online.de>
 * @param   string  $name   The actor's name
 * @param   string  $id The actor's external id
 * @return  string      The visit URL
 */
function imdbActorUrl($name, $id)
{
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
 * @author                Andreas Goetz <cpuidle@gmx.de>
 * @param  string  $name  Name of the Actor
 * @return array          array with Actor-URL and Thumbnail
 */
function imdbActor($name, $actorid)
{
    global $imdbServer;
    global $cache;

    // search directly by id or via name?
    $resp = httpClient(imdbActorUrl($name, $actorid), $cache);

    // if not direct match load best match
    if (preg_match('#<b>Popular Names</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)
            || preg_match('#<b>Names \(Exact Matches\)</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)
            || preg_match('#<b>Names \(Approx Matches\)</b>.+?<a\s+href="(.*?)">#i', $resp['data'], $m)) {

        if (!preg_match('/http/i', $m[1])) {
            $m[1] = $imdbServer.$m[1];
        }
        $resp = httpClient($m[1], true);
    }

    $ary = [];
    // now we should have loaded the best match
    if (preg_match('/<div class=".+? ipc-poster--baseAlt .+?<img.+?src="(https.+?)".+?href="(\/name\/nm\d+\/)/si', $resp['data'], $m)) {
        $ary[0][0] = $m[2]; // /name/nm12345678/
        $ary[0][1] = $m[1]; // img url
    }

    return $ary;
}

?>
