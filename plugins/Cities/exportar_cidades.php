<?php

$host   = '127.0.0.1';
$port   = '3306';
$dbname = 'lunarapps_lb';
$user   = 'root';
$pass   = 'mysql53';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$outputPath = __DIR__ . '/cities.csv.gz';
$fp = fopen('compress.zlib://' . $outputPath, 'wb');

if ($fp === false) {
    exit("Erro ao criar o arquivo {$outputPath}\n");
}

$columns = [
    'id', 'city', 'city_ascii', 'lat', 'lng',
    'country', 'iso2', 'iso3', 'timezone',
    'admin_name', 'capital', 'population',
];

fputcsv($fp, $columns, ',', '"', '\\');

$stmt = $pdo->query('SELECT ' . implode(', ', $columns) . ' FROM cities ORDER BY id ASC');

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($fp, [
        $row['id'],
        $row['city'],
        $row['city_ascii'],
        $row['lat'],
        $row['lng'],
        $row['country'],
        $row['iso2'],
        $row['iso3'],
        $row['timezone'],
        $row['admin_name'],
        $row['capital'],
        $row['population'],
    ], ',', '"', '\\');
    $count++;
}

fclose($fp);

echo "Concluido: {$count} cidades exportadas para {$outputPath}\n";
