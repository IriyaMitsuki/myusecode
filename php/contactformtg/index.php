<?php
// Токен вашего бота
$botToken = "token";
// ID чата администратора
$chatId = "id";
// Cloudflare Turnstile настройки
$siteKey = "key";
$secretKey = "key";
// Проверка куки
$cookieName = "feedback_submitted";
$cookieExpiration = time() + (5 * 60 * 60); // 5 часов

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] == $_SERVER['REMOTE_ADDR']) {
    echo "<div class='alert alert-warning' role='alert'>Вы уже отправили сообщение. Пожалуйста, подождите 5 часов.</div>";
} else {
    // Обработка отправки формы (если куки нет или IP другой)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Проверка Cloudflare Turnstile
        $turnstileResponse = $_POST['cf-turnstile-response'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'secret' => $secretKey,
            'response' => $turnstileResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['success'] === true) {
                $name = htmlspecialchars($_POST["name"]);
                $email = htmlspecialchars($_POST["email"]);
                $text = htmlspecialchars($_POST["text"]);
                // Получение IP-адреса и времени
                $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $date = date("Y-m-d H:i:s");
                // Создание сообщения для отправки в Telegram
                $message = "<b>Новое сообщение с сайта!</b>\n";
                $message .= "<b>Имя:</b> " . $name . "\n";
                $message .= "<b>Email:</b> " . $email . "\n";
                $message .= "<b>Сообщение:</b> " . $text . "\n";
                 $message .= "<b>IP:</b> " . $ip . "\n";
                $message .= "<b>Время:</b> " . $date;
                // Отправка сообщения в Telegram
                $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage?chat_id=" . $chatId . "&parse_mode=html&text=" . urlencode($message);
                $result = file_get_contents($url);
                // Проверка отправки сообщения и вывод результата
                 if ($result) {
                    $response = json_decode($result, true);
                     if ($response["ok"]) {
                         setcookie($cookieName, $ip, $cookieExpiration, "/");
                        echo "<div class='alert alert-success' role='alert'>Сообщение успешно отправлено!</div>";
                    } else {
                        echo "<div class='alert alert-danger' role='alert'>Ошибка отправки сообщения в Telegram: " . $response["description"] . "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Ошибка отправки сообщения в Telegram.</div>";
                }

        } else {
            echo "<div class='alert alert-danger' role='alert'>Ошибка проверки капчи. Попробуйте еще раз.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма обратной связи</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Обратная связь</h1>
        <form method="post">
            <div class="form-group">
                <label for="name">Имя:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="text">Сообщение:</label>
                <textarea class="form-control" id="text" name="text" rows="5" required></textarea>
            </div>
            <div class="cf-turnstile" data-sitekey="<?php echo $siteKey; ?>"></div>
            <button type="submit" class="btn btn-primary">Отправить</button>
        </form>
    </div>
</body>
</html>
