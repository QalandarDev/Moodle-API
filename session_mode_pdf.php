<?php
require 'fpdf.php';

class PDF extends FPDF
{
    public function Header()
    {
        // Logo
        $this->Image('logo.png', 10, 6, 20, 20, "PNG", "https://t.me/UrDUMoodleBot");
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(60, 6, '@UrDUMoodleBot', 1, 0, 'C', false, "https://t.me/UrDUMoodleBot");
        // Line break
        $this->Ln(20);
    }

// Page footer
    public function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Sahifa ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
// Load data
    public function LoadData($file)
    {
        // Read file lines
        $lines = file($file);
        $data = array();
        foreach ($lines as $line) {
            $data[] = explode(';', trim($line));
        }

        return $data;
    }

// Colored table
    public function FancyTable($header, $data)
    {
        // Colors, line width and bold font
        $this->SetFillColor(255, 0, 0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // Header
        $w = array(90, 35, 38, 38);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }

        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = false;
        foreach ($data as $row) {
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, $row[2], 'LR', 0, 'C', $fill);
            $this->Cell($w[3], 6, $row[3], 'LR', 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
// Column headings
$header = array('Log in', 'Last access', 'Last IP address', 'ID');
// Data loading
$data = $pdf->LoadData('sessions.txt');
$pdf->SetFont('Arial', '', 14);
$pdf->AddPage();
$pdf->FancyTable($header, $data);
$pdf->Output();
