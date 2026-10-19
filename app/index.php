<?php

echo "Hello, World!<br>";

try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo "MySQL connection: OK";
} catch (PDOException $e) {
    echo "MySQL connection: FAILED — " . $e->getMessage();
}
