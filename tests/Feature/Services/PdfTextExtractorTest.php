<?php

namespace Tests\Feature\Services;

use App\Services\PdfTextExtractor;
use Tests\TestCase;

class PdfTextExtractorTest extends TestCase
{
    public function test_runs_the_configured_extractor_and_returns_its_markdown_output(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lemu-pdf-extractor-'.bin2hex(random_bytes(6));
        mkdir($directory);

        $pdfPath = $directory.DIRECTORY_SEPARATOR.'reviewer.pdf';
        $scriptPath = $directory.DIRECTORY_SEPARATOR.'fake-extractor.php';
        $outputPathRecord = $directory.DIRECTORY_SEPARATOR.'output-path.txt';
        file_put_contents($pdfPath, '%PDF-1.7');
        file_put_contents($scriptPath, <<<'PHP'
            <?php
            if (! is_file($argv[1]) || $argv[2] !== '--output') {
                exit(2);
            }
            file_put_contents(__DIR__.'/output-path.txt', $argv[3]);
            file_put_contents($argv[3], <<<'MARKDOWN'
                # Extracted reviewer

                The formula is $$E = mc^2$$.
                MARKDOWN);
            PHP);

        config([
            'services.pymupdf.python_binary' => PHP_BINARY,
            'services.pymupdf.python_arguments' => [],
            'services.pymupdf.script' => $scriptPath,
        ]);

        $outputPath = null;
        try {
            $extractedText = (new PdfTextExtractor)->extract($pdfPath);
            $outputPath = file_get_contents($outputPathRecord);

            $this->assertSame('# Extracted reviewer'."\n\n".'The formula is $$E = mc^2$$.', $extractedText);
            $this->assertIsString($outputPath);
            if (is_string($outputPath)) {
                $this->assertFileDoesNotExist($outputPath);
            }
        } finally {
            foreach ([$pdfPath, $scriptPath, $outputPathRecord, $outputPath] as $path) {
                if (is_string($path) && is_file($path)) {
                    unlink($path);
                }
            }
            rmdir($directory);
        }
    }
}
