<?php
include_once "inlcludes/connection.php";

$getPercentageQuery = "SELECT `percentage` FROM `matchpercentage`ORDER BY `percentage` ASC;";
$percentage = mysqli_query($conn, $getPercentageQuery);

while ($row = $percentage->fetch_assoc()) {
    $matchResult = $row["percentage"];
};

$getChartPercentage = "SELECT * FROM matchpercentage GROUP BY `percentage` ASC";
$percentageChart = mysqli_query($conn, $getChartPercentage);

while ($row = $percentageChart->fetch_assoc()) {
    $chartData[] = ($row['percentage'] + 0);

};

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shakeshear</title>
    <link rel="stylesheet" type="text/css" href="/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="/css/style.css">
</head>
<div class="backGroundWrapper">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="titleBox">
                    <h1>ShakesHear</h1>
                    <h2>Hamlet</h2>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="matchBox">
                    <p>Match</p>
                    <h2 class="matchPercentage"><?= $matchResult ?>%</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="currentListen col-md-3">
                <ul class="list-group">
                    <?php
                    // Call to last.fm
                    $urlLastfm = 'http://ws.audioscrobbler.com/2.0/?method=user.getRecentTracks&user=thementaldoctor&api_key=xxx&limit=10&format=json&extended=0';
                    $lastfmCallResult = file_get_contents($urlLastfm);
                    $lastfmCallResultDecode = json_decode($lastfmCallResult, true);

                    $newTimestampArray = [];

                    // Present last listened tracks
                    foreach ($lastfmCallResultDecode['recenttracks']['track'] as $index => $item) {
                        if (!empty($item['@attr'])) { ?>
                            <li class='list-group-item active'>
                                <span class="badge">Listening</span>
                                <h4><?= $item['artist']['#text'] ?>:</h4>
                                <?= $item['name'] ?>
                            </li>
                        <?php } else { ?>
                            <li class='list-group-item'>
                                <h4><?= $item['artist']['#text'] ?>:</h4>
                                <?= $item['name'] ?>
                            </li>
                        <?php }
                    }
                    ?>
                </ul>
            </div>
            <div class="col-md-9">
                <div id="container">
                </div>
                <h3>Current Lyrics</h3>
                <?php

                foreach ($lastfmCallResultDecode['recenttracks']['track'] as $index => $item) {

                    $lastsong_name = $item['name'];
                    $lastsong_artist = $item['artist']['#text'];

                    $artistMusixmatch = $lastsong_artist;
                    $trackMusixmatch = $lastsong_name;
                    $artistMusixmatch = str_replace(' ', '%20', $artistMusixmatch);
                    $trackMusixmatch = str_replace(' ', '%20', $trackMusixmatch);
                    
                    $apiKeyMusixmatch = "xxx";
                    $urlMusixmatch = 'http://api.musixmatch.com/ws/1.1/track.search?apikey=' . $apiKeyMusixmatch . '&q_artist=' . $artistMusixmatch . '&q_track=' . $trackMusixmatch . '';
                    $contentMusixmatch = file_get_contents($urlMusixmatch);
                    $musixmatchResponse = json_decode($contentMusixmatch, true);

                    $musixmatchTrackId = $musixmatchResponse["message"]["body"]["track_list"][0]["track"]["track_id"];
                    $hasLyrics = $musixmatchResponse["message"]["body"]["track_list"][0]["track"]["has_lyrics"];

                    if ($hasLyrics == 1) {
                        $urlMusixmatchLyrics = 'http://api.musixmatch.com/ws/1.1/track.lyrics.get?track_id=' . $musixmatchTrackId . '&apikey=' . $apiKeyMusixmatch . '';
                        $content = file_get_contents($urlMusixmatchLyrics);
                        $musixmatchLyricBodyResponse = json_decode($content, true);

                        $trackLyrics = $musixmatchLyricBodyResponse["message"]["body"]["lyrics"]["lyrics_body"];
                        $trackLyricsFiltered = substr($trackLyrics, 0, strpos($trackLyrics, '*******'));

                        print $trackLyricsFiltered;
                    }
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class=" col-md-12">


            </div>
        </div>
    </div>
</div>
<script src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/highcharts.js"></script>
<script src="/js/main.js"></script>
<script type="text/javascript">
    var chartdata = <?= json_encode($chartData) ?>;
</script>
</body>
</html>
