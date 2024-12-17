<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php'; // Database connection

// Default filter option
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'weekly';

// Fetch inflows and outflows based on selected filter
if ($filter === 'weekly') {
    $income_query = "SELECT WEEK(received_date) AS week, SUM(amount) AS total_inflow FROM income GROUP BY week ORDER BY week DESC LIMIT 5";
    $expense_query = "SELECT WEEK(spent_date) AS week, SUM(amount) AS total_outflow FROM expenses GROUP BY week ORDER BY week DESC LIMIT 5";
} else { // Monthly
    $income_query = "SELECT MONTH(received_date) AS month, SUM(amount) AS total_inflow FROM income GROUP BY month ORDER BY month DESC LIMIT 5";
    $expense_query = "SELECT MONTH(spent_date) AS month, SUM(amount) AS total_outflow FROM expenses GROUP BY month ORDER BY month DESC LIMIT 5";
}

$income_result = $conn->query($income_query);
$income_data = $income_result->fetch_all(MYSQLI_ASSOC);

$expense_result = $conn->query($expense_query);
$expense_data = $expense_result->fetch_all(MYSQLI_ASSOC);

// Prepare data for chart
$labels = [];
$total_inflow = [];
$total_outflow = [];

// Process income data
foreach ($income_data as $row) {
    if ($filter === 'weekly') {
        $labels[] = "Week " . $row['week'];
    } else {
        $labels[] = "Month " . $row['month'];
    }
    $total_inflow[] = (float)$row['total_inflow'];
}

// Process expense data
foreach ($expense_data as $row) {
    if ($filter === 'weekly') {
        if (!isset($total_outflow[$row['week']])) {
            $total_outflow[$row['week']] = 0;
        }
        $total_outflow[$row['week']] += (float)$row['total_outflow'];
    } else {
        if (!isset($total_outflow[$row['month']])) {
            $total_outflow[$row['month']] = 0;
        }
        $total_outflow[$row['month']] += (float)$row['total_outflow'];
    }
}

// Prepare total outflow array for chart
$total_outflow_values = [];
foreach ($labels as $label) {
    $key = str_replace(['Week ', 'Month '], '', $label);
    $total_outflow_values[] = isset($total_outflow[$key]) ? (float)$total_outflow[$key] : 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="top-nav"</div>
      <div> <img style="display: block;-webkit-user-select: none;background-color: hsl(0, 0%, 90%);
        width:10%; height :9%;
        transition: background-color 300ms;" src="https://employee.crisscrosstamizh.in/FTP/logo.png"></div>
        <div class="nav-options">
            <a href="settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="index.php">Dashboard</a>
            <a href="transactions.php">Transactions</a>
            <a href="report.php">Reports</a>
            <a href="manageTransaction.php">Manage Transactions</a>
            <a href="payablesReceivables.php">Payables & Receivables</a> 
            <a href="settings.php">Settings</a>
        </div>
        <div class="content">
            <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>

            <!-- Filter Dropdown -->
            <form method="POST" action="">
                <label for="filter">Select Time Frame:</label>
                <select name="filter" id="filter" onchange="this.form.submit()">
                    <option value="weekly" <?= $filter === 'weekly' ? 'selected' : ''; ?>>Last 5 Weeks</option>
                    <option value="monthly" <?= $filter === 'monthly' ? 'selected' : ''; ?>>Last 5 Months</option>
                </select>
            </form>

            <div class="dashboard-summary">
                <h2>Financial Summary</h2>

                <!-- Chart Container -->
                <canvas id="cashflowChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('cashflowChart').getContext('2d');
        new Chart(ctx, {
            type: 'line', // Change to line chart with dots
            data: {
                labels: <?= json_encode($labels); ?>,
                datasets: [{
                    label: 'Total Inflows',
                    data: <?= json_encode($total_inflow); ?>,
                    borderColor: '#4CAF50', // Line color for inflows
                    backgroundColor: 'rgba(76, 175, 80, 0.2)', // Light green fill
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7 // Size of points on hover
                },
                {
                    label: 'Total Outflows',
                    data: <?= json_encode($total_outflow_values); ?>,
                    borderColor: '#F44336', // Line color for outflows
                    backgroundColor: 'rgba(244, 67, 54, 0.2)', // Light red fill
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7 // Size of points on hover
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                },
                scales: {
                    y:
                     { 
                        beginAtZero:true ,
                        title:{
                            display:true ,
                            text:'Amount ($)'
                        } 
                     },
                     x:{
                        title:{
                            display:true ,
                            text:(document.getElementById("filter").value === "weekly" ? "Weeks" : "Months")
                        }
                     } 
                    
                 }
                
             }
         });
     </script>

</body>
</html>
