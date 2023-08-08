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
                <?php
                // Check if the user is logged in
                session_start();
                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                    echo '<h2 class="card-title">EC2 Instance Control</h2>';
                } else {
                    echo '<h2 class="card-title">Login</h2>';
                }
                ?>
            </div>
            <div class="card-body">
                <?php
                require 'vendor/autoload.php'; // Load AWS SDK
                require 'config.php'; // Include the AWS and authentication credentials

                use Aws\Ec2\Ec2Client;

                // Initialize EC2 client
				$ec2Client = new Ec2Client([
					'credentials' => $awsCredentials,
					'region' => $awsCredentials['region'], // Pass the region directly
					'version' => 'latest',
				]);
				
                // Initialize variables for displaying the options
                $startOption = '';
                $stopOption = '';
                $errorMessage = '';

                // Check if the user is logged in
                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (isset($_POST['start'])) {
                            // Start the EC2 instance
                            $result = $ec2Client->startInstances([
                                'InstanceIds' => [$instanceId],
                            ]);
                            echo 'Instance started successfully.';
                        } elseif (isset($_POST['stop'])) {
                            // Stop the EC2 instance
                            $result = $ec2Client->stopInstances([
                                'InstanceIds' => [$instanceId],
                            ]);
                            echo 'Instance stopped successfully.';
                        }
                    }

                    // Set the options for displaying after successful login
                    $startOption = '<button type="submit" name="start" class="btn btn-success">Start Instance</button>';
                    $stopOption = '<button type="submit" name="stop" class="btn btn-danger">Stop Instance</button>';
                } else {
                    // Check if the login form was submitted
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $username = $_POST['username'];
                        $password = $_POST['password'];

                        // Perform basic authentication (for demonstration purposes)
			    if ($username === $loginCredentials['username'] && $password === $loginCredentials['password']) {
        			$_SESSION['loggedin'] = true;
        			header("Refresh:0"); // Refresh the page to show control options
    				} else {
        				$errorMessage = 'Invalid username or password.';
    			}
                    }
                }
                ?>

                <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) : ?>
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
		<?php

		try {
        		$result = $ec2Client->describeInstances([
            		'InstanceIds' => [$instanceId],
        		]);

        	foreach ($result['Reservations'] as $reservation) {
            		foreach ($reservation['Instances'] as $instance) {
                		$instanceId = $instance['InstanceId'];
                		$instanceState = $instance['State']['Name'];
                		$instanceType = $instance['InstanceType'];
                		$publicIpAddress = isset($instance['PublicIpAddress']) ? $instance['PublicIpAddress'] : 'N/A';

                		echo '<div class="container">';
                		echo "<h5><Instance ID: $instanceId</h5>";
                		echo "<p>Instance State: $instanceState</p>";
                		echo "<p>Instance Type: $instanceType</p>";
                		echo "<p>Public IP Address: $publicIpAddress</p>";
                		echo '</div>';
            		}
        	}
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    ?>

                    <form method="post">
                        <?php echo $startOption; ?>
                        <?php echo $stopOption; ?>
                        <button type="button" class="btn btn-secondary ml-3" onclick="window.location.href='logout.php'">Logout</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
