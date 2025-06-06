<?php
if (isset($_POST['upload'])) {
    $uploadDir = 'uploads/';
    $file = $_FILES['pdf_file'];

    // Create the uploads folder if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = basename($file['name']);
    $targetFilePath = $uploadDir . time() . "_" . $fileName;

    // Validate PDF file
    $fileType = mime_content_type($file['tmp_name']);
    if ($fileType === 'application/pdf') {
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            echo "PDF uploaded successfully: <a href='$targetFilePath' target='_blank'>View PDF</a>";
        } else {
            echo "Error: Could not move the uploaded file.";
        }
    } else {
        echo "Error: Only PDF files are allowed.";
    }
} else {
    echo "Invalid access.";
}
?>
