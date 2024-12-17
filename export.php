// export.php
<?php
require 'db.php'; // Database connection
require_once('tcpdf/tcpdf.php'); // Ensure TCPDF is included

$data = json_decode(base64_decode($_POST['data']), true);
$format = $_POST['format'];

if ($format === 'pdf') {
    // Create new PDF document
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Cash Flow Report');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Table header
    $html = '<h1>Cash Flow Report</h1>';
    $html .= '<table border="1"><thead><tr><th>Type</th><th>Category</th><th>Amount</th><th>Date</th><th>Description</th></tr></thead><tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $html .= '<td>$' . number_format($row['amount'], 2) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['date']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['description']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';

    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('cash_flow_report.pdf', 'D');
} elseif ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=cash_flow_report.xls");

    echo "<table border='1'>
            <tr><th>Type</th><th>Category</th><th>Amount</th><th>Date</th><th>Description</th></tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>$" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
?>