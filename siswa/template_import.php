<?php
require_once __DIR__ . '/../includes/auth.php';

// Only Admin/Guru BK can download
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['Admin', 'Guru BK'], true)) {
    die('Access denied');
}

// Get list of kelas untuk reference
$kelasList = db_fetch('SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];

// Contoh kelas untuk baris contoh (ambil dari kelas yang ada)
$kelas1 = $kelasList[0]['nama_kelas'] ?? 'X IPA 1';
$kelas2 = isset($kelasList[1]) ? $kelasList[1]['nama_kelas'] : $kelas1;

function ss_cell(string $value, string $style = ''): string
{
    return '<Cell' . ($style !== '' ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">'
        . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</Data></Cell>';
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Template_Import_Siswa.xls"');
header('Cache-Control: max-age=0');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>Smart BK</Author>
  <Title>Template Import Siswa</Title>
  <Created><?= gmdate('Y-m-d\TH:i:s\Z') ?></Created>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>8000</WindowHeight>
  <WindowWidth>15000</WindowWidth>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="11"/>
  </Style>
  <Style ss:ID="Header">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#2563EB" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="Example">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#334155"/>
   <Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="Note">
   <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#DC2626"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Template Siswa">
  <Table ss:ExpandedColumnCount="16" ss:ExpandedRowCount="5" x:FullColumns="1" x:FullRows="1">
   <Column ss:Width="90"/>
   <Column ss:Width="170"/>
   <Column ss:Width="85"/>
   <Column ss:Width="100"/>
   <Column ss:Width="110"/>
   <Column ss:Width="105"/>
   <Column ss:Width="150"/>
   <Column ss:Width="115"/>
   <Column ss:Width="120"/>
   <Column ss:Width="150"/>
   <Column ss:Width="115"/>
   <Column ss:Width="120"/>
   <Column ss:Width="150"/>
   <Column ss:Width="180"/>
   <Column ss:Width="180"/>
   <Column ss:Width="100"/>
   <Row ss:Height="22">
    <?= ss_cell('NIPD/NIS *', 'Header') ?>
    <?= ss_cell('Nama Lengkap *', 'Header') ?>
    <?= ss_cell('Jenis Kelamin *', 'Header') ?>
    <?= ss_cell('Kelas', 'Header') ?>
    <?= ss_cell('Tempat Lahir', 'Header') ?>
    <?= ss_cell('Tanggal Lahir', 'Header') ?>
    <?= ss_cell('Nama Ayah', 'Header') ?>
    <?= ss_cell('No HP Ayah', 'Header') ?>
    <?= ss_cell('Pekerjaan Ayah', 'Header') ?>
    <?= ss_cell('Nama Ibu', 'Header') ?>
    <?= ss_cell('No HP Ibu', 'Header') ?>
    <?= ss_cell('Pekerjaan Ibu', 'Header') ?>
    <?= ss_cell('Nama Wali', 'Header') ?>
    <?= ss_cell('Alamat Orang Tua', 'Header') ?>
    <?= ss_cell('Alamat', 'Header') ?>
    <?= ss_cell('Status', 'Header') ?>
   </Row>
   <Row ss:Height="22">
    <?= ss_cell('2024001', 'Example') ?>
    <?= ss_cell('Contoh Nama Siswa', 'Example') ?>
    <?= ss_cell('L', 'Example') ?>
    <?= ss_cell($kelas1, 'Example') ?>
    <?= ss_cell('Jakarta', 'Example') ?>
    <?= ss_cell('2008-01-15', 'Example') ?>
    <?= ss_cell('Nama Ayah', 'Example') ?>
    <?= ss_cell('081234567891', 'Example') ?>
    <?= ss_cell('Wiraswasta', 'Example') ?>
    <?= ss_cell('Nama Ibu', 'Example') ?>
    <?= ss_cell('081234567892', 'Example') ?>
    <?= ss_cell('Ibu Rumah Tangga', 'Example') ?>
    <?= ss_cell('', 'Example') ?>
    <?= ss_cell('', 'Example') ?>
    <?= ss_cell('Jl. Contoh No. 1', 'Example') ?>
    <?= ss_cell('Aktif', 'Example') ?>
   </Row>
   <Row ss:Height="22"/>
   <Row ss:Height="18">
    <?= ss_cell('CATATAN PENGISIAN (hapus baris ini sebelum upload):', 'Note') ?>
   </Row>
   <Row ss:Height="18">
    <?= ss_cell('* = wajib diisi. Jenis Kelamin: L / P. Tanggal Lahir: YYYY-MM-DD. Status: Aktif / Tidak Aktif / Pindah / Lulus. Kelas harus sesuai daftar: ' . implode(', ', array_column($kelasList, 'nama_kelas')), 'Note') ?>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
