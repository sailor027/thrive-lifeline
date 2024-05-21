<?php
// Path to the CSV file
$csvFile = '/Users/khruc/GitHub/thrive-lifeline/thrive_resources.csv';

// Open the CSV file for reading
$fileHandle = fopen($csvFile, 'r');

// Check if the file was opened successfully
if ($fileHandle !== false) {
    // Read the CSV file line by line
    while (($row = fgetcsv($fileHandle)) !== false) {
        // Print each row as an array
        print_r($row);
    }

    // Close the file handle
    fclose($fileHandle);
} else {
    // Error opening the file
    echo "Error opening $csvFile";
}
?>