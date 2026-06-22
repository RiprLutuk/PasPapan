<?php

namespace App\Support;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;

class BarcodeGenerator
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected $writer = new PngWriter,
        protected $manager = new ImageManager(Driver::class),
        protected $width = 720,
        protected $height = 720,
    ) {
        //
    }

    public function generateQrCode($value): EncodedImageInterface
    {
        $qrCode = new QrCode(
            data: (string) $value,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 20,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $file = $this->writer->write(qrCode: $qrCode)->getString();

        return $this->manager->createImage($this->width, $this->height)
            ->fill('#fff')
            ->insert($this->manager->decodeBinary($file)->scale($this->width), alignment: 'center')
            ->encode(new PngEncoder);
    }

    /**
     * @param  array<string, string>  $values  name => value
     */
    public function generateQrCodesZip(array $values): string
    {
        $zip = new \ZipArchive;
        $dir = storage_path('app/private/temp');
        if (! file_exists($dir)) {
            mkdir($dir, recursive: true);
        }
        $zipFile = $dir.'/barcodes-'.bin2hex(random_bytes(8)).'.zip';
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $usedNames = [];
        foreach ($values as $name => $value) {
            $barcodeFile = $this->generateQrCode($value);
            $entryName = $this->uniqueZipEntryName($this->safeFilename($name ?? $value), $usedNames);
            $zip->addFromString($entryName.'.png', $barcodeFile->toString());
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
     * @param  array<string, bool>  $usedNames
     */
    protected function uniqueZipEntryName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $counter = 2;

        while (isset($usedNames[$candidate])) {
            $candidate = substr($name, 0, 110).'-'.$counter;
            $counter++;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }
}
