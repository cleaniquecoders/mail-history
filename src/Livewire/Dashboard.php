<?php

namespace CleaniqueCoders\MailHistory\Livewire;

use CleaniqueCoders\MailHistory\Actions\Contracts\MailHistoryReport;
use Livewire\Component;

class Dashboard extends Component
{
    public int $days = 30;

    public string $trendInterval = 'daily';

    public ?string $viewingHash = null;

    public function updatedDays(): void
    {
        $this->viewingHash = null;
    }

    public function viewTimeline(string $hash): void
    {
        $this->viewingHash = $this->viewingHash === $hash ? null : $hash;
    }

    public function render()
    {
        $report = app(MailHistoryReport::class);
        $from = now()->subDays($this->days);
        $to = now();

        $summary = $report->summary($from, $to);
        $trends = $report->trends($this->trendInterval, $from, $to);
        $providers = $report->byProvider($from, $to);
        $activity = $report->recentActivity(20);
        $stale = $report->stale('Sending', 60);
        $topBounced = $report->topRecipients('Bounced', 5, $from, $to);
        $topComplained = $report->topRecipients('Complained', 5, $from, $to);

        $timeline = null;
        if ($this->viewingHash) {
            try {
                $timeline = $report->timeline($this->viewingHash);
            } catch (\Throwable) {
                $this->viewingHash = null;
            }
        }

        return view('mailhistory::livewire.dashboard', [
            'summary' => $summary,
            'trends' => $trends,
            'providers' => $providers,
            'activity' => $activity,
            'stale' => $stale,
            'topBounced' => $topBounced,
            'topComplained' => $topComplained,
            'timeline' => $timeline,
        ]);
    }
}
