<?php
require 'vendor/autoload.php';
include '../include/connection.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['scholarship_id'])) {
    $selectedScholarshipId = $_GET['scholarship_id'];
    $selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'Accepted'; // Default to 'Accepted' if status not provided
    $query = "
        SELECT ua.applicant_name, ua.id_number, ua.course, ua.gender, ua.scholarship_name, ts.benefits, ua.status
        FROM tbl_userapp ua
        JOIN tbl_scholarship ts ON ua.scholarship_id = ts.scholarship_id
        WHERE ua.scholarship_id = ? AND ua.status = ?
        UNION
        SELECT sf.applicant_name, sf.id_number, sf.course, sf.gender, sf.scholarship_name, ts.benefits, sf.status
        FROM tbl_scholarship_1_form sf
        JOIN tbl_scholarship ts ON sf.scholarship_id = ts.scholarship_id
        WHERE sf.scholarship_id = ? AND sf.status = ?
    ";
    $stmt = mysqli_prepare($dbConn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $selectedScholarshipId, $selectedStatus, $selectedScholarshipId, $selectedStatus);
    }
} else {
    $selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'Accepted'; // Default to 'Accepted' if status not provided
    $query = "
        SELECT ua.applicant_name, ua.id_number, ua.course, ua.gender, ua.scholarship_name, ts.benefits, ua.status
        FROM tbl_userapp ua
        JOIN tbl_scholarship ts ON ua.scholarship_id = ts.scholarship_id
        WHERE ua.status = ?
        UNION
        SELECT sf.applicant_name, sf.id_number, sf.course, sf.gender, sf.scholarship_name, ts.benefits, sf.status
        FROM tbl_scholarship_1_form sf
        JOIN tbl_scholarship ts ON sf.scholarship_id = ts.scholarship_id
        WHERE sf.status = ?
    ";
    $stmt = mysqli_prepare($dbConn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $selectedStatus, $selectedStatus);
    }
}

if ($stmt && mysqli_stmt_execute($stmt)) {
    $result = mysqli_stmt_get_result($stmt);
} else {
    die('Error preparing or executing statement: ' . mysqli_error($dbConn));
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$number = 1;
$rowNumber = 1;

$sheet->setCellValue('A1', 'No.');
$sheet->setCellValue('B1', 'ID Number');
$sheet->setCellValue('C1', 'Applicant Name');
$sheet->setCellValue('D1', 'Course');
$sheet->setCellValue('E1', 'Gender');
$sheet->setCellValue('F1', 'Type of Scholarship');
$sheet->setCellValue('G1', 'Privelege/Benefits');

while ($row = mysqli_fetch_assoc($result)) {
    $applicantName = $row['applicant_name'];
    $idNumber = $row['id_number'];
    $course = $row['course'];
    $scholarshipName = $row['scholarship_name'];
    $sex = $row['gender'];
    $benefits = $row['benefits'];

    $sheet->setCellValue('A' . ($rowNumber + 1), $number);
    $sheet->setCellValue('B' . ($rowNumber + 1), $idNumber);
    $sheet->setCellValue('C' . ($rowNumber + 1), $applicantName);
    $sheet->setCellValue('D' . ($rowNumber + 1), $course);
    $sheet->setCellValue('E' . ($rowNumber + 1), $sex);
    $sheet->setCellValue('F' . ($rowNumber + 1), $scholarshipName);
    $sheet->setCellValue('G' . ($rowNumber + 1), $benefits);

    $number++;
    $rowNumber++;
}

$writer = new Xlsx($spreadsheet);

// Send appropriate headers for Excel download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="qualified_applicants.xlsx"');
$writer->save('php://output');
?>
