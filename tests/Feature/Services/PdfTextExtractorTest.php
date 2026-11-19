<?php

namespace Tests\Feature\Services;

use App\Services\PdfTextExtractor;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
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
            if (
                ! is_file($argv[1])
                || $argv[2] !== '--output'
                || getenv('TORCH_DEVICE') !== 'cuda'
                || getenv('PYTHONUTF8') !== '1'
                || getenv('PYTHONIOENCODING') !== 'utf-8'
                || getenv('MATH_PDF_MODE') !== 'fast'
            ) {
                exit(2);
            }
            file_put_contents(__DIR__.'/output-path.txt', $argv[3]);
            file_put_contents($argv[3], <<<'MARKDOWN'
                # Extracted reviewer

                The formula is $$E = mc^2$$.
                MARKDOWN);
            PHP);

        config([
            'services.math_pdf.python_binary' => PHP_BINARY,
            'services.math_pdf.python_arguments' => [],
            'services.math_pdf.script' => $scriptPath,
            'services.math_pdf.timeout' => 60,
            'services.math_pdf.mode' => 'fast',
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

    public function test_logs_python_stdout_and_stderr_when_extraction_fails(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lemu-pdf-extractor-'.bin2hex(random_bytes(6));
        mkdir($directory);

        $pdfPath = $directory.DIRECTORY_SEPARATOR.'reviewer.pdf';
        $scriptPath = $directory.DIRECTORY_SEPARATOR.'failing-extractor.php';
        file_put_contents($pdfPath, '%PDF-1.7');
        file_put_contents($scriptPath, <<<'PHP'
            <?php
            fwrite(STDOUT, "PAGE_PROGRESS page=1/1 started\nstdout marker error");
            fwrite(STDERR, 'stderr marker error');
            exit(7);
            PHP);

        config([
            'services.math_pdf.python_binary' => PHP_BINARY,
            'services.math_pdf.python_arguments' => [],
            'services.math_pdf.script' => $scriptPath,
            'services.math_pdf.timeout' => 60,
            'services.math_pdf.mode' => 'fast',
        ]);
        Log::shouldReceive('info')
            ->once()
            ->with('Math PDF processing page progress.', ['progress' => 'page=1/1 started']);
        Log::shouldReceive('error')
            ->once()
            ->with('Math PDF Python process failed.', Mockery::on(
                static fn (array $context): bool => $context['exit_code'] === 7
                    && $context['stdout'] === "PAGE_PROGRESS page=1/1 started\nstdout marker error"
                    && $context['stderr'] === 'stderr marker error'
                    && $context['python_binary'] === PHP_BINARY,
            ));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('STDERR:');

            (new PdfTextExtractor)->extract($pdfPath);
        } finally {
            foreach ([$pdfPath, $scriptPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            rmdir($directory);
        }
    }
}
