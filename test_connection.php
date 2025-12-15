<?php
// Bật hiện lỗi tối đa
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Database Connection</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Kiểm tra file tồn tại
$config_path = 'config/database.php';
if (file_exists($config_path)) {
    echo "<p style='color:green'>✓ File config/database.php tồn tại.</p>";
} else {
    die("<p style='color:red'>✗ Không tìm thấy file config/database.php</p>");
}

// Thử require
try {
    require_once $config_path;
    echo "<p style='color:green'>✓ Require config/database.php thành công.</p>";
} catch (Throwable $e) {
    die("<p style='color:red'>✗ Lỗi khi require config: " . $e->getMessage() . "</p>");
}

// Thử kết nối
try {
    echo "<p>Đang thử kết nối database...</p>";
    $db = getDB();
    if ($db) {
        echo "<h2 style='color:green'>✓ KẾT NỐI THÀNH CÔNG!</h2>";
        $result = $db->query("SELECT VERSION() as v")->fetch();
        echo "<p>MySQL Version: " . $result['v'] . "</p>";
    }
} catch (Throwable $e) {
    echo "<h2 style='color:red'>✗ KẾT NỐI THẤT BẠI</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
