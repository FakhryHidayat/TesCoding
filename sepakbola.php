<?php
session_start();

// Inisialisasi variabel sesi untuk menyimpan data klub dan skor pertandingan
if (!isset($_SESSION['clubs'])) {
    $_SESSION['clubs'] = [];
}

if (!isset($_SESSION['matches'])) {
    $_SESSION['matches'] = [];
}

// Fungsi untuk menambah klub baru
function addClub($name, $city) {
    $_SESSION['clubs'][] = ['name' => $name, 'city' => $city, 'points' => 0, 'matches_played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_scored' => 0, 'goals_conceded' => 0];
}

// Fungsi untuk mencari klub berdasarkan nama
function findClubByName($name) {
    foreach ($_SESSION['clubs'] as $club) {
        if ($club['name'] === $name) {
            return $club;
        }
    }
    return null;
}

// Fungsi untuk menambah skor pertandingan
function addMatch($club1, $club2, $score1, $score2) {
    $match = ['club1' => $club1, 'club2' => $club2, 'score1' => $score1, 'score2' => $score2];

    // Cek apakah pertandingan sudah pernah dimainkan
    foreach ($_SESSION['matches'] as $m) {
        if (($m['club1'] === $match['club1'] && $m['club2'] === $match['club2']) || ($m['club1'] === $match['club2'] && $m['club2'] === $match['club1'])) {
            return false; // Pertandingan sudah ada
        }
    }

    // Update data klub berdasarkan hasil pertandingan
    $club1 = findClubByName($club1);
    $club2 = findClubByName($club2);

    if ($score1 > $score2) {
        $club1['points'] += 3;
        $club1['wins'] += 1;
        $club2['losses'] += 1;
    } elseif ($score1 < $score2) {
        $club2['points'] += 3;
        $club2['wins'] += 1;
        $club1['losses'] += 1;
    } else {
        $club1['points'] += 1;
        $club2['points'] += 1;
        $club1['draws'] += 1;
        $club2['draws'] += 1;
    }

    $club1['matches_played'] += 1;
    $club2['matches_played'] += 1;
    $club1['goals_scored'] += $score1;
    $club2['goals_scored'] += $score2;
    $club1['goals_conceded'] += $score2;
    $club2['goals_conceded'] += $score1;

    // Update kembali data klub di sesi
    $_SESSION['clubs'] = array_map(function($c) use ($club1, $club2) {
        if ($c['name'] === $club1['name']) {
            return $club1;
        } elseif ($c['name'] === $club2['name']) {
            return $club2;
        }
        return $c;
    }, $_SESSION['clubs']);

    // Simpan data pertandingan di sesi
    $_SESSION['matches'][] = $match;
    return true;
}

// Fungsi untuk menampilkan klasemen
function displayStandings() {
    $standings = $_SESSION['clubs'];
    usort($standings, function($a, $b) {
        if ($a['points'] == $b['points']) {
            return $b['goals_scored'] - $b['goals_conceded'] - ($a['goals_scored'] - $a['goals_conceded']);
        }
        return $b['points'] - $a['points'];
    });

    echo "<table border='1'>";
    echo "<tr><th>No</th><th>Klub</th><th>Ma</th><th>Me</th><th>S</th><th>K</th><th>GM</th><th>GK</th><th>Point</th></tr>";
    $rank = 1;
    foreach ($standings as $club) {
        echo "<tr>";
        echo "<td>{$rank}</td>";
        echo "<td>{$club['name']}</td>";
        echo "<td>{$club['matches_played']}</td>";
        echo "<td>{$club['wins']}</td>";
        echo "<td>{$club['draws']}</td>";
        echo "<td>{$club['losses']}</td>";
        echo "<td>{$club['goals_scored']}</td>";
        echo "<td>{$club['goals_conceded']}</td>";
        echo "<td>{$club['points']}</td>";
        echo "</tr>";
        $rank++;
    }
    echo "</table>";
}

