<?php
require __DIR__ . '/inc/functions.php';
$city = null;
if (!empty($_GET["city"])) {
    $city = $_GET["city"];
}

$fileName = null;
$cityInformation = [];
if (!empty($city)) {
    $cities = json_decode(file_get_contents('./data/index.json'), true);

    foreach ($cities as $value) {
        if ($value["city"] === $city) {
            $fileName = $value['filename'];
            $cityInformation = $value;
            break;
        }
    }
}


if (!empty($fileName)) {
        $results = json_decode(
            file_get_contents('compress.bzip2://' . __DIR__ . '/data/' . $fileName),
            true
            )['results'];

        $units = [
                'pm25' => null,
                'pm10' => null,
        ];
        foreach ($results as $result) {
            if (!empty($units['pm25']) && !empty($units['pm10'])) break;
            if ($result['parameter'] === 'pm25') {
                $units['pm25'] = $result['unit'];
            }
            if ($result['parameter'] === 'pm10') {
                $units['pm10'] = $result['unit'];
            }
        }

        $stats = [];
        foreach ($results as $result) {
            if ($result['parameter'] !== 'pm25' && $result['parameter'] !== 'pm10') continue;
            if ($result['value'] < 0) continue;

                $month = substr($result['date']['local'], 0, 7);
            if (!isset($stats[$month])) {
                $stats[$month] = [
                        'pm25' => [],
                        'pm10' => [],
                ];
            }

            $stats[$month][$result['parameter']][] = $result['value'];
        }
}
?>

<?php require __DIR__ . '/views/header.php'?>
<?php if (empty($city)): ?>
    <p>
        The city could  not be loaded.
    </p>
<?php  else: ?>
<h1><?php echo e($cityInformation['city']) ?> <?php echo e($cityInformation['flag']) ?></h1>
    <?php if (!empty($stats)): ?>

        <canvas id="aqi-chart" style="width: 300px; height: 200px;">

        </canvas>
        <script src="scripts/chart.umd.js"></script>
    <?php
        $labels = array_keys($stats);
        sort($labels);
        $pm25 = [];
        $pm10 = [];
        foreach ($labels as $label) {
            $measurements = $stats[$label];
            if (count($measurements['pm25']) !== 0) {
                $pm25[] = array_sum($measurements['pm25']) / count($measurements['pm25']);
            } else {
                $pm25[] = 0;
            }
              if (count($measurements['pm10']) !== 0) {
                  $pm10[] = array_sum($measurements['pm10']) / count($measurements['pm10']);
              } else {
                  $pm10[] = 0;
              }

        }

        $dataSets = [];
        if (array_sum($pm25) > 0) {
            $dataSets[] = [
                'label' => "AQI, PM2.5 in {$units['pm25']}",
                'data' => $pm25,
                'fill' => false,
                'borderColor' => 'rgb(75, 192, 192)',
                'tension' => 0.1,
            ];
        }
        if (array_sum($pm10) > 0) {
                $dataSets[] = [
                    'label' => "AQI, PM10 in {$units['pm10']}",
                    'data' => $pm10,
                    'fill' => false,
                    'borderColor' => 'rgb(141,177,57)',
                    'tension' => 0.1,
                ];
            }
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const ctx = document.getElementById('aqi-chart');
                const labels = <?php echo json_encode($labels)?>;
                const data = {
                    labels: labels,
                    datasets: <?php echo json_encode($dataSets)?>
                };
                const myChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                });
            });
        </script>
    <table>
        <thead>
        <tr>
            <th>Month</th>
            <th>PM 2.5 concentration</th>
            <th>PM 10 concentration</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($stats as $month => $measurements): ?>
             <tr>
                <th><?php echo e($month); ?></th>
                 <td>
                     <?php if(count($measurements['pm25']) !== 0): ?>
                     <?php echo e(round(array_sum($measurements['pm25']) / count($measurements['pm25']), 2)); ?>
                     <?php echo e($units['pm25']); ?>
                     <?php else: ?>
                     <i>No data available</i>
                     <?php endif; ?>
                 </td>
                 <td>
                     <?php if(count($measurements['pm10']) !== 0): ?>
                     <?php echo e(round(array_sum($measurements['pm10']) / count($measurements['pm10']), 2)); ?>
                     <?php echo e($units['pm10']); ?>
                     <?php else: ?>
                     <i>No data available</i>
                     <?php endif; ?>
                 </td>
             </tr>
        </tbody>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
<?php endif; ?>
<?php require  __DIR__ . '/views/footer.php'?>