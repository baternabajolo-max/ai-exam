<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class PdfTextExtractor
{
    public function extract(string $pdfPath): string
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('The reviewer PDF could not be found.');
        }

        $pythonBinary = config('services.pymupdf.python_binary');
        if (! is_string($pythonBinary) || trim($pythonBinary) === '') {
            throw new RuntimeException('The Python executable for reviewer extraction is not configured.');
        }

        $pythonArguments = config('services.pymupdf.python_arguments', []);
        if (! is_array($pythonArguments)
            || array_filter($pythonArguments, static fn (mixed $argument): bool => ! is_string($argument)) !== []) {
            throw new RuntimeException('The Python arguments for reviewer extraction are invalid.');
        }

        $scriptPath = config('services.pymupdf.script');
        if (! is_string($scriptPath) || ! is_file($scriptPath)) {
            throw new RuntimeException('The PyMuPDF extraction script could not be found.');
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'lemu-reviewer-text-');
        if ($temporaryFile === false) {
            throw new RuntimeException('A temporary file for reviewer text could not be created.');
        }

        unlink($temporaryFile);
        $outputPath = $temporaryFile.'.md';

        try {
            $process = new Process([
                $pythonBinary,
                ...$pythonArguments,
                $scriptPath,
                $pdfPath,
                '--output',
                $outputPath,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(sprintf(
                    'Local OCR extraction exited with code %s: %s',
                    $process->getExitCode() ?? 'unknown',
                    trim($process->getErrorOutput()),
                ));
            }

            $extractedText = file_get_contents($outputPath);
            if ($extractedText === false) {
                throw new RuntimeException('The extracted reviewer text could not be read.');
            }

            return trim($extractedText);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }
}
