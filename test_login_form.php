<?php

// Simple test to simulate login form submission
$url = 'https://1000proxy.io/login';
$email = 'demo@1000proxy.io';
$password = '123456789';

// First, get the login page to get CSRF token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";

if ($httpCode == 200) {
    echo "✅ Login page loaded successfully\n";
    
    // Extract CSRF token
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches)) {
        $csrfToken = $matches[1];
        echo "✅ CSRF token found: " . substr($csrfToken, 0, 10) . "...\n";
        
        // Now try to submit the form
        $postData = [
            '_token' => $csrfToken,
            'email' => $email,
            'password' => $password,
            'remember' => false
        ];
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'X-CSRF-TOKEN: ' . $csrfToken,
            'X-Requested-With: XMLHttpRequest'
        ]);
        
        $loginResponse = curl_exec($ch);
        $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        echo "Login attempt HTTP Code: $loginHttpCode\n";
        
        if ($loginHttpCode == 302) {
            echo "✅ Login appears successful (redirect response)\n";
            $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            echo "Redirect URL: $redirectUrl\n";
        } else {
            echo "❌ Login failed or unexpected response\n";
            echo "Response preview: " . substr($loginResponse, 0, 200) . "...\n";
        }
    } else {
        echo "❌ CSRF token not found in response\n";
    }
} else {
    echo "❌ Failed to load login page\n";
}

curl_close($ch);
echo "Test completed.\n";
