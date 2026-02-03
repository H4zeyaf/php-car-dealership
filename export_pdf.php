<?php
require('includes/fpdf.php');
require('includes/db.php');

// 1. Check ID
if(!isset($_GET['id'])) die("ID manquant");
$id = $_GET['id'];

// 2. Fetch Car Data
$stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$car) die("Voiture introuvable");

// 3. Create PDF Class
class PDF extends FPDF {
    function Header() {
        // Logo (Replace 'uploads/logo.png' if you have a logo, otherwise comment out)
        // $this->Image('uploads/logo.png',10,6,30);
        $this->SetFont('Arial','B',15);
        $this->Cell(80);
        $this->Cell(30,10,'MHB AUTOMOBILES',0,0,'C');
        $this->Ln(20);
        $this->SetDrawColor(230, 57, 70); // Red Line
        $this->SetLineWidth(1);
        $this->Line(10, 25, 200, 25);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().' - MHB Automobiles Tanger',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();

// --- TITLE & PRICE ---
$pdf->SetFont('Arial','B',24);
$title = iconv('UTF-8', 'windows-1252', $car['make'] . ' ' . $car['model']); // Fix encoding
$pdf->Cell(0, 10, $title, 0, 1, 'C');

$pdf->SetFont('Arial','B',16);
$pdf->SetTextColor(230, 57, 70); // Red Color
$price = number_format($car['sale_price'] > 0 ? $car['sale_price'] : $car['price'], 0, ',', ' ') . ' DH';
$pdf->Cell(0, 10, $price, 0, 1, 'C');
$pdf->SetTextColor(0,0,0); // Reset color

$pdf->Ln(10);

// --- IMAGE ---
$img = 'uploads/' . $car['image'];
if(file_exists($img) && !empty($car['image'])) {
    // 1. Get the REAL file type (ignores the extension)
    $imageInfo = getimagesize($img);
    $mime = $imageInfo['mime'];
    
    // 2. Map mime type to FPDF format
    $type = '';
    if($mime == 'image/jpeg') $type = 'JPG';
    elseif($mime == 'image/png') $type = 'PNG';
    elseif($mime == 'image/gif') $type = 'GIF';
    
    // 3. Only add image if type is supported
    if($type) {
        // '30, 60, 150' = X pos, Y pos, Width
        $pdf->Image($img, 30, 60, 150, 0, $type); 
        $pdf->Ln(110); 
    } else {
        // If it's WebP or unsupported, just skip the image to avoid crashing
        $pdf->Cell(0, 10, "(Image format non supporte par le PDF)", 0, 1, 'C');
        $pdf->Ln(10);
    }
} else {
    $pdf->Ln(10);
}
// --- SPECS TABLE ---
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 10, 'Annee', 1, 0, 'L', true);
$pdf->Cell(95, 10, $car['year'], 1, 1, 'L');

$pdf->Cell(95, 10, 'Reference', 1, 0, 'L', true);
$pdf->Cell(95, 10, '#REF-'.$car['id'], 1, 1, 'L');

// --- DESCRIPTION ---
$pdf->Ln(10);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, 'Description', 0, 1, 'L');
$pdf->SetFont('Arial','',12);
$desc = iconv('UTF-8', 'windows-1252', $car['description']); 
$pdf->MultiCell(0, 8, $desc);

// --- CONTACT INFO ---
$pdf->Ln(20);
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(0,0,0);
$pdf->SetTextColor(255,255,255);
$pdf->Cell(0, 15, "CONTACTEZ-NOUS AU 06 13 14 12 46 POUR RESERVER", 0, 1, 'C', true);

$pdf->Output('D', 'Fiche_MHB_'.$car['id'].'.pdf'); // Force Download
?>