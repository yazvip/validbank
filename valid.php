<?php
require_once __DIR__ . '/briapi.php';

// Load config
global $CONFIG;

// ================== AMBIL LIST BANK ==================
$banks = [];
try {
    $banks = briapi_list_banks($CONFIG);
} catch (Throwable $e) {
    error_log('Exception saat ambil list bank: ' . $e->getMessage());
}

// ================== VALIDASI REKENING ==================
$result_json = "";
$validation_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bankcode']) && !empty($_POST['accountnumber'])) {
    $bankcode = trim((string) $_POST['bankcode']);
    $account  = trim((string) $_POST['accountnumber']);

    try {
        $res = briapi_validate_account($CONFIG, $bankcode, $account);
        if ($res['http_code'] !== 200) {
            $validation_error = "Error: Terjadi masalah saat validasi rekening (HTTP Code: {$res['http_code']}).";
        }
        $result_json = $res['body'];
    } catch (Throwable $e) {
        $validation_error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Rekening Bank Lain</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 2em; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #003366; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="text"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #00529b; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #003366; }
        pre { background-color: #eee; padding: 15px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word; }
        .error { color: #D8000C; background-color: #FFD2D2; padding: 10px; border-radius: 4px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cek Rekening Bank Lain</h1>
        <form method="post">
            <label for="bankcode">Bank:</label>
            <select name="bankcode" id="bankcode" required>
                <option value="">-- Pilih Bank --</option>
                <?php
                if (!empty($banks)) {
                    foreach ($banks as $b) {
                        // Memastikan key ada sebelum digunakan
                        $bankCode = htmlspecialchars($b['bankCode'] ?? '');
                        $bankName = htmlspecialchars($b['bankName'] ?? '');
                        if (!empty($bankCode) && !empty($bankName)) {
                            echo "<option value='{$bankCode}'>{$bankCode} - {$bankName}</option>";
                        }
                    }
                } else {
                    echo "<option value='' disabled>Gagal memuat daftar bank</option>";
                }
                ?>
            </select>

            <label for="accountnumber">Nomor Rekening:</label>
            <input type="text" id="accountnumber" name="accountnumber" placeholder="Masukkan nomor rekening" required>
            
            <button type="submit">Cek Rekening</button>
        </form>

        <?php if (!empty($validation_error)): ?>
            <div class="error"><?php echo $validation_error; ?></div>
        <?php endif; ?>

        <?php if (!empty($result_json)): ?>
            <h3>Response JSON Hasil Validasi:</h3>
            <pre><?php echo htmlspecialchars(json_encode(json_decode($result_json), JSON_PRETTY_PRINT)); ?></pre>
        <?php endif; ?>
    </div>
</body>
</html>

