<?php

namespace AndrewSvirin\Ebics\Services;

use RuntimeException;
use ZipArchive;

/**
 * Read zipped content.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
final class ZipService
{
    /**
     * Create temporary file to store zipped string.
     * @return string
     */
    private function createTmpFile(): string
    {
        if (!($path = tempnam(sys_get_temp_dir(), 'ebics-file'))) {
            throw new RuntimeException('Can not create temporary dir.');
        }
        return $path;
    }

    /**
     * Read zipped string and extract file content items.
     *
     * @param string $zippedContent
     *
     * @return array
     */
    public function extractFilesFromString(string $zippedContent): array
    {
        // save content into temp file
        $tempFile = $this->createTmpFile();
        file_put_contents($tempFile, $zippedContent);

        $zip = new ZipArchive();
        if (true !== $zip->open($tempFile)) {
            throw new RuntimeException('Zip archive was not opened.');
        }

        // Read zipped order data items.
        $fileContentItems = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileContentItems[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }

        // Close zip.
        $zip->close();
        // Remove temporary file.
        unlink($tempFile);

        return $fileContentItems;
    }

    /**
     * Uncompress from gz.
     *
     * @param string $compressed
     *
     * @return string
     */
    public function uncompress(string $compressed): string
    {
        if (!($uncompressed = gzuncompress($compressed))) {
            throw new RuntimeException('Data can not be uncompressed.');
        }

        return $uncompressed;
    }

    /**
     * Compress to gz.
     *
     * @param string $uncompressed
     *
     * @return string
     */
    public function compress(string $uncompressed): string
    {
        if (!($compressed = gzcompress($uncompressed))) {
            throw new RuntimeException('Data can not be compressed.');
        }

        return $compressed;
    }
}
