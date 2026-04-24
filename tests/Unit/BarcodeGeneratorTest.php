<?php

use App\Support\BarcodeGenerator;

test('barcode filenames are sanitized for downloads and zip entries', function () {
    $generator = new BarcodeGenerator;

    expect($generator->safeFilename('../unsafe name "test"'))->toBe('unsafe-name-test')
        ->and($generator->safeFilename(''))->toBe('barcode');
});
