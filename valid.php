<?php
// ================== KONFIGURASI ==================
// Ganti dengan kredensial Anda dari BRIAPI
$client_id     = "bzoZFq23SknHOWovbTMxPgZwAkGIxuCU"; // ganti key baru
$client_secret = "xUyEaHFse52GewTP";               // ganti secret baru
$base_url      = "https://sandbox.partner.api.bri.co.id";

// ================== AMBIL TOKEN OTOMATIS ==================
function getAccessToken($url, $clientId, $clientSecret) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/x-www-form-urlencoded",
        "Authorization: Basic " . base64_encode($clientId . ":" . $clientSecret)
    ));
    // INI PERBAIKANNYA: Menggunakan metode POST yang lebih eksplisit
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        die("<b>Gagal ambil access token (HTTP Code: {$http_code}):</b><pre>" . htmlspecialchars($response) . "</pre>");
    }

    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        die("<b>Access token tidak ditemukan dalam response:</b><pre>" . htmlspecialchars($response) . "</pre>");
    }
    return $data['access_token'];
}

// Menghapus grant_type dari URL
$token_url = $base_url . "/oauth/client_credential/accesstoken";
$access_token = getAccessToken($token_url, $client_id, $client_secret);

// ================== FUNGSI UNTUK MEMBUAT SIGNATURE ==================
function createSignature($resourcePath, $verb, $token, $timestamp, $body, $clientSecret) {
    $stringToSign = "path={$resourcePath}&verb={$verb}&token={$token}&timestamp={$timestamp}&body={$body}";
    return base64_encode(hash_hmac('sha256', $stringToSign, $clientSecret, true));
}


// ================== AMBIL LIST BANK ==================
$banks = [];
try {
    // Menggunakan endpoint v1 untuk mengambil daftar bank
    $resourcePathListBank = "/v1/transfer/external/accounts";
    $timestampListBank = gmdate("Y-m-d\TH:i:s.000\Z");
    $bodyListBank = "";

    // INI PERBAIKANNYA: "Bearer" dihapus dari stringToSign
    $signatureListBank = createSignature($resourcePathListBank, "GET", $access_token, $timestampListBank, $bodyListBank, $client_secret);

    $urlListBank = $base_url . $resourcePathListBank;

    $ch = curl_init($urlListBank);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $access_token",
        "BRI-Timestamp: $timestampListBank",
        "BRI-Signature: $signatureListBank"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseList = curl_exec($ch);
    $http_code_list = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code_list == 200) {
        $listBankData = json_decode($responseList, true);
        if (isset($listBankData['data'])) {
            $banks = $listBankData['data'];
        }
    } else {
        // Menampilkan pesan error jika gagal mengambil daftar bank
        error_log("Gagal ambil daftar bank. HTTP Code: {$http_code_list}. Response: {$responseList}");
    }

} catch (Exception $e) {
    error_log("Exception saat ambil list bank: " . $e->getMessage());
}


// ================== VALIDASI REKENING ==================
$result_json = "";
$validation_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bankcode']) && !empty($_POST['accountnumber'])) {
    $bankcode = trim($_POST['bankcode']);
    $account  = trim($_POST['accountnumber']);

    $timestampValidate = gmdate("Y-m-d\TH:i:s.000\Z");

    // Endpoint v2 untuk validasi rekening sudah benar
    $resourcePathValidate = "/v2/transfer/external/accounts?bankcode={$bankcode}&beneficiaryaccount={$account}";
    $bodyValidate = "";

    // INI PERBAIKANNYA: "Bearer" dihapus dari stringToSign
    $signatureValidate = createSignature($resourcePathValidate, "GET", $access_token, $timestampValidate, $bodyValidate, $client_secret);

    $urlValidate = $base_url . $resourcePathValidate;

    $ch = curl_init($urlValidate);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $access_token",
        "BRI-Timestamp: $timestampValidate",
        "BRI-Signature: $signatureValidate"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code_validate = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code_validate != 200) {
        $validation_error = "Error: Terjadi masalah saat validasi rekening (HTTP Code: {$http_code_validate}).";
    }
    $result_json = $response;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
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

