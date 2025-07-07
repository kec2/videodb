<?php
/**
 * Fetch all data for either all movies or a specific movie.
 *
 * @package Contrib
 * @author  Alex Mondshain <alex_mond@yahoo.com>
 */

chdir('..');
require_once './engines/engines.php';
require_once './core/functions.php';
require_once './core/genres.php';
require_once './core/custom.php';
require_once './core/edit.core.php';

?>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../<?php echo $config['style'] ?>"
    type="text/css" />
<title>Fetch all data</title>
</head>
<body>
    <h2>Fetch all data</h2>
    Fetch all data for either all movies or a specific movie.
    <br>Leave <b>Video ID</b> empty for fetching for all movies.
    <br>
    <br>
    <h3>All data for all users on all engines are updated!</h3>
    <form>
        Video ID: <input type="text" name="videoId">
        <br>
        Also update genres: <input type="checkbox" name="update_genres" value="1" checked>
        <br>
        Display debug msg: <input type="checkbox" name="debug" value="1">
        <br>
        Use cache: <input type="checkbox" name="use_cache" value="1">
        <br><br>
        <input type="submit" name="submit" value="Fetch">
    </form>
</body>
</html>

<?php
if (isset($submit) && $submit == "Fetch") {
    if (!empty($videoId) && isset($videoId) && is_int(intval($videoId, 10))) {
        $id = intval($videoId, 10);
        echo "<br>Updating ID: ".$id."<br>";

        FetchSaveMovie($id, $update_genres, $debug, $use_cache);
    } else {
        $ids = runSQL('SELECT id FROM '.TBL_DATA);
        foreach ($ids as $id) {
            echo "<br>Updating ID: ".$id['id']."<br>";
            FetchSaveMovie($id['id'], $update_genres, $debug, $use_cache);
        }
    }
    echo "Update done<br>";
}

/**
 * @return void
 */
function FetchSaveMovie($id, $update_genres, $debug, $use_cache) {
    set_time_limit(60);
    // get fields (according to list) from db to be saved later
    $video = runSQL('SELECT * FROM '.TBL_DATA.' WHERE id = '.$id);

    if ($debug) {
        echo "<pre>=================== Video DB Data ============================<br>";
        print_r($video[0]);
        echo "<br>=================== Video DB Data ============================</pre>";
    }

    $imdbID = $video[0]['imdbID'];
    if (empty($imdbID)) {
        echo "No imdbID, exit<br><br><br>";
        return;
    }

    echo "Movie/imdbID -- ".$video[0]['title']."/".$video[0]['imdbID']."<br>";
    if (empty($engine)) {
        $engine = engineGetEngine($imdbID);
    }

    if ($debug) {
        echo "imdbID = $imdbID, engine = $engine<br>";
    }

    $imdbdata = engineGetData($imdbID, $engine, $use_cache);

    if (empty($imdbdata['title'])) {
        echo "Fetch failed, try again...<br>";
        $imdbdata = engineGetData($imdbID, $engine, false); // Never use cache the second time.
    }

    if (empty($imdbdata['title'])) {
        echo "Fetch failed again, exit.<br><br><br>";
        return;
    }

    if ($debug) {
        echo "<pre>=================== IMDB Data ================================<br>";
        print_r($imdbdata);
        echo "<br>=================== IMDB Data ================================</pre>";
    }

    if (!empty($imdbdata['title'])) {
        $video[0]['title']    = $imdbdata['title'];
        $video[0]['subtitle'] = $imdbdata['subtitle'];
        $video[0]['year']     = $imdbdata['year'];
        $video[0]['imgurl']   = $imdbdata['coverurl'];
        $video[0]['runtime']  = $imdbdata['runtime'];
        $video[0]['director'] = $imdbdata['director'];
        $video[0]['rating']   = $imdbdata['rating'];
        $video[0]['country']  = $imdbdata['country'];
        $video[0]['language'] = $imdbdata['language'];
        $video[0]['actors']   = $imdbdata['cast'];
        $video[0]['plot']     = $imdbdata['plot'];
    }

    if ($update_genres) {
        $genres = $imdbdata['genres'];
        if (isset($genres)) {
            foreach ($genres as $genre) {
                // check if genre is found- otherwise fail silently
                if (is_numeric($genreId = getGenreId($genre))) {
                    $video[0]['genres'][] = $genreId;
                } else {
                    echo "UNKNOWN GENRE $genre<br>";
                }
            }
        }
    }

    // custom fields
    for ($i = 1; $i <= 4; $i++) {
        $custom = 'custom'.$i;
        $type = $config[$custom.'type'];
        if (!empty($type) && isset($$type)) {
            // copy imdb data into corresponding custom field
            $$custom = $$type;
            echo "CUSTOM $custom $type = $imdbdata[$type]<br>";
        }
    }

    //  -------- SAVE

    $SETS = prepareSQL($video[0]);

    if ($debug) {
        echo "<pre>=================== Final Data ===============================<br>";
        echo "SETS = ".print_r($SETS, true);
        echo "<br>=================== Final Data ===============================</pre>";
    }

    $id = updateDB($SETS, $id);

    // save genres
    if ($update_genres) {
        setItemGenres($id, $video[0]['genres']);
    }

    // set seen for currently logged in user
    $seen = get_userseen($id);
    set_userseen($id, $seen);
}

?>
