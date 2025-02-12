<?php
include("./include/header.php");

$read =   "SELECT * FROM `transactions` ORDER BY created_at DESC;";
$result = mysqli_query($connection, $read);

if ($result) {
    if (mysqli_num_rows($result) > 0) {

?>


        <div class="dashboard-main-body">
            <div class="card basic-data-table">
                <div class="card-header">
                    <h5 class="card-title mb-0">View Transaction</h5>
                </div>
                <div class="card-body">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">First Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Country</th>
                                <th scope="col">Store Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Transaction Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Set the status class and text based on the row's status value
                                if ($row["status"] == "pending") {
                                    $statusClass = 'bg-warning-focus text-warning-main';  // Yellow for pending
                                    $statusText = 'Pending';
                                } elseif ($row["status"] == "paid") {
                                    $statusClass = 'bg-success-focus text-success-main';  // Green for paid
                                    $statusText = 'Paid';
                                } elseif ($row["status"] == "failed") {
                                    $statusClass = 'bg-danger-focus text-danger-main';  // Red for failed
                                    $statusText = 'Failed';
                                } elseif ($row["status"] == "refunded") {
                                    $statusClass = 'bg-info-focus text-info-main';  // Blue for refunded
                                    $statusText = 'Refunded';
                                } else {
                                    $statusClass = 'bg-secondary-focus text-secondary-main';  // Default gray for unknown status
                                    $statusText = 'Unknown';
                                }

                                echo ' 
            <tr>
              
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["name"] . '</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["first_name"] . '</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["email"] . '</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["amount"] . '</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["country"] . '</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["store_name"] . '</h6>
                    </div>
                </td>
                <td>
                    <span class="px-24 py-4 rounded-pill fw-medium text-sm ' . $statusClass . '">
                        ' . $statusText . '
                    </span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <h6 class="text-md mb-0 fw-medium flex-grow-1">' . date("M d, Y h:i A", strtotime($row["created_at"])) . '</h6>
                    </div>
                </td>
            </tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>



<?php
        //including header
    }
}

include("./include/footer.php");
?>