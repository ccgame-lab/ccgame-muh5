<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * @return list<array<string, mixed>>
     */
    public function sdkPayload(): array
    {
        try {
            return Announcement::query()
                ->active()
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->body,
                    'body' => $announcement->body,
                    'type' => $announcement->type,
                    'icon' => $announcement->icon ?? $this->defaultIcon((string) $announcement->type),
                    'link' => $announcement->link,
                    'created_at' => $announcement->created_at?->toDateTimeString(),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Return the latest active announcement (for polling).
     */
    public function latest(Request $request): JsonResponse
    {
        $announcement = Announcement::query()
            ->active()
            ->latest('id')
            ->first();

        if (! $announcement) {
            return response()->json(['id' => 0]);
        }

        $user = $request->user();

        // Nếu user đã xem thông báo này rồi thì không trả về nữa
        if ($user && (int) $user->last_seen_announcement_id >= (int) $announcement->id) {
            return response()->json(['id' => 0]);
        }

        return response()->json([
            'id' => $announcement->id,
            'title' => $announcement->title,
            'body' => $announcement->body,
            'type' => $announcement->type,
            'icon' => $announcement->icon ?? $this->defaultIcon((string) $announcement->type),
            'link' => $announcement->link,
        ]);
    }

    private function defaultIcon(string $type): string
    {
        return match ($type) {
            'success' => '🎉',
            'warning' => '⚠️',
            default => '📢',
        };
    }

    /**
     * Acknowledge that the user has seen an announcement.
     */
    public function acknowledge(Request $request): JsonResponse
    {
        $request->validate([
            'announcement_id' => 'required|integer|exists:announcements,id',
        ]);

        $user = $request->user();
        $announcementId = (int) $request->input('announcement_id');

        if ($user && (int) $user->last_seen_announcement_id < $announcementId) {
            $user->update(['last_seen_announcement_id' => $announcementId]);
        }

        return response()->json(['success' => true]);
    }
}
