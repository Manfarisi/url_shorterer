<?php

namespace App\Jobs;

use App\Models\LinkClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;

class RecordLinkClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $linkId,
        private ?string $ipAddress,
        private ?string $referrer,
        private ?string $userAgent,
    ) {}

    public function handle(): void
    {
        $agent = new Agent();
        $agent->setUserAgent($this->userAgent);

        LinkClick::create([
            'link_id' => $this->linkId,
            'ip_address' => $this->ipAddress,
            'referrer' => $this->referrer,
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'clicked_at' => now(),
        ]);
    }
}