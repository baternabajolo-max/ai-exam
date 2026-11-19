<?php

namespace App\Jobs;

/**
 * Keep queued jobs created before the math-processing pipeline upgrade runnable.
 */
class ExtractSubtopicText extends ProcessMathPdfJob {}
