<?php
require('../fpdf/fpdf.php');
include '../config.php';

$id = $_GET['id'];
// Get request + resident info
$result = $conn->query("SELECT r.certificate_type, c.fullname, c.address, c.birthdate 
                        FROM certificate_requests r 
                        JOIN residents c ON r.resident_id = c.id 
                        WHERE r.id=$id AND r.status='Approved'");

$data = $result->fetch_assoc();

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Barangay Certificate',0,1,'C');
        $this->Ln(5);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,10,
    "To whom it may concern:\n\nThis is to certify that {$data['fullname']} of {$data['address']} is issued a {$data['certificate_type']}.\n\nIssued on: " . date('F d, Y')
);
$pdf->Output();
?>