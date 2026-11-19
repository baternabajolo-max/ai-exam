<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\Process as SymfonyProcess;

class PdfTextExtractor
{
    public function extract(string $pdfPath): string
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('The reviewer PDF could not be found.');
        }

        $pythonBinary = config('services.math_pdf.python_binary');
        if (! is_string($pythonBinary) || trim($pythonBinary) === '') {
            throw new RuntimeException('The Python executable for math PDF processing is not configured.');
        }

        $pythonArguments = config('services.math_pdf.python_arguments', []);
        if (! is_array($pythonArguments)
            || array_filter($pythonArguments, static fn (mixed $argument): bool => ! is_string($argument)) !== []) {
            throw new RuntimeException('The Python arguments for math PDF processing are invalid.');
        }

        $scriptPath = config('services.math_pdf.script');
        if (! is_string($scriptPath) || ! is_file($scriptPath)) {
            throw new RuntimeException('The math PDF processing script could not be found.');
        }

        $timeout = config('services.math_pdf.timeout');
        if (! is_int($timeout) || $timeout < 1) {
            throw new RuntimeException('The math PDF processing timeout is invalid.');
        }

        $mode = config('services.math_pdf.mode');
        if (! is_string($mode) || ! in_array($mode, ['fast', 'balanced'], true)) {
            throw new RuntimeException('The math PDF processing mode must be fast or balanced.');
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'lemu-reviewer-text-');
        if ($temporaryFile === false) {
            throw new RuntimeException('A temporary file for reviewer text could not be created.');
        }

        unlink($temporaryFile);
        $outputPath = $temporaryFile.'.md';
        $progressBuffer = '';

        try {
            $process = Process::timeout($timeout)
                ->env([
                    'TORCH_DEVICE' => 'cuda',
                    'PYTHONUTF8' => '1',
                    'PYTHONIOENCODING' => 'utf-8',
                    'MATH_PDF_MODE' => $mode,
                ])
                ->run([
                    $pythonBinary,
                    ...$pythonArguments,
                    $scriptPath,
                    $pdfPath,
                    '--output',
                    $outputPath,
                ], static function (string $type, string $output) use (&$progressBuffer): void {
                    if ($type !== SymfonyProcess::OUT) {
                        return;
                    }

                    $progressBuffer .= $output;
                    while (($newline = strpos($progressBuffer, "\n")) !== false) {
                        $line = trim(substr($progressBuffer, 0, $newline));
                        $progressBuffer = substr($progressBuffer, $newline + 1);

                        if (str_starts_with($line, 'PAGE_PROGRESS ')) {
                            Log::info('Math PDF processing page progress.', [
                                'progress' => substr($line, strlen('PAGE_PROGRESS ')),
                            ]);
                        }
                    }
                });

            $remainingProgress = trim($progressBuffer);
            if (str_starts_with($remainingProgress, 'PAGE_PROGRESS ')) {
                Log::info('Math PDF processing page progress.', [
                    'progress' => substr($remainingProgress, strlen('PAGE_PROGRESS ')),
                ]);
            }

            if ($process->failed()) {
                Log::error('Math PDF Python process failed.', [
                    'python_binary' => $pythonBinary,
                    'python_arguments' => $pythonArguments,
                    'pdf_path' => $pdfPath,
                    'exit_code' => $process->exitCode(),
                    'stdout' => $process->output(),
                    'stderr' => $process->errorOutput(),
                ]);

                throw new RuntimeException(sprintf(
                    "CUDA math PDF processing exited with code %s.\nSTDERR:\n%s\nSTDOUT:\n%s",
                    $process->exitCode() ?? 'unknown',
                    trim($process->errorOutput()),
                    trim($process->output()),
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