// Proses input data klub
if (isset($_POST['action']) && $_POST['action'] === 'addClub') {
    $name = $_POST['name'];
    $city = $_POST['city'];

    if (empty($name) || empty($city)) {
        echo "Nama klub dan kota klub harus diisi.";
    } else {
        $existingClub = findClubByName($name);
        if ($existingClub !== null) {
            echo "Klub dengan nama yang sama sudah ada.";
        } else {
            addClub($name, $city);
            echo "Klub berhasil ditambahkan.";
        }
    }
}

// Proses input skor pertandingan
if (isset($_POST['action']) && $_POST['action'] === 'addMatch') {
    if (isset($_POST['single'])) {
        $club1 = $_POST['club1'];
        $club2 = $_POST['club2'];
        $score1 = $_POST['score1'];
        $score2 = $_POST['score2'];

        if ($club1 === $club2) {
            echo "Klub tidak boleh sama.";
        } else {
            if (addMatch($club1, $club2, $score1, $score2)) {
                echo "Pertandingan berhasil ditambahkan.";
            } else {
                echo "Pertandingan sudah ada.";
            }
        }
    } elseif (isset($_POST['multiple'])) {
        $matches = $_POST['matches'];

        foreach ($matches as $match) {
            $club1 = $match['club1'];
            $club2 = $match['club2'];
            $score1 = $match['score1'];
            $score2 = $match['score2'];

            if ($club1 === $club2) {
                echo "Klub tidak boleh sama.";
                exit;
            } else {
                if (!addMatch($club1, $club2, $score1, $score2)) {
                    echo "Pertandingan sudah ada.";
                    exit;
                }
            }
        }
        echo "Pertandingan berhasil ditambahkan.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Mini Application: Klub Soccer</title>
</head>
<body>
    <h1>Input Data Klub</h1>
    <form method="post">
        <input type="hidden" name="action" value="addClub">
        Nama Klub: <input type="text" name="name"><br>
        Kota Klub: <input type="text" name="city"><br>
        <input type="submit" name="submit" value="Save">
    </form>

    <h1>Input Skor Pertandingan</h1>
    <form method="post">
        <input type="hidden" name="action" value="addMatch">
        <input type="radio" name="single" value="true" checked> Satu per satu
        <br>
        Klub 1: <input type="text" name="club1"> - Klub 2: <input type="text" name="club2">
        <br>
        Score 1: <input type="number" name="score1"> - Score 2: <input type="number" name="score2">
        <br>
        <input type="submit" name="submit" value="Save">
    </form>

    <form method="post">
        <input type="hidden" name="action" value="addMatch">
        <input type="radio" name="multiple" value="true"> Multiple
        <div id="matches">
            <div id="match1">
                Klub 1: <input type="text" name="matches[1][club1]"> - Klub 2: <input type="text" name="matches[1][club2]">
                Score 1: <input type="number" name="matches[1][score1]"> - Score 2: <input type="number" name="matches[1][score2]">
            </div>
        </div>
        <button type="button" onclick="addMatch()">Add</button>
        <input type="submit" name="submit" value="Save">
    </form>

    <h1>Tampilan Klasemen</h1>
    <?php displayStandings(); ?>

    <script>
        var matchCount = 1;
        function addMatch() {
            matchCount++;
            var newMatch = document.createElement("div");
            newMatch.setAttribute("id", "match" + matchCount);
            newMatch.innerHTML = "Klub 1: <input type='text' name='matches[" + matchCount + "][club1]'> - Klub 2: <input type='text' name='matches[" + matchCount + "][club2]'> Score 1: <input type='number' name='matches[" + matchCount + "][score1]'> - Score 2: <input type='number' name='matches[" + matchCount + "][score2]'>";
            document.getElementById("matches").appendChild(newMatch);
        }
    </script>
</body>
</html>
