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

// Fetch last 10 transactions from both income and expenses
$query = "SELECT 'income' AS type, id, category, amount, received_date AS date, description FROM income
          UNION ALL
          SELECT 'expense' AS type, id, category, amount, spent_date AS date, description FROM expenses
          ORDER BY date DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) AS total FROM (
                    SELECT id FROM income
                    UNION ALL
                    SELECT id FROM expenses
                ) AS combined";
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Handle adding/updating transactions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $transaction_id = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null;

    if ($transaction_id) {
        // Update existing transaction
        if ($type === 'income') {
            $stmt = $conn->prepare("UPDATE income SET category=?, amount=?, received_date=?, description=? WHERE id=?");
        } else {
            $stmt = $conn->prepare("UPDATE expenses SET category=?, amount=?, spent_date=?, description=? WHERE id=?");
        }
        $stmt->bind_param('sdssi', $category, $amount, $date, $description, $transaction_id);
    } else {
        // Add new transaction
        if ($type === 'income') {
            $stmt = $conn->prepare("INSERT INTO income (category, amount, received_date, description) VALUES (?, ?, ?, ?)");
        } else {
            $stmt = $conn->prepare("INSERT INTO expenses (category, amount, spent_date, description) VALUES (?, ?, ?, ?)");
        }
        $stmt->bind_param('sdss', $category, $amount, $date, $description);
    }
    
    if ($stmt->execute()) {
        header('Location: manageTransaction.php'); // Redirect to avoid form resubmission
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transactions</title>
    <link rel="stylesheet" href="manageTransaction.css">
</head>
<body>
    <div class="top-nav">
    <div> <img style="display: block;-webkit-user-select: none;background-color: hsl(0, 0%, 90%);
        width:10%; height :8%;
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
            <h1>Manage Transactions</h1>

            <!-- Button to Add New Transaction -->
            <button id="addTransactionBtn">Add New Transaction</button>

            <!-- Transactions Table -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($transaction['type'])); ?></td>
                            <td><?= htmlspecialchars($transaction['category']); ?></td>
                            <td>$<?= number_format($transaction['amount'], 2); ?></td>
                            <td><?= htmlspecialchars($transaction['date']); ?></td>
                            <td><?= htmlspecialchars($transaction['description']); ?></td>
                            <td><button class="editBtn" data-id="<?= htmlspecialchars($transaction['id']); ?>" data-type="<?= htmlspecialchars($transaction['type']); ?>" data-category="<?= htmlspecialchars($transaction['category']); ?>" data-amount="<?= htmlspecialchars($transaction['amount']); ?>" data-date="<?= htmlspecialchars($transaction['date']); ?>" data-description="<?= htmlspecialchars($transaction['description']); ?>">Edit</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1; ?>">« Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span><?= $i; ?></span> <!-- Current page -->
                    <?php else: ?>
                        <a href="?page=<?= $i; ?>"><?= $i; ?></a> <!-- Other pages -->
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1; ?>">Next »</a>
                <?php endif; ?>
            </div>

            <!-- Modal for Adding/Editing Transactions -->
            <div id="transactionModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Add/Edit Transaction</h2>

                    <!-- Form for Adding/Editing Transaction -->
                    <form id="transactionForm" method="POST" action="">
                        <!-- Hidden input to store transaction ID for editing -->
                        <input type="hidden" name="transaction_id" id="transaction_id" />

                        <!-- Transaction Details -->
                        <label for="type">Type:</label><br />
                        <select name="type" id="type" required onchange="populateCategories()">
                            <option value="">Select Type...</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select><br />

                        <!-- Categories will be populated based on type selection -->
                        <label for="category">Category:</label><br />
                        <select name="category" id="category" required></select><br />

                        <label for="amount">Amount:</label><br />
                        <input type="number" name="amount" step=".01" required /><br />

                        <label for="date">Date:</label><br />
                        <input type="date" name="date" required /><br />

                        <label for="description">Description:</label><br />
                        <textarea name="description"></textarea><br />

                        <!-- Submit Button -->
                        <button type="submit">Save Transaction</button>

                    </form>

                </div> <!-- End of modal-content -->
            </div> <!-- End of modal -->

        </div> <!-- End of content -->
    </div> <!-- End of layout -->

    <!-- JavaScript to Handle Modal Logic -->
    <script src="//code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Script for Modal Functionality -->
    <script type='text/javascript'>
       $(document).ready(function() {
    var modal = $('#transactionModal');

    // Show modal on button click for adding a new transaction
    $('#addTransactionBtn').click(function() {
        modal.show();
        $('#transactionForm')[0].reset(); // Reset form fields
        $('#transaction_id').val(''); // Clear hidden ID field
        $('#type').val(''); // Reset type selection
        $('#category').empty(); // Clear categories

        // Re-populate categories when the type is selected
        $('#type').change(function() {
            populateCategories($(this).val());
        });
    });

    // Close modal when clicking on close button or outside of modal content
    $('.close').click(function() {
        modal.hide();
    });
    
    $(window).click(function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Edit button functionality (populating the form)
    $('.editBtn').click(function() {
        var transactionId = $(this).data('id');
        var type = $(this).data('type');
        var category = $(this).data('category');
        var amount = $(this).data('amount');
        var date = $(this).data('date');
        var description = $(this).data('description');

        // Populate form fields with existing data
        $('#transaction_id').val(transactionId);
        $('#type').val(type);

        // Populate categories based on type selection and set selected category
        populateCategories(type);
        $('#category').val(category);

        $('input[name=amount]').val(amount);
        $('input[name=date]').val(date);
        $('textarea[name=description]').val(description);

        modal.show(); // Show the modal with populated data
    });
});

function populateCategories(type) {
    const categoriesIncome = [
        "Payroll", "Investment", "Sales", "Services", "Others"
    ];

    const categoriesExpense = [
        "Rent", "Supplies", "Utilities", "Others"
    ];

    let categories;

    if (type === 'income') {
        categories = categoriesIncome;
    } else if (type === 'expense') {
        categories = categoriesExpense;
    } else {
        $('#category').empty(); // Clear categories if no type is selected
        return;
    }

    $('#category').empty(); // Clear existing options
    $('#category').append('<option value="" disabled selected>Select a category...</option>'); // Default option
    
    $.each(categories, function(index, value) {
        $('#category').append($('<option></option>').attr('value', value).text(value));
    });
}
    
    </script>

<!-- Include your styles here if needed -->

<!-- Include your manageTransaction.css file here -->
<link rel='stylesheet' href='manageTransaction.css'>

</body>

</html>