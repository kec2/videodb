<?php
/**
 * IMDB Parser
 *
 * Parses data from the Internet Movie Database
 *
 * @package Engines
 * @author  Andreas Gohr    <a.gohr@web.de>
 * @link    http://www.imdb.com  Internet Movie Database
 * @version $Id: imdb.php,v 1.76 2013/04/10 18:11:43 andig2 Exp $
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
            # handles one episode of a TV serie
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
    $data= array(); // result
    $ary = array(); // temp

    // fetch mainpage
    $resp = httpClient($imdbServer.'/title/tt'.$imdbID.'/', $cache);     // added trailing / to avoid redirect
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    // add encoding
    $data['encoding'] = $resp['encoding'];

    # <meta property="og:type" content="video.tv_show">
    # <meta property="og:type" content="video.episode">
    # <meta property="og:type" content="video.tv_show"/>
    if (preg_match('/<meta property="og:type" content="video\.(episode|tv_show)"\/?>/si', $resp['data'])) {
        $data['istv'] = 1;

        // find id of Series
        // Either it is an episode
        if (preg_match('/<a .+? data-testid="hero-title-block__series-link" href="\/title\/tt(\d+)\/\?ref_=tt_ov_inf">/si', $resp['data'], $ary)) {
            $data['tvseries_id'] = trim($ary[1]);
        } else {
            // Or it is the main page
            $data['tvseries_id'] = $imdbID;
        }
    }

    $data['year'] = imdbGetYear($resp['data']);

    $titles = imdbGetTitleAndSubtitle($resp['data']);
    $data['title'] = $titles['title'];
    $data['subtitle'] = $titles['subtitle'];
    $data['origtitle'] = $titles['origtitle'];

    // Cover URL
    $data['coverurl'] = imdbGetCoverURL($resp['data']);

    // MPAA Rating
    $data['mpaa'] = imdbGetParentalGuide($resp['data']);

    // Runtime
    $data['runtime'] = getRuntime($resp['data']);

    // Director
    $data['director'] = imdbGetDirectors($resp['data']);

    // Rating
    $data['rating'] = imdbGetRating($resp['data']);

    // Countries
    $data['country'] = imdbGetCountries($resp['data']);

    // Languages
    $data['language'] = imdbGetLanguages($resp['data']);

    // Genres (as Array)
    $data['genres'] = imdbGetGenres($resp['data']);

    // Plot
    $data['plot'] = imdbGetPlot($resp['data']);

    // Cast
    $data['cast'] = imdbGetCast($imdbID);

    return $data;
}

function imdbGetGenres($data) {
/*
<a href="show.php?id=1048" class="th radius">
					<!-- Uncomment this if you want to use lazy-load together with image thumbnails - suited for mobile access -->
					</a>
*/
    $genres = [];
    if (preg_match_all('/<a class=".+?" href="\/search\/title\?genres=.+?"><span class="i.+?">(.+?)<\/span><\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        foreach($ary[1] as $genre) {
            $genres[] = trim($genre);
        }
        return $genres;
    } elseif (preg_match('/<script .+?>.+?"genre":\[(.+?)].+<\/script>/si', $data, $ary)) {
        $genreStr = str_replace('"', '', $ary[1]);
        $genres = explode(',', $genreStr);
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
function imdbGetParentalGuide($data) {
    // MPAA Rating
    if (preg_match('#<a .+? href="/title/tt\d+/parentalguide/certificates\?ref_=tt_ov_pg">(.+?)</a>#is', $data, $ary)) {
        return trim($ary[1]);
    }
    return null;
}

function imdbGetCountries($data) {
    // Countries
    if (preg_match_all('/href="\/search\/title\/\?country_of_origin.+?>(.+?)<\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        return trim(join(', ', $ary[1]));
    }
    return null;
}

/*
 * @param string $imdbID    is the is the ID of the movie
 */
function imdbGetCast($imdbID) {
    global $imdbIdPrefix;
    global $imdbServer;
    global $cache;

    // Fetch credits
    $resp = httpClient($imdbServer.'/title/tt'.$imdbID.'/fullcredits', $cache);
    if (!$resp['success']) {
        $CLIENTERROR .= $resp['error']."\n";
    }

    if (preg_match('#<table class="cast_list">(.*)#si', $resp['data'], $match)) {
        $cast = '';
        // no idea why it does not always work with (.*?)</table
        // could be some maximum length of .*?
        // anyways, I'm cutting it here
        $casthtml = substr($match[1], 0, strpos($match[1], '</table'));
        if (preg_match_all('#<td class="primary_photo">\s+<a href="/name/(nm\d+)/?.*?".+?<a .+?>(.+?)</a>.+?<td class="character">(.*?)</td>#si', $casthtml, $ary, PREG_PATTERN_ORDER)) {
            for ($i = 0; $i < sizeof($ary[0]); $i++) {
                $actorid = trim(strip_tags($ary[1][$i]));
                $actor = trim(strip_tags($ary[2][$i]));

                // make spaces, tabs and newlines into spaces
                $character = preg_replace('/\s/', ' ', $ary[3][$i]);
                // change HTML brake space into space.
                $character = preg_replace('/&nbsp;/', ' ', $character);
                // make multiple spaces into a single space
                $character = preg_replace('/\s+/', ' ', $character);
                // replace U+0092 : <control> PRIVATE USE TWO [PU2] with single quote
                $character = preg_replace('/[\x00\x92]/u', '&#039;', $character);
                // sometimes appearing in series (e.g. Scrubs)
                $character = preg_replace('#/ ... #', '', $character);
                $character = trim(strip_tags($character));

                $cast .= "$actor::$character::$imdbIdPrefix$actorid\n";
            }
        }

        // remove html entities and replace &nbsp; with simple space
        return html_clean_utf8($cast);
    }
    dlog('Failed to find a cast for:' . $imdbID);
    return null;
}

function imdbGetPlot($data) {
    // Plot
    // it seams that imdb has three version of the plot: xs_to_m (extra small - medium), l (large) and xl (extra large)
    // this return the first which is proberly xs_to_m.
    if (preg_match('/<p data-testid="plot" .+?>.+?<span role="presentation" data-testid="plot-.+?".+?>(.+?)<\/span></si', $data, $ary)) {
        return $ary[1];
    }
    return null;
}

function imdbGetLanguages($data) {
    // Languages
    if (preg_match_all('/primary_language.+?ref_=tt_dt_ln">(.+?)<\/a>/si', $data, $ary, PREG_PATTERN_ORDER)) {
        return trim(strtolower(join(', ', $ary[1])));
    } elseif (preg_match('/<script .+?"inLanguage":"(.+?)",/si', $data, $ary)) {
        return strtolower($ary[1]);
    }
    return null;
}

function imdbGetRating($data) {
    // Rating
    if (preg_match('/<div data-testid="hero-rating-bar__aggregate-rating__score" class="sc-.+?"><span class="sc-.+?">(.+?)<\/span><span>\/<!-- -->10<\/span><\/div>/si', $data, $ary)) {
        return trim($ary[1]);
    }
    return null;
}

function imdbGetDirectors($data) {
    // Director
    if (preg_match_all('/ref_=tt_[a-z]{2}_dr_\d+">(.+?)<\/a>/i', $data, $ary, PREG_PATTERN_ORDER)) {
        return trim(join(', ', array_unique($ary[1])));
    }
    return null;
}

function imdbGetYear($data) {
    if (preg_match('/<title>.+? \(.*?(\d{4}).*?\) - IMDb<\/title>/', $data, $ary)) {
        return $ary[1];
    }

    return null;
}

function imdbGetTitleAndSubtitle($data) {

    $titles = [
        'title' => null,
        'subtitle' => null,
        'origtitle' => null
    ];

    // See for different formats. https://contribute.imdb.com/updates/guide/title_formats
    if (preg_match('/<title>&quot;(.+?)&quot; (.+?) \(.+?\) - IMDb<\/title>/si', $data, $ary)) {
        # handles one episode of a TV serie
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
        dlog("failed to find title for " . $ary[1]);
    }

    # orig. title
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
function imdbGetCoverURL($data) {
    global $imdbServer;
    global $CLIENTERROR;
    global $cache;

    // find cover image url
    if (preg_match('/<a class="ipc-lockup-overlay ipc-focusable" href="(\/title\/tt\d+\/mediaviewer\/\??rm.+?)" aria-label=".*?Poster.*?"><div class="ipc-lockup-overlay__screen"><\/div><\/a>/s', $data, $ary)) {
        // Fetch the image page
        $resp = httpClient($imdbServer.$ary[1], $cache);

        if ($resp['success']) {
            // get big cover image.
            preg_match('/<div style=".+?" class=".+?"><img src="(.+?)"/si', $resp['data'], $ary);
            // If you want the image to scaled to a certain size you can do this.
            // UX800 sets the width of the image to 800 with correct aspect ratio with regard to height.
            // UY800 set the height to 800 with correct aspect ratio with regard to width.
            return str_replace('.jpg', 'UY800_.jpg', $ary[1]);
            //return trim($ary[1]);
        }
        $CLIENTERROR .= $resp['error']."\n";
        return '';
    }
    // src look somthing like: src="https://images-na.ssl-images-amazon.com/images/M/MV5BMTc0MDMyMzI2OF5BMl5BanBnXkFtZTcwMzM2OTk1MQ@@._V1_UX214_CR0,0,214,317_AL_.jpg"
    // The last part ._V1_UX214.....jpg seams to be an function that scales the image. Just remove that we want the full size.
    else if (preg_match('/<div.*?class="poster".*?<img.*?src="(.*?\.)_v.*?"/si', $data, $ary)) {
        return $ary[1]."_V1_SY600_CR0,0,600_AL_.jpg";
    } else {
        # no image
        return '';
    }
}

function getRuntime($data) {
    if (preg_match('/<script .+?>{.+?tt.+?,"runtime":{"seconds":(\d+)/', $data, $ary)) {
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

    // now we should have loaded the best match

    $ary = [];
    if (preg_match('/<div class=".+? ipc-poster--baseAlt .+?<img.+?src="(https.+?)".+?href="(\/name\/nm\d+\/)/si', $resp['data'], $m)) {
        $ary[0][0] = $m[2]; // /name/nm12345678/
        $ary[0][1] = $m[1]; // img url
    }

    return $ary;
}

?>
