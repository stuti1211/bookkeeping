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

// Fetch last 10 accounts payable and receivable
$query = "SELECT 'payable' AS type, id, bill_description AS description, amount, due_date, status FROM accounts_payable
          UNION ALL
          SELECT 'receivable' AS type, id, invoice_description AS description, amount, due_date, status FROM accounts_receivable
          ORDER BY due_date DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) AS total FROM (
                    SELECT id FROM accounts_payable
                    UNION ALL
                    SELECT id FROM accounts_receivable
                ) AS combined";
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payables and Receivables</title>
    <link rel="stylesheet" href="payablesReceivables.css">
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
            <a href="index.php" class="<?= $currentPage == 'index' ? 'active' : ''; ?>">Dashboard</a>
            <a href="transactions.php" class="<?= $currentPage == 'transactions' ? 'active' : ''; ?>">Transactions</a>
            <a href="report.php" class="<?= $currentPage == 'report' ? 'active' : ''; ?>">Reports</a>
            <a href="manageTransaction.php" class="<?= $currentPage == 'manageTransaction' ? 'active' : ''; ?>">Manage Transactions</a>
            <a href="payablesReceivables.php" class="<?= $currentPage == 'payablesReceivables' ? 'active' : ''; ?>">Payables & Receivables</a>
            <a href="settings.php" class="<?= $currentPage == 'setting' ? 'active' : ''; ?>">Settings</a>
        </div>

        <div class="content">
            <h1>Payables and Receivables</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($transaction['type'])); ?></td>
                            <td><?= htmlspecialchars($transaction['description']); ?></td>
                            <td>$<?= number_format($transaction['amount'], 2); ?></td>
                            <td><?= htmlspecialchars($transaction['due_date']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($transaction['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1; ?>">« Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span><?= $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i; ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1; ?>">Next »</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
