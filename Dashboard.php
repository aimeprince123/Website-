
<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$stmt = $pdo->query(
    "SELECT * FROM heartbeat_readings
     ORDER BY id DESC LIMIT 1"
);

$reading = $stmt->fetch();

$count = $pdo->query(
    "SELECT COUNT(*) FROM heartbeat_readings"
)->fetchColumn();

require "header.php";
?>

<div class="dashboard">

    <h1>Heartbeat Dashboard</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['full_name']) ?>
    </p>

    <div class="cards">

        <div class="card">
            <h3>♥ Heart Rate</h3>
            <h1 id="bpm">
                <?= $reading ? htmlspecialchars($reading['bpm']) : '--' ?>
            </h1>
            <p>BPM</p>
        </div>

        <div class="card">
            <h3>WiFi Status</h3>
            <h2 id="wifi">
                <?= $reading ? htmlspecialchars($reading['wifi_status']) : 'Waiting' ?>
            </h2>
        </div>

        <div class="card">
            <h3>GSM Call Status</h3>
            <h2 id="call">
                <?= $reading ? htmlspecialchars($reading['call_status']) : 'No data' ?>
            </h2>
        </div>

        <div class="card">
            <h3>Total Readings</h3>
            <h1><?= $count ?></h1>
        </div>

    </div>

    <div class="card">
        <h2>System Information</h2>
        <p>Controller: ESP32-WROOM-32</p>
        <p>Sensor: Pulse Sensor</p>
        <p>Communication: WiFi</p>
        <p>Emergency Alert: GSM Call</p>
    </div>

    <div class="notice">
        Educational prototype only. Not a medical device.
    </div>

</div>

<script>
async function updateReading() {
    try {
        const response = await fetch("reader.php");
        const data = await response.json();

        if(data.reading) {
            document.getElementById("bpm").textContent =
                data.reading.bpm;

            document.getElementById("wifi").textContent =
                data.reading.wifi_status;

            document.getElementById("call").textContent =
                data.reading.call_status;
        }
    } catch(error) {
        console.log("Unable to update readings");
    }
}

updateReading();
setInterval(updateReading, 3000);
</script>

<?php require "footer.php"; ?>
