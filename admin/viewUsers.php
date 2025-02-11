<?php
include("./include/header.php");

$read =   "SELECT * FROM `users`;";
$result = mysqli_query($connection, $read);

if ($result) {
    if (mysqli_num_rows($result) > 0) {

?>


        <div class="dashboard-main-body">
            <div class="card basic-data-table">
                <div class="card-header">
                    <h5 class="card-title mb-0">View Users</h5>
                </div>
                <div class="card-body">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>

                        <thead>
                            <tr>
                                <th scope="col" style="text-align: left">
                                    Id
                                </th>
                                <th scope="col">Username</th>
                                <th scope="col">Role</th>
                                <th scope="col">Token</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>

                           <?php
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Check if the status is 1 (Approved) or not (Blocked)
                                $statusClass = ($row["status"] == 1) ? 'bg-success-focus text-success-main' : 'bg-danger-focus text-danger-main';
                                $statusText = ($row["status"] == 1) ? 'Approved' : 'Blocked';
                            
                                echo '<tr>
                                    <td>
                                        <div class="form-check style-check d-flex align-items-center">
                                            <label class="form-check-label">' . $row["id"] . '</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/user-list/user-list1.png" alt="" class="flex-shrink-0 me-12 radius-8">
                                            <h6 class="text-md mb-0 fw-medium flex-grow-1">' . $row["username"] . '</h6>
                                        </div>
                                    </td>
                                    <td>' . $row["role"] . '</td>
                                    <td>' . ($row["api_key"] ? $row["api_key"] : "None") . '</td>

                                    <td>
                                        <span class="px-24 py-4 rounded-pill fw-medium text-sm ' . $statusClass . '">
                                            ' . $statusText . '
                                        </span>
                                    </td>
                                    <td>';
                                    
                                // Only show edit/delete icons if role is NOT "admin"
                                if (strtolower($row["role"]) !== "admin") {
                                    echo '<a href="updateUser?id='.$row["id"].'" class="w-32-px h-32-px bg-warning-focus text-warning-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                            <iconify-icon icon="lucide:edit"></iconify-icon>
                                          </a>
                                          ';
                                }
                            
                                echo '</td></tr>';
                            }
                            ?>
                            
                            <!--<a href="deleteUser?id='.$row["id"].'" class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center">-->
                            <!--                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>-->
                            <!--              </a>-->



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