<?php
// Mock HTTP POST to gallery_api.php
$url = 'http://localhost/KarnatakaTrekkers/admin/treks/gallery_api.php';
$data = ['action' => 'list', 'session_token' => 'dummy_token'];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) {
    echo "Error fetching data";
} else {
    echo "Result: " . $result;
}
?>
