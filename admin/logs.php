<?php

include("./include/config.php");

// Query the ovri_logs table
$sql = "SELECT * FROM ovri_logs";
$result = $connection->query($sql);

// Check if any rows were returned
if ($result->num_rows > 0) {
    // Generate a timestamp for the filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "ovri_logs_{$timestamp}.csv";  // Filename with timestamp

    // Set the header to indicate a file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');

    // Open a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Get the column headers
    $columns = $result->fetch_fields();
    $header = [];
    foreach ($columns as $column) {
        $header[] = $column->name;
    }

    // Output the column headers to the CSV
    fputcsv($output, $header);

    // Output the data rows to the CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    // Close the output stream
    fclose($output);
} else {
    echo "No data found in the table.";
}

// Close the database connection
$connection->close();
?>
