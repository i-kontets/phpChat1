<?php
// .envから環境変数を読み込む（簡易実装）
$env = parse_ini_file('.env');
$api_key = $env['OPENAI_API_KEY'] ?? '';

$response_text = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['query'])) {
    $user_query = $_POST['query'];

    // APIリクエストの設定
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-5-mini', // 指定のモデル名
        'messages' => [
            [
                'role' => 'system',
                'content' => 'ユーザーの入力から最も重要な単語を1つだけ選び、その「原語: 英訳」という形式のプレーンテキストのみを返してください。余計な説明は一切不要です。'
            ],
            [
                'role' => 'user',
                'content' => $user_query
            ]
        ],
        'temperature' => 0.3,
    ];

    // Streamコンテキストを使用してPOSTリクエストを作成
    $options = [
        'http' => [
            'header'  => [
                "Content-Type: application/json",
                "Authorization: Bearer $api_key"
            ],
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        $response_text = "エラーが発生しました。";
    } else {
        $json = json_decode($result, true);
        $response_text = $json['choices'][0]['message']['content'] ?? "応答を取得できませんでした。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Simple Chatbot</title>
</head>
<body>
    <h1>重要単語 抽出・翻訳</h1>
    <form method="POST">
        <input type="text" name="query" placeholder="問いかけを入力してください" required style="width: 300px;">
        <button type="submit">送信</button>
    </form>

    <?php if ($response_text): ?>
        <h2>結果:</h2>
        <pre><?php echo htmlspecialchars($response_text); ?></pre>
    <?php endif; ?>
</body>
</html>