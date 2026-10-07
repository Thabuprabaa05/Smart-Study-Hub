<?php
// Script to generate valid sample PDFs for pre-seeded notes
$targetDir = __DIR__ . '/../uploads/notes/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$files = [
    'sample_dbms_normalization.pdf' => "Smart Study Hub - DBMS SQL & Normalization Cheatsheet\nAuthor: Rahul Sharma\nTopics: 1NF, 2NF, 3NF, BCNF, SQL Joins, Indexing, ACID Transactions.",
    'sample_os_midterm.pdf' => "Smart Study Hub - Operating Systems Midterm Review\nAuthor: Priya Patel\nTopics: CPU Scheduling (FCFS, SJF, RR), Deadlocks, Mutex & Semaphores, Memory Paging.",
    'sample_cn_subnetting.pdf' => "Smart Study Hub - Computer Networks TCP/IP & Subnetting Guide\nAuthor: Ananya Sen\nTopics: OSI 7-Layer Model, IPv4 & IPv6 Subnetting, TCP 3-Way Handshake, DNS & HTTP.",
    'sample_se_patterns.pdf' => "Smart Study Hub - Software Engineering Design Patterns\nAuthor: Rahul Sharma\nTopics: Factory, Singleton, Observer, Strategy Patterns, Agile Scrum, UML Class Diagrams.",
    'sample_webtech_security.pdf' => "Smart Study Hub - Web Technologies & PHP PDO Security\nAuthor: Ananya Sen\nTopics: PDO Prepared Statements, Session Authentication, CSRF Protection, Password Hashing.",
    'sample_dsa_graphs.pdf' => "Smart Study Hub - Data Structures & Algorithms Graphs\nAuthor: Priya Patel\nTopics: BFS, DFS, Dijkstra Shortest Path, Minimum Spanning Trees (Kruskal/Prim), Dynamic Programming."
];

function generatePdf($text, $filePath) {
    $lines = explode("\n", $text);
    $stream = "BT\n/F1 14 Tf\n50 740 Td\n22 TL\n";
    foreach ($lines as $line) {
        $clean = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
        $stream .= "(" . $clean . ") '\n";
    }
    $stream .= "ET";
    $streamLen = strlen($stream);

    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    $pdf .= "4 0 obj\n<< /Length $streamLen >>\nstream\n" . $stream . "\nendstream\nendobj\n";
    $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000266 00000 n \n0000000344 00000 n \ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n424\n%%EOF";
    file_put_contents($filePath, $pdf);
}

foreach ($files as $filename => $content) {
    generatePdf($content, $targetDir . $filename);
}

echo "Created " . count($files) . " sample PDF documents in uploads/notes/\n";
