<?php

namespace App\Http\Controllers;

use App\Jobs\RecordLinkClick;
use App\Models\Link;
use App\Models\LinkClick;
use App\Services\Base62Encoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Jenssegers\Agent\Agent;

class LinkController extends Controller
{
    public function __construct(private Base62Encoder $encoder) {}

    public function store(Request $request)
    {
        $validated = $request->validate(['original_url' => 'required|url|max:2048',]);

        $link = Link::create([
            'user_id' => $request->user()?->id,
            'original_url' => $validated['original_url'],
            'short_code' => 'temp',
        ]);

        $link->short_code = $this->encoder->encode($link->id);
        $link->save();

        return response()->json([
            'short_code' => $link->short_code,
            'short_url' => url($link->short_code),
            'original_url' => $link->original_url,
        ], 201);
    }
    public function redirect(string $code)
    {
        $id = $this->encoder->decode($code);
        $cacheKey = "link:{$code}";

        $originalUrl = Cache::remember($cacheKey, now()->addHours(24), function () use ($id) {
            $link = Link::findOrFail($id);
            return $link->original_url;
        });

        Link::where('id', $id)->increment('click_count');
        Link::where('id', $id)->update(['last_clicked_at' => now()]);

        RecordLinkClick::dispatch(
            $id,
            request()->ip(),
            request()->header('referer'),
            request()->userAgent(),
        );

        return Redirect::away($originalUrl);
    }

    public function analytics(int $id)
    {
        $link = Link::findOrFail($id);

        $clicksPerDay = LinkClick::where('link_id', $id)
            ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $deviceBreakdown = LinkClick::where('link_id', $id)
            ->select('device_type', DB::raw('COUNT(*) as total'))
            ->groupBy('device_type')
            ->get();

        $browserBreakdown = LinkClick::where('link_id', $id)
            ->select('browser', DB::raw('COUNT(*) as total'))
            ->groupBy('browser')
            ->get();

        return response()->json([
            'link' => $link,
            'total_clicks' => $link->click_count,
            'clicks_per_day' => $clicksPerDay,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
        ]);
    }
}
