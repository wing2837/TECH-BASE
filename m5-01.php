<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>mission5_01</title>
</head>
<body>
<?php
// データベース接続情報
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user = 'ユーザー名';
$password = 'パスワード';

// データベース接続
try {
    $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (PDOException $e) {
    exit('データベースに接続できませんでした。' . $e->getMessage());
}

// テーブル作成
$sql = "CREATE TABLE IF NOT EXISTS board (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            comment TEXT NOT NULL,
            date DATETIME NOT NULL,
            password VARCHAR(50) NOT NULL
        )";
$pdo->exec($sql);

// テーブル削除と再作成処理
if(isset($_POST["reset"])){
    // テーブル削除
    $pdo->exec("DROP TABLE IF EXISTS board");

    // テーブル再作成
    $sql = "CREATE TABLE IF NOT EXISTS board (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                comment TEXT NOT NULL,
                date DATETIME NOT NULL,
                password VARCHAR(50) NOT NULL
            )";
    $pdo->exec($sql);

    echo "テーブルを削除し、再作成しました。<br>";
}

if(isset($_POST["submit"])){
    if(isset($_POST["str"])){ // 新規投稿
        if(empty($_POST["name"]) || empty($_POST["str"]) || empty($_POST["password"])){
            echo "名前、コメント、パスワードを入力してください。<br>";
        } else {
            $name = $_POST["name"];
            $str = $_POST["str"];
            $date = date("Y/m/d H:i:s");
            $password = $_POST["password"];
            
            echo $name . "さんの" . $str . "<br>が投稿されました。<br>";

            // データベースに投稿を追加
            $stmt = $pdo->prepare("INSERT INTO board (name, comment, date, password) VALUES (:name, :comment, :date, :password)");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':comment', $str, PDO::PARAM_STR);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
        }
    } elseif(isset($_POST["delete"])){ // 削除処理
        $delete_number = $_POST["delete"];
        $password = $_POST["password"];
        
        // データベース内の該当投稿を削除
        $stmt = $pdo->prepare("SELECT * FROM board WHERE id = :id");
        $stmt->bindParam(':id', $delete_number, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row['password'] === $password) {
            $stmt = $pdo->prepare("DELETE FROM board WHERE id = :id");
            $stmt->bindParam(':id', $delete_number, PDO::PARAM_INT);
            $stmt->execute();

            // 削除した場合のメッセージを表示
            if ($stmt->rowCount() > 0) {
                echo $delete_number . "番の投稿が削除されました。<br>";
            } else {
                echo "削除に失敗しました。<br>";
            }
        } else {
            echo "削除に失敗しました。投稿番号またはパスワードが正しくありません。<br>";
        }
    } elseif(isset($_POST["edit"]) && isset($_POST["password"])) {
        // 編集フォームの表示
        $edit = $_POST["edit"];
        $password = $_POST["password"];
        $stmt = $pdo->prepare("SELECT * FROM board WHERE id = :id AND password = :password");
        $stmt->bindParam(':id', $edit, PDO::PARAM_INT);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            echo '<form method="POST" action="https://tech-base.net/tb-250694/m5-01.php">';
            echo '名前：<input type="text" name="name" value="' . $row['name'] . '"> ';
            echo 'コメント：<input type="text" name="str" value="' . $row['comment'] . '"> ';
            echo 'パスワード：<input type="password" name="password" value="' . $row['password'] . '"> ';
            echo '<input type="hidden" name="edit" value="' . $edit . '">';
            echo '<input type="submit" name="submit_edit" value="編集">'; // 修正：送信ボタンの名前を変更
            echo '</form>';
        } else {
            echo "編集に失敗しました。投稿番号またはパスワードが正しくありません。<br>";
        }
    }
}

if(isset($_POST["submit_edit"]) && isset($_POST["name"]) && isset($_POST["str"]) && isset($_POST["password"])) {
    // 編集処理
    $edit = $_POST["edit"];
    $edited_comment = $_POST["str"];
    $name = $_POST["name"];
    $date = date("Y/m/d H:i:s");
    $password = $_POST["password"];
    
    // データベース内の該当投稿を取得
    $stmt = $pdo->prepare("SELECT * FROM board WHERE id = :id AND password = :password");
    $stmt->bindParam(':id', $edit, PDO::PARAM_INT);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row) {
        $stmt = $pdo->prepare("UPDATE board SET name = :name, comment = :comment, date = :date WHERE id = :id");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $edited_comment, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':id', $edit, PDO::PARAM_INT);
        $stmt->execute();
        echo $edit . "番の投稿が編集されました。<br>";
    } else {
        echo "編集に失敗しました。投稿番号またはパスワードが正しくありません。<br>";
    }
}

// 投稿フォームと投稿内容の表示
echo '
<form method="POST" action="https://tech-base.net/tb-250694/m5-01.php">
    名前：<input type="text" name="name"> 
    コメント：<input type="text" name="str"> 
    パスワード：<input type="password" name="password"> <!-- パスワード入力欄を追加 -->
    <input type="submit" name="submit" value="送信">
</form>
<form method="POST" action="https://tech-base.net/tb-250694/m5-01.php">
    <input type="number" name="delete" placeholder="削除対象番号">
    パスワード：<input type="password" name="password"> <!-- パスワード入力欄を追加 -->
    <input type="submit" name="submit" value ="削除">
</form>
<form method="POST" action="https://tech-base.net/tb-250694/m5-01.php">
    <input type="number" name="edit" placeholder="編集対象番号">
    パスワード：<input type="password" name="password"> <!-- パスワード入力欄を追加 -->
    <input type="submit" name="submit" value ="編集">
</form>
<hr>';

// データベースから投稿を取得して表示
$stmt = $pdo->query("SELECT * FROM board");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . " " . $row['name'] . " " . $row['comment'] . " " . $row['date'] . "<br>";
}

?>

<form method="POST" action="https://tech-base.net/tb-250694/m5-01.php">
    <input type="submit" name="reset" value="テーブル削除と再作成">
</form>
</body>
</html>