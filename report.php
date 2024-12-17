<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php'; // Database connection

// Initialize variables
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Fetch cash flow data based on selected date range
$query = "SELECT 'Income' AS type, category, amount, received_date AS date, description FROM income WHERE received_date BETWEEN ? AND ?
          UNION ALL
          SELECT 'Expense' AS type, category, -amount AS amount, spent_date AS date, description FROM expenses WHERE spent_date BETWEEN ? AND ?
          ORDER BY date";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow Report</title>
    <link rel="stylesheet" href="report.css">
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
            <a href="reports.php">Reports</a>
            <a href="manageTransaction.php">Manage Transactions</a>
            <a href="payablesReceivables.php">Payables & Receivables</a> 
            <a href="settings.php">Settings</a>
        </div>
        <div class="content">
            <h1>Cash Flow Report</h1>

            <!-- Date Selection Form -->
            <form method="POST" action="">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required value="<?= htmlspecialchars($start_date); ?>">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required value="<?= htmlspecialchars($end_date); ?>">
                <button type="submit">Generate Report</button>
            </form>

            <?php if (!empty($transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaction['type']); ?></td>
                                <td><?= htmlspecialchars($transaction['category']); ?></td>
                                <td>$<?= number_format($transaction['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($transaction['date']); ?></td>
                                <td><?= htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Export Buttons -->
                <div class="export-buttons">
                    <!-- <form method="POST" action="export.php" style="display:inline;">
                        <input type="hidden" name="data" value='<?= base64_encode(json_encode($transactions)); ?>'>
                        <input type="hidden" name="format" value="pdf">
                        <button type="submit">Export to PDF</button>
                    </form> -->

                    <form method="POST" action="export.php" style="display:inline;">
                        <input type="hidden" name="data" value='<?= base64_encode(json_encode($transactions)); ?>'>
                        <input type="hidden" name="format" value="excel">
                        <button type="submit">Export to Excel</button>
                    </form>
                </div>

            <?php else: ?>
                <p>No transactions found for the selected date range.</p>
            <?php endif; ?>
        </div> <!-- End of content -->
    </div> <!-- End of layout -->

    <!-- You can add footer or scripts here if needed -->
</body>
</html>
