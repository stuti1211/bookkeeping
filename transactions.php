<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php'; // Database connection

// Pagination setup
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Determine which table to display (income or expenses)
$table = isset($_GET['table']) && $_GET['table'] === 'expenses' ? 'expenses' : 'income';
$table_title = $table === 'expenses' ? 'Expenses' : 'Income';

// Fetch transactions based on selected table
if ($table === 'expenses') {
    $transaction_query = "SELECT * FROM expenses ORDER BY spent_date DESC LIMIT $limit OFFSET $offset";
    $count_query = "SELECT COUNT(*) AS total FROM expenses";
} else {
    $transaction_query = "SELECT * FROM income ORDER BY received_date DESC LIMIT $limit OFFSET $offset";
    $count_query = "SELECT COUNT(*) AS total FROM income";
}

$transaction_result = $conn->query($transaction_query);
$transactions = $transaction_result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Fetch category-wise amounts for chart data
$chart_query = "SELECT category, SUM(amount) AS total_amount FROM " . ($table === 'expenses' ? 'expenses' : 'income') . " GROUP BY category";
$chart_result = $conn->query($chart_query);
$chart_data = [];
while ($row = $chart_result->fetch_assoc()) {
    $chart_data[] = [
        'label' => htmlspecialchars($row['category']),
        'y' => (float)$row['total_amount']
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link rel="stylesheet" href="transactions.css">
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</head>
<body>
    <div class="top-nav">
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
            <a href="transactions.php?table=income">Transactions</a>
            <a href="report.php">Reports</a>
            <a href="manageTransaction.php">Manage Transactions</a>
            <a href="payablesReceivables.php">Payables & Receivables</a> 
            <a href="settings.php">Settings</a>
        </div>
        <div class="content">
            <h1><?= htmlspecialchars($table_title); ?> Transactions</h1>

           <!-- Toggle Buttons -->
<div class="toggle-buttons">
    <a href="?table=income&page=1" class="<?= $table === 'income' ? 'active' : ''; ?>">Income</a>
    <a href="?table=expenses&page=1" class="<?= $table === 'expenses' ? 'active' : ''; ?>">Expenses</a>
</div>
             <!-- Chart Container -->
             <div id="chartContainer" style="height: 370px; width: 100%; margin-top: 20px;"></div>

<!-- Chart Script -->
<script type="text/javascript">
    window.onload = function () {
        var chartData = <?= json_encode($chart_data); ?>;
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            theme: "light2",
            title: {
                text: "<?= htmlspecialchars($table_title); ?> by Category"
            },
            data: [{
                type: "column",
                dataPoints: chartData
            }]
        });
        chart.render();
    }
</script>


            <?php if (count($transactions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaction['id']); ?></td>
                                <td><?= htmlspecialchars($transaction['category']); ?></td>
                                <td>$<?= number_format($transaction['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($table === 'expenses' ? $transaction['spent_date'] : $transaction['received_date']); ?></td>
                                <td><?= htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?table=<?= $table; ?>&page=<?= $page - 1; ?>">« Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span><?= $i; ?></span> <!-- Current page -->
                        <?php else: ?>
                            <a href="?table=<?= $table; ?>&page=<?= $i; ?>"><?= $i; ?></a> <!-- Other pages -->
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?table=<?= $table; ?>&page=<?= $page + 1; ?>">Next »</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No transactions found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- You can add footer or scripts here if needed -->
</body>
</html>
