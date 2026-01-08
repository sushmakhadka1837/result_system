<?php
require 'db_config.php'; // DB connection

// Target page
$url = "https://kailaba.com/engineering-notes-kailaba/";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
$html = curl_exec($ch);
curl_close($ch);

// Use DOMDocument to parse HTML
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($doc);

// Find note items
$items = $xpath->query("//div[contains(@class,'post-content')]//a[contains(@href,'.pdf')]");

foreach($items as $a){
    $pdf_link = $a->getAttribute('href');
    $pdf_title = trim($a->textContent);

    // Simple heuristic: subject + semester + department
    // You can refine based on actual page structure
    $subject_name = "Unknown"; 
    $semester = "Unknown";
    $department = "Computer"; 
    $resource_type = "Theory";

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO kailaba_resources 
        (subject_name, semester, department, resource_type, pdf_title, pdf_link) 
        VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $subject_name, $semester, $department, $resource_type, $pdf_title, $pdf_link);
    $stmt->execute();
}

echo "Scraping completed!";
?>
