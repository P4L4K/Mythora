<?php
/**
 * MYTHORA - Storage Monitor & Optimization Service
 * Provides:
 * 1. Authoritative bucket storage usage calculation (from database records & cloud limits)
 * 2. Supabase Free Tier 1 GB (1,073,741,824 bytes) capacity tracking
 * 3. Threshold warnings: Normal (<70%), Warning (70%), Cleanup Recommended (85%), Critical (95%), Blocked (100%)
 * 4. Safe single & bulk video deletion with strict data protection (preserves projects & scenes)
 * 5. Pre-generation storage headroom validation
 */

namespace App\Services;

use PDO;
use App\Config\Config;

class StorageMonitorService {
    // Supabase Free Tier Storage Limit: 1 GB (1,073,741,824 bytes)
    public const MAX_STORAGE_BYTES = 1073741824; 
    // Per-file upload ceiling: 50 MB (52,428,800 bytes)
    public const MAX_PER_FILE_BYTES = 52428800;

    /**
     * Retrieves comprehensive storage metrics for user or system
     */
    public function getStorageMetrics(PDO $pdo, ?string $userId = null): array {
        // 1. Authoritative sum of all active video files from database
        $sql = "SELECT COALESCE(SUM(file_size_bytes), 0) AS total_used, COUNT(*) AS total_videos FROM videos";
        $params = [];
        if ($userId) {
            $sql .= " WHERE user_id = :uid";
            $params[':uid'] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: ['total_used' => 0, 'total_videos' => 0];

        $usedBytes = (int)$row['total_used'];
        $videoCount = (int)$row['total_videos'];
        $capacityBytes = self::MAX_STORAGE_BYTES;
        $availableBytes = max(0, $capacityBytes - $usedBytes);
        $percentUsed = round(($usedBytes / $capacityBytes) * 100, 2);

        // 2. Determine warning state according to Part 2-B
        $status = 'normal';
        $warningMessage = null;
        $canUpload = true;

        if ($percentUsed >= 100 || $availableBytes <= 5242880) { // <5MB remaining
            $status = 'exhausted';
            $canUpload = false;
            $warningMessage = 'Storage is 100% full. New video uploads are blocked until older renders are downloaded or removed.';
        } elseif ($percentUsed >= 95) {
            $status = 'critical';
            $warningMessage = 'Storage is over 95% full. Very little space remains. We strongly recommend downloading and removing older renders.';
        } elseif ($percentUsed >= 85) {
            $status = 'cleanup_recommended';
            $warningMessage = 'Storage is over 85% full. Review your library and consider cleaning up unused video renders.';
        } elseif ($percentUsed >= 70) {
            $status = 'warning';
            $warningMessage = 'Storage has reached 70% capacity. Monitor your space to ensure uninterrupted video generation.';
        }

        return [
            'capacity_bytes' => $capacityBytes,
            'capacity_formatted' => $this->formatBytes($capacityBytes),
            'used_bytes' => $usedBytes,
            'used_formatted' => $this->formatBytes($usedBytes),
            'available_bytes' => $availableBytes,
            'available_formatted' => $this->formatBytes($availableBytes),
            'percent_used' => $percentUsed,
            'video_count' => $videoCount,
            'status' => $status,
            'can_upload' => $canUpload,
            'warning_message' => $warningMessage,
            'max_per_file_bytes' => self::MAX_PER_FILE_BYTES,
            'max_per_file_formatted' => '50 MB'
        ];
    }

    /**
     * Lists all videos with associated project metadata for storage manager
     */
    public function listStoredVideos(PDO $pdo, string $userId, array $filters = []): array {
        $sort = $filters['sort'] ?? 'newest';
        $search = trim($filters['search'] ?? '');

        $orderClause = match($sort) {
            'oldest' => 'v.rendered_at ASC',
            'largest' => 'v.file_size_bytes DESC',
            'smallest' => 'v.file_size_bytes ASC',
            default => 'v.rendered_at DESC'
        };

        $sql = "
            SELECT v.id AS video_id, v.project_id, v.storage_path, v.download_url,
                   v.duration_sec, v.file_size_bytes, v.resolution, v.aspect_ratio,
                   v.rendered_at, p.title AS project_title, p.input_type
            FROM videos v
            JOIN projects p ON v.project_id = p.id
            WHERE v.user_id = :uid
        ";
        $params = [':uid' => $userId];

        if (!empty($search)) {
            $sql .= " AND (p.title ILIKE :search OR v.storage_path ILIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY {$orderClause}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(function($r) {
            $r['file_size_formatted'] = $this->formatBytes((int)$r['file_size_bytes']);
            $r['duration_formatted'] = gmdate("i:s", (int)$r['duration_sec']);
            return $r;
        }, $rows);
    }

