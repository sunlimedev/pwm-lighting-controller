<?php

$db = new PDO("sqlite:your_database.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- helper: 12h → 24h ---
function to24Hour($hour, $ampm)
{
    $hour = (int)$hour;

    if ($ampm === "AM") {
        return ($hour == 12) ? 0 : $hour;
    } else {
        return ($hour == 12) ? 12 : $hour + 12;
    }
}

// --- helper: 24h → 12h ---
function to12Hour($hour24)
{
    if ($hour24 == 0) return [12, "AM"];
    if ($hour24 < 12) return [$hour24, "AM"];
    if ($hour24 == 12) return [12, "PM"];
    return [$hour24 - 12, "PM"];
}

// --- handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try {
        $db->beginTransaction();

        for ($i = 0; $i <= 6; $i++)
        {
            $open_hour_24  = to24Hour($_POST['open_hour'][$i], $_POST['open_ampm'][$i]);
            $open_minute   = (int)$_POST['open_minute'][$i];

            $close_hour_24 = to24Hour($_POST['close_hour'][$i], $_POST['close_ampm'][$i]);
            $close_minute  = (int)$_POST['close_minute'][$i];

            $stmt = $db->prepare("
                UPDATE time
                SET open_hour = :open_hour,
                    open_minute = :open_minute,
                    close_hour = :close_hour,
                    close_minute = :close_minute
                WHERE weekday_id = :weekday_id
            ");

            $stmt->execute([
                ':open_hour'   => $open_hour_24,
                ':open_minute' => $open_minute,
                ':close_hour'  => $close_hour_24,
                ':close_minute'=> $close_minute,
                ':weekday_id'  => $i
            ]);
        }

        $db->commit();
    }
    catch (PDOException $e) {
        $db->rollBack();
        echo $e->getMessage();
        exit;
    }
}

// --- load existing data ---
$stmt = $db->query("SELECT * FROM time");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// index by weekday_id
$timeData = [];
foreach ($rows as $row) {
    $timeData[$row['weekday_id']] = $row;
}

$days = [
    0 => "Monday",
    1 => "Tuesday",
    2 => "Wednesday",
    3 => "Thursday",
    4 => "Friday",
    5 => "Saturday",
    6 => "Sunday"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lighting Hours</title>
    <link rel="stylesheet" href="tailwind.min.css">
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-md mx-auto bg-white p-4 rounded shadow">

<h2 class="text-lg font-medium mb-4">Lighting Schedule</h2>

<form method="POST" class="space-y-4">

<?php foreach ($days as $id => $day):

    $row = $timeData[$id] ?? null;

    $open_hour = $row ? $row['open_hour'] : 0;
    $open_min  = $row ? $row['open_minute'] : 0;

    $close_hour = $row ? $row['close_hour'] : 0;
    $close_min  = $row ? $row['close_minute'] : 0;

    list($open_h12, $open_ampm) = to12Hour($open_hour);
    list($close_h12, $close_ampm) = to12Hour($close_hour);

    // detect states
    $isNone = ($open_hour == $close_hour && $open_min == $close_min);
    $isAllDay = ($open_hour == 0 && $open_min == 0 && $close_hour == 23 && $close_min == 59);

?>

<div class="border p-3 rounded space-y-2" id="day-<?= $id ?>">

    <div class="flex justify-between items-center">
        <span class="font-medium"><?= $day ?></span>

        <div class="flex gap-3">
            <label class="flex items-center gap-1">
                <input type="checkbox" class="allDay w-4 h-4" <?= $isAllDay ? 'checked' : '' ?>>
                <span>All Day</span>
            </label>

            <label class="flex items-center gap-1">
                <input type="checkbox" class="noneDay w-4 h-4" <?= $isNone ? 'checked' : '' ?>>
                <span>None</span>
            </label>
        </div>
    </div>

    <div class="flex gap-2">
        <select name="open_hour[<?= $id ?>]" class="border p-1 rounded open_hour">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= ($i == $open_h12) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <select name="open_minute[<?= $id ?>]" class="border p-1 rounded open_minute">
            <?php foreach ([0,15,30,45] as $m): ?>
                <option value="<?= sprintf('%02d',$m) ?>" <?= ($m == $open_min) ? 'selected' : '' ?>>
                    <?= sprintf('%02d',$m) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="open_ampm[<?= $id ?>]" class="border p-1 rounded open_ampm">
            <option value="AM" <?= ($open_ampm == "AM") ? 'selected' : '' ?>>AM</option>
            <option value="PM" <?= ($open_ampm == "PM") ? 'selected' : '' ?>>PM</option>
        </select>
    </div>

    <div class="flex gap-2">
        <select name="close_hour[<?= $id ?>]" class="border p-1 rounded close_hour">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= ($i == $close_h12) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <select name="close_minute[<?= $id ?>]" class="border p-1 rounded close_minute">
            <?php foreach ([0,15,30,45] as $m): ?>
                <option value="<?= sprintf('%02d',$m) ?>" <?= ($m == $close_min) ? 'selected' : '' ?>>
                    <?= sprintf('%02d',$m) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="close_ampm[<?= $id ?>]" class="border p-1 rounded close_ampm">
            <option value="AM" <?= ($close_ampm == "AM") ? 'selected' : '' ?>>AM</option>
            <option value="PM" <?= ($close_ampm == "PM") ? 'selected' : '' ?>>PM</option>
        </select>
    </div>

</div>

<?php endforeach; ?>

<button type="submit" class="w-full mt-4 px-4 py-2 bg-blue-500 text-white rounded">
    Save
</button>

</form>
</div>

<script>
document.querySelectorAll("[id^='day-']").forEach(container => {

    const allDay = container.querySelector(".allDay");
    const noneDay = container.querySelector(".noneDay");

    const openHour = container.querySelector(".open_hour");
    const openMinute = container.querySelector(".open_minute");
    const openAMPM = container.querySelector(".open_ampm");

    const closeHour = container.querySelector(".close_hour");
    const closeMinute = container.querySelector(".close_minute");
    const closeAMPM = container.querySelector(".close_ampm");

    function disableAll(state) {
        openHour.disabled = state;
        openMinute.disabled = state;
        openAMPM.disabled = state;
        closeHour.disabled = state;
        closeMinute.disabled = state;
        closeAMPM.disabled = state;
    }

    function applyInitialState() {
        if (allDay.checked) {
            disableAll(true);
        } else if (noneDay.checked) {
            disableAll(true);
        }
    }

    applyInitialState();

    allDay.addEventListener("change", function () {
        if (this.checked) {
            noneDay.checked = false;

            openHour.value = "12";
            openMinute.value = "00";
            openAMPM.value = "AM";

            closeHour.value = "11";
            closeMinute.value = "59";
            closeAMPM.value = "PM";

            disableAll(true);
        } else {
            disableAll(false);
        }
    });

    noneDay.addEventListener("change", function () {
        if (this.checked) {
            allDay.checked = false;

            openHour.value = "12";
            openMinute.value = "00";
            openAMPM.value = "AM";

            closeHour.value = "12";
            closeMinute.value = "00";
            closeAMPM.value = "AM";

            disableAll(true);
        } else {
            disableAll(false);
        }
    });

});
</script>

</body>
</html>
