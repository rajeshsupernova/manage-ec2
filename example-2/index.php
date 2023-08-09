<?php
require 'vendor/autoload.php'; // Load AWS SDK
require 'config3.php'; // Include the AWS and authentication credentials

use Aws\Ec2\Ec2Client;
use Aws\Exception\AwsException;

// Initialize EC2 client
$ec2Client = new Ec2Client([
    'credentials' => $awsCredentials,
    'region' => $awsCredentials['region'], // Pass the region directly
    'version' => 'latest',
]);

// Start session
session_start();

// Check if the user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $isLoggedIn = true;
    $currentUsername = $_SESSION['username'];
} else {
    $isLoggedIn = false;
    $currentUsername = null;
}

// Check for session timeout
if ($isLoggedIn && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    session_unset();
    session_destroy();
    $isLoggedIn = false;
    $currentUsername = null;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Initialize variables for displaying the options
$startOption = '';
$stopOption = '';
$errorMessage = '';
$instanceDetails = '';

/* Check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Perform authentication
    if (array_key_exists($username, $loginCredentials) && $password === $loginCredentials[$username]) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Refresh:0"); // Refresh the page to show control options
    } else {
        $errorMessage = 'Invalid username or password.';
    }*/
// Check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Perform authentication
    if (array_key_exists($username, $loginCredentials) && $password === $loginCredentials[$username]) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Refresh:0"); // Refresh the page to show control options
    } else {
        $errorMessage = 'Invalid username or password.';
    }
// Clear the error message before setting success message
$errorMessage = '';
    if ($isLoggedIn) {
        if (isset($_POST['start'])) {
            try {
                // Start the EC2 instance
                $result = $ec2Client->startInstances([
                    'InstanceIds' => [$_POST['instance_id']],
                ]);
		$successMessage = 'Instance start request sent successfully.';
//                echo 'Instance start request sent successfully.';
            } catch (AwsException $e) {
                $errorMessage = 'Error starting instance: ' . $e->getAwsErrorMessage();
            }
        } elseif (isset($_POST['stop'])) {
            try {
                // Stop the EC2 instance
                $result = $ec2Client->stopInstances([
                    'InstanceIds' => [$_POST['instance_id']],
                ]);

//                echo 'Instance stop request sent successfully.';
		$successMessage = 'Instance stop request sent successfully.';
            } catch (AwsException $e) {
                $errorMessage = 'Error stopping instance: ' . $e->getAwsErrorMessage();
            }
        } elseif (isset($_POST['get_details'])) {
            try {
                // Get instance details
                $result = $ec2Client->describeInstances([
                    'InstanceIds' => [$_POST['instance_id']],
                ]);

                $instanceDetails = json_encode($result->search('Reservations[].Instances[]'), JSON_PRETTY_PRINT);
            } catch (AwsException $e) {
                $errorMessage = 'Error getting instance details: ' . $e->getAwsErrorMessage();
            }
        } elseif (isset($_POST['logout'])) {
            // Log out user
            session_unset();
            session_destroy();
            header("Refresh:0"); // Refresh the page after logging out
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>EC2 Instance Control</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <?php if ($isLoggedIn) : ?>
                    <h2 class="card-title">EC2 Instance Control (Logged in as <?php echo $currentUsername; ?>)</h2>
                <?php else : ?>
                    <h2 class="card-title">Login</h2>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$isLoggedIn) : ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <?php if (!empty($errorMessage)) : ?>
                            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                <?php else : ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="instance_id">Instance ID:</label>
                            <input type="text" class="form-control" id="instance_id" name="instance_id" required>
                        </div>
                        <button type="submit" name="start" class="btn btn-success">Start Instance</button>
                        <button type="submit" name="stop" class="btn btn-danger">Stop Instance</button>
                        <button type="submit" name="get_details" class="btn btn-info">Get Instance Details</button>
                    </form>
</br>		<form method="post">
			<button type="submit" name="logout" class="btn btn-secondary">Logout</button>
		</form>

<?php if (!empty($instanceDetails)) : ?>
                        <div class="mt-3">
                            <h3>Instance Details:</h3>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Instance ID</th>
                                        <th>Instance Type</th>
                                        <th>State</th>
                                        <th>Public IP Address</th>
					<th>Private IP Address</th>
                                        <!-- Add more columns as needed -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $details = json_decode($instanceDetails, true);
                                    foreach ($details as $instance) :
                                    ?>
                                    <tr>
                                        <td><?php echo $instance['InstanceId']; ?></td>
                                        <td><?php echo $instance['InstanceType']; ?></td>
                                        <td><?php echo $instance['State']['Name']; ?></td>
                                        <td><?php echo $instance['PublicIpAddress']; ?></td>
					<td><?php echo $instance['PrivateIpAddress']; ?></td>
                                        <!-- Add more columns as needed -->
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($errorMessage)) : ?>
                    <div class="alert alert-danger mt-3"><?php echo $errorMessage; ?></div>
                <?php endif; ?>
                    <?php if (!empty($successMessage)) : ?>
                        <div class="alert alert-success mt-3"><?php echo $successMessage; ?></div>
                    <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>