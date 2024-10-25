<?php
// Пути к файлам
$voteFile = 'votes.json';   // Файл для хранения голосов
$ipFile = 'ip_log.json';    // Файл для хранения IP-логов
$allowedVoteInterval = 3600; // Ограничение времени на повторное голосование (1 час в секундах)

// Функция для загрузки данных из JSON
function loadJson($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : []; // Возвращаем пустой массив, если данные невалидны
    }
    return []; // Возвращаем пустой массив, если файл не существует
}

// Функция для сохранения данных в JSON
function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Загружаем текущие голоса и IP-логи
$votes = loadJson($voteFile);
$ipLog = loadJson($ipFile);

// Получаем IP-адрес пользователя
$userIP = $_SERVER['REMOTE_ADDR'];

// Переменная для сообщений
$message = '';
$messageType = ''; // success или error

// Проверяем, голосовал ли пользователь в последние 1 час
// Проверяем, голосовал ли пользователь в последние 1 час
$canVote = true;
if (isset($ipLog[$userIP]) && time() - $ipLog[$userIP] < $allowedVoteInterval) {
    $secondsLeft = $allowedVoteInterval - (time() - $ipLog[$userIP]);
    $minutes = floor($secondsLeft / 60);  // Полные минуты
    $seconds = $secondsLeft % 60;         // Оставшиеся секунды

    // Формируем строку времени
    $timeLeft = $minutes . " минут" . ($seconds > 0 ? " и $seconds секунд" : "");

    $canVote = false;
    $message = "Вы уже голосовали! Попробуйте снова через $timeLeft.";
    $messageType = 'error';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
        $selectedLanguage = $_POST['language'];

        // Проверка, существует ли выбранный язык в голосовании
        if (!isset($votes[$selectedLanguage])) {
            $votes[$selectedLanguage] = 0;
        }

        // Увеличение количества голосов за выбранный язык
        $votes[$selectedLanguage]++;
        saveJson($voteFile, $votes);

        // Сохраняем IP-адрес и время последнего голосования
        $ipLog[$userIP] = time();
        saveJson($ipFile, $ipLog);

        $message = "Спасибо за ваш голос за $selectedLanguage!";
        $messageType = 'success';
    }
}

// Функция для отображения результатов голосования
function displayResults($votes) {
    if (empty($votes)) {
        echo '<p>Пока что нет голосов.</p>';
        return;
    }

    $totalVotes = array_sum($votes);
    if ($totalVotes > 0) {
        echo '<table class="results-table">';
        echo '<tr><th>Язык программирования</th><th>% голосов</th></tr>';
        foreach ($votes as $language => $count) {
            $percent = ($count / $totalVotes) * 100;
            echo "<tr><td>$language</td><td>
                    <div class='progress-bar'>
                        <div class='progress' style='width:" . round($percent) . "%'></div>
                    </div>
                    " . round($percent, 2) . "%</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>Пока что нет голосов.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интернет-голосование</title>
    <style>
        /* Basic Reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Background and Text */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-image: url('fon.jpg'); /* Replace with a high-quality image */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
        }

        /* Main Heading */
        h1 {
            text-align: center;
            font-size: 2.8em;
            color: #fff;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
            background-color: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            animation: glow 3s infinite alternate;
        }

        /* Paragraph */
        p {
            text-align: center;
            font-size: 1.2em;
            color: #fff;
            text-shadow: 0px 0px 8px rgba(0, 0, 0, 0.5);
        }

        /* Centered text with background for readability */
        .question-text {
            text-align: center;
            font-size: 1.5em;
            background-color: rgba(0, 0, 0, 0.6); /* Semi-transparent background */
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px auto;
            width: fit-content;
        }

        /* Form Styling */
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.5);
            animation: fadeIn 1.5s ease-out;
            max-width: 400px;
            margin: 20px auto;
        }

        input[type="radio"] {
            margin-right: 10px;
            transform: scale(1.3);
            cursor: pointer;
        }

        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s ease;
            border-radius: 50px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
            margin-top: 15px;
        }

        input[type="submit"]:hover {
            background-color: #218838;
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.6);
        }

        /* Notification Box */
        .notification {
            display: none;
            padding: 15px;
            margin: 20px auto;
            border-radius: 12px;
            font-size: 1.1em;
            text-align: center;
            max-width: 600px;
            opacity: 0;
            animation: fadeIn 1s forwards;
            color: white;
        }

        .notification.success {
            background-color: rgba(40, 167, 69, 0.9);
        }

        .notification.error {
            background-color: rgba(220, 53, 69, 0.9);
        }

        /* Voting Results Table */
        .results-table {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            border-radius: 12px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeInUp 1.5s ease-out;
        }

        .results-table th, .results-table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .results-table th {
            background-color: #28a745;
            color: white;
            font-size: 1.1em;
        }

        .results-table td {
            font-size: 1em;
        }

        .results-table tr:last-child td {
            border-bottom: none;
        }

        /* Progress Bar Styling */
        .progress-bar {
            background-color: #ddd;
            border-radius: 5px;
            width: 100%;
            height: 25px;
            overflow: hidden;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .progress {
            height: 100%;
            background-color: #28a745;
            animation: grow 1.5s ease-out;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes glow {
            from { text-shadow: 0 0 15px rgba(255, 255, 255, 0.5); }
            to { text-shadow: 0 0 25px rgba(255, 255, 255, 1); }
        }

        @keyframes grow {
            from { width: 0; }
            to { width: 100%; }
        }
    </style>
</head>
<body>

<h1>Интернет-голосование</h1>

<!-- Push Message Notification -->
<div class="notification <?php echo $messageType; ?>" style="<?php if ($message) { echo 'display: block;'; } ?>">
    <?php echo $message; ?>
</div>

<!-- Centered Text with Background for Readability -->
<div class="question-text">
    Какому языку программирования Вы отдали бы предпочтение?
</div>

<!-- Voting Form -->
<?php if ($canVote): ?>
    <form method="POST" action="">
        <label><input type="radio" name="language" value="C++" required> C++</label><br>
        <label><input type="radio" name="language" value="C#"> C#</label><br>
        <label><input type="radio" name="language" value="JavaScript"> JavaScript</label><br>
        <label><input type="radio" name="language" value="PHP"> PHP</label><br>
        <label><input type="radio" name="language" value="Java"> Java</label><br><br>
        <input type="submit" value="Голосовать">
    </form>
<?php endif; ?>

<h2 style="text-align: center; font-size: 2em; margin-top: 30px;">Результаты голосования</h2>
<?php displayResults($votes); ?>

</body>
</html>