<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API sample</title>
    <style>
        /* 背景画像の設定 */
        body {
            margin: 0;
            padding: 20px;
            /* 背景画像は削除し、背景色のみ維持 */
            background-color: #f9f3e6;
            font-family: "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
        }

        /* ヘッダーとフォームの可読性向上 */
        h1, h2, #search-form {
            background: rgba(255, 255, 255, 0.85);
            padding: 15px 20px;
            border-radius: 10px;
            width: fit-content;
            margin-bottom: 15px;
        }

        /* 検索入力フィールドの強調 */
        #search-form input[type="text"] {
            width: 300px; /* 幅を広げる */
            padding: 12px 15px; /* 高さを出す */
            font-size: 1.1em;
            border: 2px solid #d4a373; /* 歴史的な茶系で強調 */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        #search-form input[type="text"]:focus {
            border-color: #a87c52;
            box-shadow: 0 0 8px rgba(212, 163, 115, 0.6);
            outline: none;
        }

        #search-form button[type="submit"] {
            padding: 12px 20px;
            font-size: 1.1em;
            background-color: #d4a373;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: background-color 0.3s ease;
        }

        #search-form button[type="submit"]:hover {
            background-color: #a87c52;
        }

        /* メインコンテンツを包むコンテナ */
        .main-container {
            display: flex;
            flex-direction: column; /* 基本は縦並び（スマホ） */
            gap: 20px;
            margin-top: 20px;
        }

        /* PC画面（幅768px以上）の設定 */
        @media (min-width: 768px) {
            .main-container {
                flex-direction: row; /* 横並びに変更 */
                align-items: flex-start;
            }
            #results-container {
                flex: 1; /* リストを左側に */
                max-height: 80vh;
                overflow-y: auto; /* 長いリストはスクロール可能に */
                padding-right: 10px;
            }
            #map-container {
                flex: 1; /* 地図を右側に */
                position: sticky;
                top: 20px;
            }
        }

        /* 検索結果のカードスタイル */
        .book-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 5px solid #d4a373; /* 歴史を感じさせる茶系のアクセント */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* 地図のスタイル */
        #map {
            width: 100%;
            height: 500px; /* PCでは少し高めに設定 */
            border-radius: 15px;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* ローディング画面のスタイル */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            margin-top: 15px;
            font-family: sans-serif;
            color: #333;
        }
        .trivia-box {
            margin-top: 25px;
            padding: 15px;
            max-width: 80%;
            text-align: center;
            color: #555;
            font-size: 0.9em;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div id="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text">国会図書館のデータを検索中...</div>
        <div id="trivia-display" class="trivia-box"></div>
    </div>

    <h1>国会図書館文献検索システム</h1>
    <h2>国会図書館の文献タイトルの検索結果より、川越市の史跡名やタグが含まれる地点を地図に表示します。</h2>
    <h2>複数のキーワードを入力した場合は、すべてのキーワードを含む文献を検索します（AND検索）。</h2>
    <form method="GET" action="" id="search-form">
        <input type="text" name="query" placeholder="検索キーワードを入力" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '川越 神社'; ?>">
        <button type="submit">検索</button>
    </form>

    <?php
    // .env ファイルを読み込んで環境変数にセットする（XAMPPなどのローカル環境用）
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!empty($name)) {
                    putenv(sprintf("%s=%s", $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    $query = $_GET['query'] ?? '川越 神社';
    
    // 検索順序による差異を減らす工夫
    $keywordsForApi = preg_split('/[\s,　]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    sort($keywordsForApi); // キーワードをアルファベット・50音順に並び替えて固定
    $normalizedQuery = implode(' ', $keywordsForApi);

    // シンプルな構成に戻す：成功実績のある title パラメータを使用し、絞り込みを解除
    $url = "https://ndlsearch.ndl.go.jp/api/opensearch?title=" . urlencode($normalizedQuery) . "&cnt=20";

    // キャッシュ設定
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    $cacheFile = $cacheDir . '/' . md5($normalizedQuery) . '.xml';
    $cacheLimit = 86400; // キャッシュ有効期限（24時間 = 86400秒）

    // 古いキャッシュファイルを削除（クリーンアップ処理）
    foreach (glob($cacheDir . "/*.xml") as $file) {
        if (is_file($file) && (time() - filemtime($file) > $cacheLimit)) {
            unlink($file);
        }
    }

    $response = false;

    // キャッシュが存在し、かつ期限内かチェック
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheLimit)) {
        $response = file_get_contents($cacheFile);
    } else {
        // キャッシュがない、または古い場合はAPIを叩く
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: MySearchApp/1.0 (Contact: your-email@example.com)'
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            file_put_contents($cacheFile, $response);
        }
    }

    $xml = $response ? simplexml_load_string($response) : false;
    $ndlTitlesCombined = "";

    echo '<div class="main-container">';
    echo '<div id="results-container">';

    if ($xml && isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $ndlTitlesCombined .= (string)$item->title . " ";
            echo '<div class="book-item">';
            echo "<strong>タイトル:</strong> " . htmlspecialchars($item->title) . "<br>";
            echo "<strong>リンク:</strong> <a href='" . htmlspecialchars($item->link) . "' target='_blank'>" . htmlspecialchars($item->link) . "</a>";
            echo "</div>";
        }
    }

    // マッチした地点を抽出
    $matchedLocations = [];
    if (!empty($ndlTitlesCombined)) {
        try {
            // 環境変数からDB情報を取得（Docker/Render対応）
            $dbUrl = getenv('DATABASE_URL');
            if ($dbUrl) {
                // DATABASE_URL形式 (postgresql://user:pass@host:port/dbname) を解析
                $p = parse_url($dbUrl);
                $dbHost = $p['host'] ?? 'localhost';
                $dbPort = $p['port'] ?? '5432';
                $dbUser = $p['user'] ?? 'postgres';
                $dbPass = $p['pass'] ?? '';
                $dbName = ltrim($p['path'] ?? 'postgres', '/');
            } else {
                // 個別の環境変数からの取得（従来通りのフォールバック）
                $dbHost = getenv('DB_HOST') ?: 'localhost';
                $dbName = getenv('DB_NAME') ?: 'postgres';
                $dbUser = getenv('DB_USER') ?: 'root';
                $dbPass = getenv('DB_PASS') ?: '';
                $dbPort = getenv('DB_PORT') ?: '5432';
            }

            $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $stmt = $pdo->query("SELECT name, display_name, latitude, longitude, tags FROM spots");
            $spots = $stmt->fetchAll();

            foreach ($spots as $spot) {
                $isMatched = false;
                // 1. スポットの具体的な名前が文献タイトルに含まれているか（最優先・高精度）
                if (mb_strpos($ndlTitlesCombined, $spot['name']) !== false) {
                    $isMatched = true;
                } else {
                    // 2. 検索クエリにタグが含まれ、かつ文献タイトルにもそのタグが含まれているか（カテゴリ検索対応）
                    $tagArray = preg_split('/[\s,]+/u', $spot['tags'], -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($tagArray as $tag) {
                        $cleanTag = ltrim($tag, '#'); // #があれば除去
                        if (!empty($cleanTag) && 
                            mb_strpos($query, $cleanTag) !== false && 
                            mb_strpos($ndlTitlesCombined, $cleanTag) !== false) {
                            $isMatched = true;
                            break;
                        }
                    }
                }
                if ($isMatched) $matchedLocations[] = $spot;
            }
        } catch (PDOException $e) {
            echo "DBエラー: " . htmlspecialchars($e->getMessage());
        }
    }

    if (!$response && empty($ndlTitlesCombined)) {
        echo "<p>APIへのアクセスが制限されているか、ネットワークエラーが発生しました。しばらく時間を置いてから再度お試しください。</p>";
    } elseif (empty($ndlTitlesCombined)) {
        echo "<p>データが見つかりませんでした。</p>";
    }

    echo '</div><!-- /#results-container -->';
    ?>

    <div id="map-container">
        <div id="map"></div>
    </div>
    </div><!-- /#main-container -->

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // 1. 地図の初期化（デフォルトは川越市中心部）
    var map = L.map('map').setView([35.924, 139.483], 14);

    // 2. 地図タイルの読み込み（ここがAPIキー不要のポイント）
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 3. マッチした全ての場所にピンを立てる
    var spots = <?php echo json_encode($matchedLocations); ?>;
    if (spots.length > 0) {
        var markerGroup = L.featureGroup();
        spots.forEach(function(spot) {
            var marker = L.marker([spot.latitude, spot.longitude])
                .bindPopup('<strong>' + spot.display_name + '</strong><br>に関する文献が表示されています');
            markerGroup.addLayer(marker);
        });
        markerGroup.addTo(map);
        // 全てのピンが収まるように地図の範囲を自動調整
        map.fitBounds(markerGroup.getBounds(), {padding: [50, 50]});
    }
</script>

<script>
    const kawagoeTrivia = [
        "川越城の本丸御殿は、東日本では唯一現存する貴重な遺構です。",
        "時の鐘は、今でも1日に4回（午前6時、正午、午後3時、午後6時）鐘を鳴らしています。",
        "サツマイモが川越の名産になったのは、江戸への輸送が新河岸川の舟運で便利だったからです。",
        "川越一番街の蔵造りの街並みは、1893年の川越大火の後に耐火建築として普及しました。",
        "川越まつりは「川越氷川祭の山車行事」としてユネスコ無形文化遺産に登録されています。",
        "「九里（栗）よりうまい十三里」という言葉は、江戸から川越までの距離が約13里だったことに由来します。",
        "喜多院には、江戸城から移築された「家光誕生の間」や「春日の局化粧の間」があります。"
    ];

    // フォーム送信時にローディングを表示
    document.getElementById('search-form').addEventListener('submit', function() {
        // ランダムに豆知識を選択
        const randomIndex = Math.floor(Math.random() * kawagoeTrivia.length);
        document.getElementById('trivia-display').innerText = "💡川越豆知識: " + kawagoeTrivia[randomIndex];
        
        // オーバーレイを表示
        document.getElementById('loading-overlay').style.display = 'flex';
    });
</script>
</body>

</html>