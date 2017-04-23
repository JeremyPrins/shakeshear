<?php
$logfile = fopen('cronlog.txt', 'a');
fwrite($logfile, "\n[" . date("Y-m-d H:i:s") . "] Cronjob ran!");
include_once "inlcludes/connection.php";

// Parameters Last.fm call
$user = "thementaldoctor";
$apiKeyLastfm = "b5cf63047a0dfb6c847cb3a23dce9f34";
$format = "json";
$limit = 10;
$extraInfo = 0;

print "<h2> Laatste " . ($limit) . " nummers </h2>";
$date = date('H:i:s d-m-Y ');
print "Laatste refresh was op " . $date . "<hr>";
//
// Call to last.fm
$urlLastfm = 'http://ws.audioscrobbler.com/2.0/?method=user.getRecentTracks&user=' . $user . '&api_key=' . $apiKeyLastfm . '&limit=' . $limit . '&format=' . $format . '&extended=' . $extraInfo . '';
$lastfmCallResult = file_get_contents($urlLastfm);
$lastfmCallResultDecode = json_decode($lastfmCallResult, true);

$newTimestampArray = [];

// Present last listened tracks
foreach ($lastfmCallResultDecode['recenttracks']['track'] as $index => $item) {
    if (!empty($item['@attr'])) {
        print "Now playing : " . $item['name'] . " | " . $item['artist']['#text'] . '<hr>';
    } else {
        $newTimestampArray[] = $item['date']["uts"];
        print $item['name'] . " - " . $item['artist']['#text'] . " | " . $item['date']["uts"] . '<br>';
    }
}
print "<hr>";

// Retrieve old timestamps
$oldTimestampQuery = "SELECT `timestamp` FROM `trackcheck`";
$result = mysqli_query($conn, $oldTimestampQuery);

$oldTimestampArray = [];
if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while ($row = mysqli_fetch_assoc($result)) {
        $oldTimestampArray[] = $row['timestamp'];
    }
} else {
    echo "0 results";
}

echo "OLD TIMESTAMPS";
echo "<pre>";
print_r($oldTimestampArray);
echo "</pre>";
echo "NEW TIMESTAMPS";
echo "<pre>";
print_r($newTimestampArray);
echo "</pre>";

// Work out new song since the last check
$newTracks = array_diff($newTimestampArray, $oldTimestampArray);
print "Tracks to process -> ";
print_r($newTracks);
print "<hr>";

// Update timestamp table for next check
$checkCount = 0;
foreach ($newTimestampArray as $trackTimestamp) {
    $checkCount++;
    $insertTimestamps = "UPDATE `trackcheck` SET `timestamp` = $trackTimestamp WHERE `id` = $checkCount";

    $performInsert = mysqli_query($conn, $insertTimestamps);
    if ($performInsert) {
//        echo "Timestamp inserted --> " . $insertTimestamps . "<br>";
    } else {
        echo "Error --> " . $insertTimestamps . " <br>" . $conn->error;
    }
}

