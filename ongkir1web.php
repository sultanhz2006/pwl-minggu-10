<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Ongkir RajaOngkir</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 700px;
            max-width: calc(100% - 32px);
            margin: 40px auto;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }

        label {
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .card {
            background: white;
            border-left: 5px solid #007bff;
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .error {
            background: #ffdede;
            color: #b00020;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .success {
            background: #d9ffd9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        pre {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Cek Ongkir RajaOngkir (Komerce API)</h2>

    <form method="POST">
        <label for="origin">Kota Asal</label>
        <select id="origin" name="origin" required>
            <option value="501">Semarang</option>
            <option value="114">Jakarta Barat</option>
            <option value="39">Bandung</option>
        </select>

        <label for="destination">Kota Tujuan</label>
        <select id="destination" name="destination" required>
            <option value="114">Jakarta Barat</option>
            <option value="501">Semarang</option>
            <option value="78">Surabaya</option>
        </select>

        <label for="weight">Berat (gram)</label>
        <input
            id="weight"
            type="number"
            name="weight"
            min="1"
            required
            placeholder="1000"
        >

        <label for="courier">Kurir</label>
        <select id="courier" name="courier" required>
            <option value="jne">JNE</option>
            <option value="pos">POS</option>
            <option value="tiki">TIKI</option>
        </select>

        <button type="submit">Cek Ongkir</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $origin = $_POST['origin'];
        $destination = $_POST['destination'];
        $weight = $_POST['weight'];
        $courier = $_POST['courier'];

        // Isi dengan API key dari baris "Shipping Cost", bukan "Payment API".
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
        }

        $apiKey = getenv('RAJAONGKIR_API_KEY') ?: ($env['RAJAONGKIR_API_KEY'] ?? 'ISI_API_KEY_SHIPPING_COST_KAMU');

        if ($apiKey === "ISI_API_KEY_SHIPPING_COST_KAMU") {
            echo "
            <div class='error'>
                API key belum diisi. Klik <b>Show Key</b> pada menu Developer &gt; Settings, baris <b>Shipping Cost</b>, lalu tempelkan ke variabel <code>\$apiKey</code>.
            </div>";
        } else {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    "origin" => $origin,
                    "destination" => $destination,
                    "weight" => $weight,
                    "courier" => $courier,
                    "price" => "lowest",
                ]),
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "key: " . $apiKey,
                    "Content-Type: application/x-www-form-urlencoded",
                ],
            ]);

            $result = curl_exec($curl);
            $error = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($error) {
                echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
            } else {
                $response = json_decode($result, true);

                if (isset($response['data']) && count($response['data']) > 0) {
                    echo "<div class='success'>Ongkir berhasil ditemukan</div>";

                    foreach ($response['data'] as $ongkir) {
                        echo "
                        <div class='card'>
                            <h3>" . htmlspecialchars($ongkir['service']) . "</h3>
                            <b>Kurir:</b> " . htmlspecialchars($ongkir['name']) . "
                            <br><br>
                            <b>Deskripsi:</b> " . htmlspecialchars($ongkir['description']) . "
                            <br><br>
                            <b>Biaya:</b> Rp " . number_format($ongkir['cost']) . "
                            <br><br>
                            <b>Estimasi:</b> " . htmlspecialchars($ongkir['etd']) . "
                        </div>";
                    }
                } else {
                    echo "
                    <div class='error'>
                        Data ongkir tidak ditemukan
                        <br><br>
                        HTTP Code: " . htmlspecialchars((string) $httpCode) . "
                        <pre>";
                    print_r($response);
                    echo "</pre>
                    </div>";
                }
            }
        }
    }
    ?>
</div>
</body>
</html>
