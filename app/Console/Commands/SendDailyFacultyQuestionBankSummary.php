<?php

namespace App\Console\Commands;

use App\Models\QuestionBankItem;
use App\Models\User;
use App\Notifications\DailyFacultyQuestionBankSummaryNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:faculty-question-bank-daily-summary')]
#[Description("Notify Admins with the previous day's Faculty question-bank totals")]
class SendDailyFacultyQuestionBankSummary extends Command
{
    private const REPORT_TIMEZONE = 'Asia/Manila';

    public function handle(): int
    {
        $reportEnd = CarbonImmutable::now(self::REPORT_TIMEZONE)->startOfDay();
        $reportStart = $reportEnd->subDay();
        $reportDate = $reportStart->toDateString();
        $questionCountsByFaculty = QuestionBankItem::query()
            ->where('created_at', '>=', $reportStart->utc())
            ->where('created_at', '<', $reportEnd->utc())
            ->selectRaw('author_id, COUNT(*) AS questions_added')
            ->groupBy('author_id')
            ->pluck('questions_added', 'author_id');
        $facultyCounts = User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $faculty): array => [
                'faculty_name' => $faculty->name,
                'questions_added' => (int) ($questionCountsByFaculty[$faculty->id] ?? 0),
            ])
            ->all();
        $totalQuestionsAdded = (int) collect($facultyCounts)->sum('questions_added');
        $admins = User::query()->where('role', 'super-admin')->get();

        if ($admins->isEmpty()) {
            $this->error('No Admin accounts were found to receive the daily summary.');

            return self::FAILURE;
        }

        foreach ($admins as $admin) {
            $alreadyNotified = $admin->notifications()
                ->where('type', DailyFacultyQuestionBankSummaryNotification::class)
                ->where('data->report_date', $reportDate)
                ->exists();
            if ($alreadyNotified) {
                continue;
            }

            $admin->notify(new DailyFacultyQuestionBankSummaryNotification(
                $reportDate,
                $totalQuestionsAdded,
                $facultyCounts,
            ));
        }

        $this->info("Daily summary for {$reportDate} sent to {$admins->count()} Admin account(s).");

        return self::SUCCESS;
    }
}
