<?php
// AWS credentials
$awsCredentials = [
    'key'    => 'YOUR_AWS_ACCESS_KEY',
    'secret' => 'YOUR_AWS_SECRET_KEY',
    'region' => 'us-east-1', // Update with your desired region
];

// Authentication credentials for multiple users
$loginCredentials = [
    'user1' => 'password1',
    'user2' => 'password2',
    // Add more users and passwords as needed
];

// Session timeout in seconds (default: 1800 seconds = 30 minutes)
$sessionTimeout = 1800;
?>
