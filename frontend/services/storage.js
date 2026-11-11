/**
 * MYTHORA - Storage Manager Service
 * Interfaces with /api/storage/metrics and /api/storage/videos
 */
class StorageService {
  async getMetrics() {
    return window.apiClient.get('/storage/metrics');
  }

  async listVideos(params = {}) {
    const query = new URLSearchParams(params).toString();
    const endpoint = `/storage/videos${query ? '?' + query : ''}`;
    return window.apiClient.get(endpoint);
  }

  async deleteVideo(videoId) {
    return window.apiClient.request(`/storage/videos/${videoId}`, { method: 'DELETE' });
  }

  async bulkDelete(videoIds) {
    return window.apiClient.post('/storage/videos/bulk-delete', { video_ids: videoIds });
  }
}

window.storageService = new StorageService();

