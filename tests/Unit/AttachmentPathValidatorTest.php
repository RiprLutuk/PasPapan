<?php

use App\Support\AttachmentPathValidator;

test('attachment path validator accepts safe relative paths', function () {
    $validator = new AttachmentPathValidator();

    expect($validator->isSafeRelativePath('attendance_photos/2026/04/19/check-in.jpg'))->toBeTrue()
        ->and($validator->isSafeRelativePath('reimbursements/receipt.pdf'))->toBeTrue();
});

test('attachment path validator rejects traversal absolute and remote paths', function () {
    $validator = new AttachmentPathValidator();

    expect($validator->isSafeRelativePath('../secret.txt'))->toBeFalse()
        ->and($validator->isSafeRelativePath('/etc/passwd'))->toBeFalse()
        ->and($validator->isSafeRelativePath('https://example.com/file.jpg'))->toBeFalse()
        ->and($validator->isSafeRelativePath('C:\\temp\\file.jpg'))->toBeFalse();
});
