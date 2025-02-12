<?php
error_log("=== DÉBUT PAYMENT.PHP ===");
require("../admin/include/config.php");

if (!isset($_GET['token'])) {
    die(json_encode([
        'Code' => 400,
        'ErrorCode' => '000001',
        'ErrorDescription' => 'Invalid Request: Parameter is missing.'
    ]));
}

$userdata = $_GET['token'];
$decoded = base64_decode($userdata, true);

if ($decoded === false) {
    die(json_encode([
        'Code' => 400,
        'ErrorCode' => '000002',
        'ErrorDescription' => 'Invalid Data: Corrupt or malformed base64 string.'
    ]));
}

$MyVars = @unserialize($decoded);

if ($MyVars === false || !is_array($MyVars)) {
    die(json_encode([
        'Code' => 400,
        'ErrorCode' => '000003',
        'ErrorDescription' => 'Invalid Data: Unserialization failed or data is not in expected format.'
    ]));
}

$requiredFields = [
    'MerchantKey',
    'amount',
    'RefOrder',
    'Customer_Email',
    'Customer_Name',
    'Customer_FirstName',
    'country',
    'userIP',
    'lang',
    'urlOK',
    'urlKO',
    'ipnURL'
];

$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($MyVars[$field]) || empty($MyVars[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    die(json_encode([
        'Code' => 400,
        'ErrorCode' => '000004',
        'ErrorDescription' => 'Missing Data: The following fields are missing or empty: ' . implode(', ', $missingFields)
    ]));
}

error_log("Tous les champs requis sont présents");

function apiCheck($userApi)
{
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM `users` WHERE api_key = ? AND status=1");
    $stmt->bind_param("s", $userApi);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows == 1;
}

$merchantApi = $MyVars['MerchantKey'];
$apiCheck = apiCheck($merchantApi);

if (!$apiCheck) {
    $errorResponse = array(
        'Code' => 400,
        'ErrorCode' => '000000',
        'ErrorDescription' => 'Authorization failed (Your API key is not recognized)'
    );
    echo json_encode($errorResponse);
    exit();
}

error_log("Clé API validée");

function insertTrans($reqbody)
{
    if (
        empty($reqbody['Customer_Name']) || empty($reqbody['Customer_Email']) || empty($reqbody['Customer_FirstName']) ||
        empty($reqbody['amount']) || empty($reqbody['country']) || empty($reqbody['MerchantKey'])
    ) {
        return null;
    }

    global $connection;
    $stmt = $connection->prepare("INSERT INTO transactions (name, email, ref_order, first_name, amount, country, status, token) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?,?)");

    $token = $reqbody['MerchantKey'];
    $amount = $reqbody['amount'];
    $email = $reqbody['Customer_Email'];
    $name = $reqbody['Customer_Name'];
    $first_name = $reqbody['Customer_FirstName'];
    $country = $reqbody['country'];
    $ref_order = $reqbody['RefOrder'];
    $status = 'pending';

    $stmt->bind_param("ssssdsss", $name, $email, $ref_order, $first_name, $amount, $country, $status, $token);

    if ($stmt->execute()) {
        $inserted_id = $stmt->insert_id;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    return $inserted_id;
}

$inserted_id = insertTrans($MyVars);
$encodedData = urlencode(serialize($MyVars));

// Enregistrement dans ovri_logs
try {
    $requestType = "via card";
    $requestBody = json_encode($MyVars);
    
    $query = "INSERT INTO ovri_logs (request_type, request_body, response_body, http_code, token) 
              VALUES (?, ?, '{}', 0, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("sss", $requestType, $requestBody, $MyVars['MerchantKey']);
    $stmt->execute();
    
    error_log("Transaction enregistrée dans ovri_logs");
} catch (Exception $e) {
    error_log("Erreur lors de l'enregistrement dans ovri_logs: " . $e->getMessage());
}

error_log("=== FIN PAYMENT.PHP - Redirection vers le formulaire de paiement ===");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payblis</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.jpeg" sizes="16x16">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="card.css?v=030325">
</head>

<body>

    <div class="container">
        <div class="payment-card ">

            <div class="card-header d-flex align-items-center flex-column">
                <img src="../assets/images/logo.jpeg" alt="Payblis Logo" width="100px">
                <h6 class="mt-3"><?php echo  $MyVars['amount'] ?> EUR</h6>
                <h6><?php echo  $MyVars['Customer_Name'] ?></h6>
                <span>Ref <?php echo $MyVars['RefOrder'] ?></span>
            </div>
            <!-- <div class="divider">Pay by card Visa or Mastercard</div> -->

            <form id="paymentForm" action="checkout" method="POST">

                <input type="hidden" name="array" value="<?php echo $encodedData ?>">
                <input type="hidden" name="inserted_id" value="<?php echo $inserted_id ?>">

                <div class="form-group mt-3">
                    <label for="cardHolderName">Card Name *</label>
                    <input type="text" class="form-control" id="cardHolderName" name="cardHolderName" placeholder="John Doe" required>
                    <div class="invalid-feedback" id="cardHolderNameError">Please enter a valid card holder's name (letters only).</div>
                </div>

                <div class="form-group">
                    <label for="cardHolderName">Card number *</label>
                    <input type="text" class="form-control" id="cardno" name="cardno" placeholder="1234 5678 9012 3456" maxlength="16" required>
                    <div class="invalid-feedback" id="cardnoError">Please enter a valid 16-digit card number.</div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="cardHolderName">Expiration Month *</label>
                        <input type="text" class="form-control" id="expMonth" name="expMonth" placeholder="MM" maxlength="2" required>
                        <div class="invalid-feedback" id="expMonthError">Please enter a valid expiration month</div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="cardHolderName">Expiration Year *</label>
                        <input type="text" class="form-control" id="expYear" name="expYear" placeholder="YY" maxlength="2" required>
                        <div class="invalid-feedback" id="expYearError">Expiration year cannot be less than current year.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cardHolderName">Card CVV *</label>
                    <input type="text" class="form-control" id="CVN" name="CVN" placeholder="Enter CVV" maxlength="4" required>
                    <div class="invalid-feedback" id="CVNError">Invalid CVV (3-4 digits).</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Pay <?php echo  $MyVars['amount'] ?> EUR</button>
            </form>

            <!-- <p class="text-center text-muted small mt-3">By submitting this form, you agree to the <a href="#">Privacy Policy</a></p> -->
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    <img src="https://pay.payblis.com/wp-content/uploads/2025/01/3d-secure-1.png" class="img-fluid mx-2" width="100px" alt="">
                    <img src="https://pay.payblis.com/wp-content/uploads/2025/01/pcidss-ssl-1.png" class="img-fluid mx-2" width="100px" alt="">
                </div>
            </div>
        </div>

    </div>
    <footer>
        <div class="container py-3">
            <div class="row d-flex align-items-center">
                <div class="col-lg-2 col-12 ">
                    <img src="../assets/images/logo.jpeg" alt="payblis Logo" class="img-fluid" width="100px">
                </div>
                <div class="col-lg-7 col-12 mt-sm-2 mt-md-2">
                    <span>This order process is managed by our online reseller and official merchant, Payblis.com, who also handles order inquiries and returns.</span>
                </div>
                <div class="col-lg-3 col-12 mt-sm-2 mt-md-2">
                    <a href="#">General conditions</a>
                    &nbsp; | &nbsp;
                    <a href="#">Privacy Policy</a>
                </div>
            </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardHolderNameInput = document.getElementById('cardHolderName');
            const cardnoInput = document.getElementById('cardno');
            const expMonthInput = document.getElementById('expMonth');
            const expYearInput = document.getElementById('expYear');
            const CVNInput = document.getElementById('CVN');

            // Card Holder Name Validation
            cardHolderNameInput.addEventListener('keyup', function() {
                const cardHolderName = cardHolderNameInput.value;
                if (!/^[A-Za-z\s]+$/.test(cardHolderName)) {
                    document.getElementById('cardHolderNameError').style.display = 'block';
                } else {
                    document.getElementById('cardHolderNameError').style.display = 'none';
                }
            });

            // Card Number Validation (16 digits)
            cardnoInput.addEventListener('input', function() {
                // Remove non-digit characters
                this.value = this.value.replace(/\D/g, '');
                // Limit to 16 digits
                if (this.value.length > 16) {
                    this.value = this.value.slice(0, 16);
                }
            });

            cardnoInput.addEventListener('keyup', function() {
                const cardno = cardnoInput.value;
                if (!/^\d{16}$/.test(cardno)) {
                    document.getElementById('cardnoError').style.display = 'block';
                } else {
                    document.getElementById('cardnoError').style.display = 'none';
                }
            });

            // Expiration Month Validation (2 digits, 1-12)
            expMonthInput.addEventListener('input', function() {
                // Remove non-digit characters
                this.value = this.value.replace(/\D/g, '');
                // Limit to 2 digits
                if (this.value.length > 2) {
                    this.value = this.value.slice(0, 2);
                }
            });

            expMonthInput.addEventListener('keyup', function() {
                const expMonth = expMonthInput.value;
                if (expMonth < 1 || expMonth > 12) {
                    document.getElementById('expMonthError').style.display = 'block';
                } else {
                    document.getElementById('expMonthError').style.display = 'none';
                }
            });

            // Expiration Year Validation (2 digits, >= current year)
            expYearInput.addEventListener('input', function() {
                // Remove non-digit characters
                this.value = this.value.replace(/\D/g, '');
                // Limit to 2 digits
                if (this.value.length > 2) {
                    this.value = this.value.slice(0, 2);
                }
            });

            expYearInput.addEventListener('keyup', function() {
                const expYear = expYearInput.value;
                const currentYear = new Date().getFullYear().toString().slice(-2);
                if (expYear.length !== 2 || parseInt(expYear) < parseInt(currentYear)) {
                    document.getElementById('expYearError').style.display = 'block';
                } else {
                    document.getElementById('expYearError').style.display = 'none';
                }
            });

            // CVV Validation (3 or 4 digits)
            CVNInput.addEventListener('input', function() {
                // Remove non-digit characters
                this.value = this.value.replace(/\D/g, '');
                // Limit to 4 digits
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 4);
                }
            });

            CVNInput.addEventListener('keyup', function() {
                const CVN = CVNInput.value;
                if (!/^\d{3,4}$/.test(CVN)) {
                    document.getElementById('CVNError').style.display = 'block';
                } else {
                    document.getElementById('CVNError').style.display = 'none';
                }
            });

            // Form Submission Validation
            document.getElementById('paymentForm').addEventListener('submit', function(event) {
                event.preventDefault();
                let isValid = true;

                const cardHolderName = cardHolderNameInput.value;
                const cardno = cardnoInput.value;
                const expMonth = expMonthInput.value;
                const expYear = expYearInput.value;
                const CVN = CVNInput.value;

                if (!/^[A-Za-z\s]+$/.test(cardHolderName)) {
                    document.getElementById('cardHolderNameError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('cardHolderNameError').style.display = 'none';
                }

                if (!/^\d{16}$/.test(cardno)) {
                    document.getElementById('cardnoError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('cardnoError').style.display = 'none';
                }

                if (expMonth < 1 || expMonth > 12) {
                    document.getElementById('expMonthError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('expMonthError').style.display = 'none';
                }

                const currentYear = new Date().getFullYear().toString().slice(-2);
                if (expYear.length !== 2 || parseInt(expYear) < parseInt(currentYear)) {
                    document.getElementById('expYearError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('expYearError').style.display = 'none';
                }

                if (!/^\d{3,4}$/.test(CVN)) {
                    document.getElementById('CVNError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('CVNError').style.display = 'none';
                }

                if (isValid) {
                    this.submit();
                }
            });
        });
    </script>

</body>

</html>