    /**
     * Deletes a rendered video from cloud storage and DB while strictly preserving project scripts and scenes
     */
    public function deleteRenderedVideo(PDO $pdo, string $userId, string $videoId): bool {
        // 1. Fetch video and verify ownership
        $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $videoId, ':uid' => $userId]);
        $video = $stmt->fetch();
        if (!$video) {
            throw new \Exception("Video record not found or unauthorized access.");
        }

        // 2. Delete cloud storage file
        $storage = new SupabaseStorageService();
        $storagePath = $video['storage_path'];
        $deleted = $storage->deleteObjects([$storagePath]);

        // 3. Remove DB record only after storage attempt
        $delStmt = $pdo->prepare("DELETE FROM videos WHERE id = :id AND user_id = :uid");
        $delStmt->execute([':id' => $videoId, ':uid' => $userId]);

        // 4. Update project status back to draft (preserving all scenes, scripts, Content DNA)
        $pStmt = $pdo->prepare("UPDATE projects SET status = 'draft' WHERE id = :pid AND user_id = :uid");
        $pStmt->execute([':pid' => $video['project_id'], ':uid' => $userId]);

        return true;
    }

    /**
     * Bulk deletes multiple rendered videos safely
     */
    public function deleteMultipleVideos(PDO $pdo, string $userId, array $videoIds): array {
        $deleted = [];
        $failed = [];

        foreach ($videoIds as $vid) {
            try {
                $this->deleteRenderedVideo($pdo, $userId, (string)$vid);
                $deleted[] = $vid;
            } catch (\Exception $e) {
                $failed[] = ['video_id' => $vid, 'error' => $e->getMessage()];
            }
        }

        return [
            'deleted_count' => count($deleted),
            'deleted_ids' => $deleted,
            'failed_count' => count($failed),
            'failed' => $failed
        ];
    }

    /**
     * Validates if there is enough space for an expected video render
     */
    public function validateHeadroomForGeneration(PDO $pdo, string $userId, float $durationSec = 30.0, string $resolution = '1080x1920'): array {
        $metrics = $this->getStorageMetrics($pdo);

        // Estimate size based on H.264 High 1080x1920 ~650kbps + 96kbps audio
        // (~95 KB/second -> 30s is ~3.0 MB, with 2.5x safety multiplier = 7.5 MB)
        $estimatedBytes = (int)ceil($durationSec * 95000 * 2.5);

        if ($metrics['available_bytes'] < $estimatedBytes || !$metrics['can_upload']) {
            return [
                'allowed' => false,
                'reason' => 'Insufficient storage capacity. Expected render requires ~' . $this->formatBytes($estimatedBytes) . ', but only ' . $metrics['available_formatted'] . ' is available. Please free up space in the Storage Manager.',
                'metrics' => $metrics,
                'estimated_bytes' => $estimatedBytes
            ];
        }

        return [
            'allowed' => true,
            'metrics' => $metrics,
            'estimated_bytes' => $estimatedBytes
        ];
    }

    private function formatBytes(int $bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}

