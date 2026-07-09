<?php
// Functional-medicine report configuration.
return [
    'engine_version' => '1.0.0',
    'disclaimer' => 'This report is intended for clinical and educational purposes only. '
        .'It does not replace individualised medical advice, diagnosis, or treatment. '
        .'All findings and recommendations should be reviewed and applied by a qualified '
        .'healthcare practitioner in the context of the patient\'s full clinical picture.',
    // Where generated PDFs are written (private disk, NOT public).
    'pdf_disk' => 'local',
];
