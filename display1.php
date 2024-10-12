<?php
// Database connection settings
$host = 'localhost';
$db = 'form_data';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch employee IDs with points greater than zero
$sql = "SELECT employee_id, SUM(points) AS total_points FROM submissions GROUP BY employee_id HAVING total_points > 0";
$result = $conn->query($sql);
// Fetch total points for each employee
$sql = "SELECT employee_id, SUM(points) AS total_points 
        FROM submissions 
        GROUP BY employee_id";
$result = $conn->query($sql);

$employee_data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employee_data[] = $row;
    }
}

// Fetch total work hours for each employee
$sql_hours = "SELECT employee_id, SUM(time_spent) AS total_hours 
              FROM submissions 
              GROUP BY employee_id";
$result_hours = $conn->query($sql_hours);

$hours_data = [];
if ($result_hours->num_rows > 0) {
    while($row = $result_hours->fetch_assoc()) {
        $hours_data[] = $row;
    }
}

$conn->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1, h2 {
            text-align: center;
            color: #4CAF50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #4CAF50;
            color: white;
        }

        .chart-container {
            width: 80%;
            margin: 40px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Employee Performance Dashboard</h1>

        <!-- Employee Performance Data -->
        <h2>Performance Summary by Employee</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Total Points</th>
                    <th>Total Work Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($employee_data as $data) {
                    $employee_id = $data['employee_id'];
                    $total_points = $data['total_points'];
                    // Match the corresponding total hours
                    $total_hours = 0;
                    foreach ($hours_data as $hours) {
                        if ($hours['employee_id'] == $employee_id) {
                            $total_hours = $hours['total_hours'];
                            break;
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo $employee_id; ?></td>
                        <td><?php echo $total_points; ?></td>
                        <td><?php echo $total_hours; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Visualization using Chart.js -->
        <div class="chart-container">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('performanceChart').getContext('2d');

        // Prepare data for chart (from PHP arrays)
        const employeeIds = <?php echo json_encode(array_column($employee_data, 'employee_id')); ?>;
        const totalPoints = <?php echo json_encode(array_column($employee_data, 'total_points')); ?>;
        const totalHours = <?php echo json_encode(array_column($hours_data, 'total_hours')); ?>;

        const performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: employeeIds,
                datasets: [
                    {
                        label: 'Total Points',
                        data: totalPoints,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Work Hours',
                        data: totalHours,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
