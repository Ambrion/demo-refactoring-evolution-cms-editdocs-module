<?php
echo '<h1>✅ Rewrite работает!</h1>';
echo '<pre>';
echo 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . "\n";
echo 'QUERY_STRING: ' . $_SERVER['QUERY_STRING'] . "\n";
echo 'q parameter: ' . ($_GET['q'] ?? 'NOT SET') . "\n";
echo 'DOCUMENT_ROOT: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo '</pre>';
