<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Changelog;
use App\Models\Server;
use Illuminate\Console\Command;

class ChangelogSync extends Command
{
    protected $signature = 'changelog:sync {server_id : Server ID to sync changelog for}';

    protected $description = 'Sync changelog from game server docs/changelog.md into database';

    public function handle(): int
    {
        $serverId = (int) $this->argument('server_id');
        $server = Server::find($serverId);

        if (! $server) {
            $this->error("Server ID {$serverId} not found.");

            return self::FAILURE;
        }

        $changelogPath = $server->server_path.'/docs/changelog.md';
        if (! file_exists($changelogPath)) {
            $this->error("Changelog file not found: {$changelogPath}");

            return self::FAILURE;
        }

        $content = file_get_contents($changelogPath);
        if ($content === false || $content === '') {
            $this->error("Changelog file is empty or unreadable: {$changelogPath}");

            return self::FAILURE;
        }

        $synced = $this->parseChangelog($content, $serverId);

        $this->info("Synced {$synced} changelog entries for server {$server->name}.");

        return self::SUCCESS;
    }

    private function parseChangelog(string $content, int $serverId): int
    {
        $synced = 0;

        // Split by ## YYYY-MM-DD pattern
        $entries = preg_split('/\n(?=## \d{4}-\d{2}-\d{2})/', $content);
        if ($entries === false) {
            return 0;
        }

        foreach ($entries as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            // Extract date and optional title from heading
            if (! preg_match('/^## (\d{4}-\d{2}-\d{2})(?:\s+\((.*?)\))?\s*$/m', $entry, $heading)) {
                continue;
            }

            $versionDate = $heading[1];
            $title = $heading[2] ?? $versionDate;

            // Extract all bullet lines after heading as player_notes
            $lines = explode("\n", $entry);
            array_shift($lines); // remove heading line
            $contentLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '## ')) {
                    continue;
                }
                $contentLines[] = $line;
            }
            $playerNotes = implode("\n", $contentLines);

            // Clean markdown bullets
            $playerNotes = (string) preg_replace('/^- /m', '', $playerNotes);
            $playerNotes = trim($playerNotes);

            Changelog::updateOrCreate(
                [
                    'server_id' => $serverId,
                    'version_date' => $versionDate,
                    'title' => $title,
                ],
                [
                    'dev_notes' => null,
                    'player_notes' => $playerNotes,
                    'is_published' => true,
                    'sort_order' => 0,
                ]
            );

            $synced++;
        }

        return $synced;
    }
}
