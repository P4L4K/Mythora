<?php
/**
 * VYRAL STUDIO - Asynchronous Background Worker Daemon
 * Polls `generation_jobs` table for queued tasks, renders via FFmpeg, and notifies state
 */

require_once __DIR__ . '/../backend/src/Config/Config.php';
require_once __DIR__ . '/../backend/src/Database/Database.php';
require_once __DIR__ . '/../backend/src/Services/VideoRendererService.php';

use App\Config\Config;
use App\Database\Database;
use App\Services\VideoRendererService;

Config::load();
$pdo = Database::getConnection();
$renderer = new VideoRendererService();

$pollInterval = (int)Config::get('WORKER_POLL_INTERVAL_SEC', 3);
echo "🌿 MYTHORA Render Worker daemon started. Polling PostgreSQL generation_jobs queue every {$pollInterval}s...\n";

while (true) {
    try {
        // Find next pending or queued job
        $stmt = $pdo->prepare("SELECT * FROM generation_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
        $stmt->execute();
        $job = $stmt->fetch();

        if ($job) {
            $jobId = $job['id'];
            $projectId = $job['project_id'];
            echo "🎬 Processing Job: {$jobId} for Project: {$projectId}\n";

            // Update to processing
            $pdo->prepare("UPDATE generation_jobs SET status = 'processing', current_stage = 'rendering_ffmpeg', started_at = CURRENT_TIMESTAMP WHERE id = :id")
                ->execute([':id' => $jobId]);

            try {
                $res = $renderer->renderProject($pdo, $jobId, $projectId);
                $pdo->prepare("UPDATE generation_jobs SET status = 'completed', current_stage = 'completed', progress_percent = 100, completed_at = CURRENT_TIMESTAMP WHERE id = :id")
                    ->execute([':id' => $jobId]);
                $pdo->prepare("UPDATE projects SET status = 'ready' WHERE id = :id")->execute([':id' => $projectId]);
                echo "✅ Job {$jobId} completed successfully: {$res['download_url']}\n";
            } catch (\Exception $e) {
                echo "❌ Job {$jobId} failed: " . $e->getMessage() . "\n";
                $pdo->prepare("UPDATE generation_jobs SET status = 'failed', current_stage = 'failed', error_message = :err WHERE id = :id")
                    ->execute([':err' => $e->getMessage(), ':id' => $jobId]);
                $pdo->prepare("UPDATE projects SET status = 'failed' WHERE id = :id")->execute([':id' => $projectId]);
            }
        }
    } catch (\Throwable $err) {
        echo "Worker error: " . $err->getMessage() . "\n";
    }

    sleep($pollInterval);
}

