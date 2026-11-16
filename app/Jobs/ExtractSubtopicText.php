<?php

namespace App\Jobs;

use App\Models\Subtopic;
use App\Services\PdfTextExtractor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ExtractSubtopicText implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(public int $subtopicId, public string $filePath) {}

    public function uniqueId(): string
    {
        return $this->subtopicId.':'.hash('sha256', $this->filePath);
    }

    public function handle(PdfTextExtractor $extractor): void
    {
        $subtopic = Subtopic::query()
            ->whereKey($this->subtopicId)
            ->where('file_path', $this->filePath)
            ->first();

        if ($subtopic === null) {
            return;
        }

        if ($subtopic->text_extraction_status === Subtopic::TEXT_EXTRACTION_COMPLETED) {
            return;
        }

        if (! Storage::disk('public')->exists($this->filePath)) {
            throw new RuntimeException('The uploaded reviewer file is missing from public storage.');
        }

        $subtopic->update([
            'extracted_text' => null,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PROCESSING,
        ]);

        $extractedText = $extractor->extract(Storage::disk('public')->path($this->filePath));

        Subtopic::query()
            ->whereKey($this->subtopicId)
            ->where('file_path', $this->filePath)
            ->update([
                'extracted_text' => $extractedText,
                'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
            ]);
    }

    public function failed(?Throwable $exception): void
    {
        Subtopic::query()
            ->whereKey($this->subtopicId)
            ->where('file_path', $this->filePath)
            ->update([
                'extracted_text' => null,
                'text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED,
            ]);

        Log::error('Reviewer PDF text extraction failed.', [
            'subtopic_id' => $this->subtopicId,
            'file_path' => $this->filePath,
            'exception' => $exception,
        ]);
    }
}
