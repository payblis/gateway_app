<?php
include("./include/header.php");

// Database query to fetch logs from 'ovri_logs' table
$read = "SELECT * FROM `ovri_logs`;";
$result = mysqli_query($connection, $read);

if ($result && mysqli_num_rows($result) > 0) {
?>
    <!-- Main Dashboard Body -->
    <div class="dashboard-main-body">
        <div class="card basic-data-table">
            <!-- Card Header -->
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">View Logs</h5>
                <a href="logs" onclick="return confirm('Do you want to export?')" class="btn btn-primary">Export to CSV</a>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <!-- Table Container -->
                <div class="table-responsive">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length="10">
                        <!-- Table Head -->
                        <thead>
                            <tr>
                                <th scope="col" style="text-align: left">ID</th>
                                <th scope="col">Transaction ID</th>
                                <th scope="col">Request Type</th>
                                <th scope="col">HTTP Code</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Token</th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody>
                            <?php
                            // Loop through and display each log entry
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '
                                <tr>
                                    <td>
                                        <div class="form-check style-check d-flex align-items-center">
                                            <label class="form-check-label">' . $row["id"] . '</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1" style="word-wrap: break-word; max-width: 150px;">' . $row["transaction_id"] . '</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1" style="word-wrap: break-word; max-width: 150px;">' . $row["request_type"] . '</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["http_code"] . '</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["created_at"] . '</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["token"] . '</h6>
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
    </div>

<?php
}
include("./include/footer.php");
?>
