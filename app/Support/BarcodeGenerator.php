<?php

namespace App\Support;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class BarcodeGenerator
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected $qrGenerator = new QrCode(''),
        protected $writer = new PngWriter(),
        protected $manager = new ImageManager(Driver::class),
        protected $width = 720,
        protected $height = 720,
    ) {
        //
    }

    public function generateQrCode($value)
    {
        $qrCode = $this->qrGenerator
            ->create($value)
            ->setSize(300)
            ->setMargin(20)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $file = $this->writer->write(qrCode: $qrCode)->getString();

        return $this->manager->create($this->width, $this->height)
            ->fill('#fff')
            ->place($this->manager->read($file)->scale($this->width), 'center')
            ->toPng();
    }

    /**
     * @param array<string, string> $values name => value
     */
    public function generateQrCodesZip(array $values)
    {
        $zip = new \ZipArchive();
        $dir = public_path('temp');
        if (!file_exists($dir)) {
            mkdir($dir, recursive: true);
        }
        $zipFile = public_path('temp/barcodes.zip');
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $usedNames = [];
        foreach ($values as $name => $value) {
            $barcodeFile = $this->generateQrCode($value);
            $entryName = $this->uniqueZipEntryName($this->safeFilename($name ?? $value), $usedNames);
            $zip->addFromString($entryName . '.png', $barcodeFile->toString());
        }
        $zip->close();
        return $zipFile;
    }

    public function safeFilename(?string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $name);
        $name = trim((string) $name, ".-_\t\n\r\0\x0B");

        return substr($name !== '' ? $name : 'barcode', 0, 120);
    }

    /**
     * @param array<string, bool> $usedNames
     */
    protected function uniqueZipEntryName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $counter = 2;

        while (isset($usedNames[$candidate])) {
            $candidate = substr($name, 0, 110) . '-' . $counter;
            $counter++;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }
}