foreach ($lastfmCallResultDecode['recenttracks']['track'] as $index => $item) {
    //print_r($item);
    if (isset($item['@attr'])) {
        echo "User is currently listening to " . $item['name'] . " by " . $item['artist']['#text'];
    } else if ($item['date']['uts'] > $oldTimestampArray[0]) {
        echo "This listen for " . $item['name'] . " by " . $item['artist']['#text'] . " has finished, adding to db.";

        //add new song's words to userwordcount


        // Laatste nummmer
        $lastsong_name = $item['name'];
// Laatste artiest
        $lastsong_artist = $item['artist']['#text'];

        $artistMusixmatch = $lastsong_artist;
        $trackMusixmatch = $lastsong_name;
        $artistMusixmatch = str_replace(' ', '%20', $artistMusixmatch);
        $trackMusixmatch = str_replace(' ', '%20', $trackMusixmatch);

        ?>

        <hr>
        <h2>Musixmatch</h2>
        <h3>Artist = <?= $lastsong_artist ?></h3>
        <h3>Song = <?= $lastsong_name ?></h3>

        <?php


// Parameters Musixmatch call
        $apiKeyMusixmatch = "127714d616b8ff66c2a79e0518535843";

//  Musixmatch call
        $urlMusixmatch = 'http://api.musixmatch.com/ws/1.1/track.search?apikey=' . $apiKeyMusixmatch . '&q_artist=' . $artistMusixmatch . '&q_track=' . $trackMusixmatch . '';
        $contentMusixmatch = file_get_contents($urlMusixmatch);
        $musixmatchResponse = json_decode($contentMusixmatch, true);

        $musixmatchTrackId = $musixmatchResponse["message"]["body"]["track_list"][0]["track"]["track_id"];
        $hasLyrics = $musixmatchResponse["message"]["body"]["track_list"][0]["track"]["has_lyrics"];

        print "<hr>";
        print "TrackId: " . $musixmatchTrackId;
        print "<br>";

        if ($hasLyrics == 1) {
            $urlMusixmatchLyrics = 'http://api.musixmatch.com/ws/1.1/track.lyrics.get?track_id=' . $musixmatchTrackId . '&apikey=' . $apiKeyMusixmatch . '';
            $content = file_get_contents($urlMusixmatchLyrics);
            $musixmatchLyricBodyResponse = json_decode($content, true);

            $trackLyrics = $musixmatchLyricBodyResponse["message"]["body"]["lyrics"]["lyrics_body"];

            print "Originele lyrics <br>" . $trackLyrics;
            print "<hr>";

            $trackLyricsFiltered = strtolower(substr($trackLyrics, 0, strpos($trackLyrics, '*******')));

            print $trackLyricsFiltered;

            $uniqueWords = array_count_values(str_word_count($trackLyricsFiltered, 1));
            arsort($uniqueWords);

            $totalWords = count($uniqueWords);

            print "<hr>";
            print "<h2>Unieke woorden: $totalWords </h2> ";
            print "<hr>";

            echo "<pre>";
            print_r($uniqueWords);
            echo "</pre>";


            $insertWords = "";
            $i = 0;
            foreach ($uniqueWords as $word => $wordCount) {
                $i++;
                $word = mysqli_real_escape_string($conn, $word);
                $insertWords = "INSERT INTO `userwords` ( `user_word`, `user_word_count`) VALUES ('$word', $wordCount) ON DUPLICATE KEY UPDATE `user_word_count` = `user_word_count` + $wordCount";

                $performInsert = mysqli_query($conn, $insertWords);
                if ($performInsert) {

                    echo "Word inserted ---> " . $i . " - " . $insertWords . "<br>";
                } else {
                    echo "Error: " . $insertWords . " <br>" . $conn->error;
                }
            };

        } else {
            print "<h2> Dit is een instrumentaal nummer </h2>";
        }

    }
    echo "<br><br>";
}

// Retrieve all words  in Hamlet
$fetchHamletQuery = "SELECT `words`, `count`FROM `hamletwords`";
$result = mysqli_query($conn, $fetchHamletQuery);

$hamletArray = [];
if (mysqli_num_rows($result) > 0) {
    $result = mysqli_query($conn, $fetchHamletQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $hamletArray[] = $row;
    }
} else {
    echo "0 results";
}


// Retrieve all userwords
$fetchUserQuery = "SELECT `user_word`, `user_word_count`FROM `userwords`";
$result = mysqli_query($conn, $fetchUserQuery);

$userArray = [];
if (mysqli_num_rows($result) > 0) {
    $result2 = mysqli_query($conn, $fetchUserQuery);
    while ($row = mysqli_fetch_assoc($result)) {
        $userArray[] = $row;
    }
} else {
    echo "0 results";
}

print  "<hr>";


//echo "<pre>";
//print_r($userArray);
//echo "</pre>";
//echo "<pre>";
//print_r($hamletArray);
//echo "</pre>";


$intersectArray = [];
foreach ($userArray as $data1) {
    $duplicate = true;
    foreach ($hamletArray as $data2) {
        if ($data1['user_word'] === $data2['words'])
            $duplicate = false;
    }

    if ($duplicate === false) $intersectArray[] = $data1;
}

//echo "matching words array";
//echo "<pre>";
//print_r($intersectArray);
//echo "</pre>";

/*
echo "hamlet array";
echo "<pre>";
print_r($hamletArray);
echo "</pre>";
*/

$correctCount = [];
foreach ($intersectArray as $key => $data) {
    foreach ($hamletArray as $keyham => $dataham) {
        if ($data['user_word'] == $dataham['words'] && $data['user_word_count'] >= $dataham['count']) {

            $data['user_word_count'] = $dataham['count'];
        }
    }
    $correctCount[] = $data;

}

//echo "<pre>";
//print_r($correctCount);
//echo "</pre>";

$totalUserWords = array_sum(array_column($correctCount, 'user_word_count'));
$totalHamletWords = array_sum(array_column($hamletArray, 'count'));

echo $totalUserWords;
echo "<hr>";
echo $totalHamletWords;
$total = $totalUserWords / $totalHamletWords * 100;
echo "<hr>";

echo $matchPercentage = number_format((float)$total, 2, '.', '');

$insertPercentage = "INSERT INTO `matchpercentage` (`percentage`) VALUES ($matchPercentage)";

$performInsert = mysqli_query($conn, $insertPercentage);

if (!$performInsert) {
    echo mysqli_error($conn);
}

?>