/**
 * VYRAL STUDIO - Video Generation Pipeline & Job Polling Service
 * Handles Hook Lab, Script Gen, Scene Segmentation, and Job Queue Tracking
 */
class GenerationService {
  /**
   * Hook Lab (Feature B): Generates 3 opening variations (Curiosity, Story, Educational)
   */
  async generateHooks(prompt, platform = 'tiktok') {
    return await window.apiClient.post('/generation/hooks', { prompt, platform });
  }

  /**
   * Generates complete structured script & scene breakdown
   */
  async generateScript(params) {
    return await window.apiClient.post('/generation/script', params);
  }

  /**
   * Submits project to background rendering queue
   */
  async startGeneration(projectId) {
    return await window.apiClient.post('/generation/start', { project_id: projectId });
  }

  /**
   * Retrieves accurate job progress and real backend stage
   */
  async getJobStatus(jobId) {
    return await window.apiClient.get(`/generation/jobs/${jobId}`);
  }

  /**
   * Polls job progress with reactive callbacks (No fake progress percentages)
   */
  pollJob(jobId, onProgress, onComplete, onError) {
    const config = window.MYTHORA_CONFIG || window.VYRAL_CONFIG || {};
    const pollInterval = config.POLL_INTERVAL_MS || 2500;
    
    const intervalId = setInterval(async () => {
      try {
        const job = await this.getJobStatus(jobId);
        
        if (onProgress) {
          onProgress(job);
        }

        if (job.status === 'completed') {
          clearInterval(intervalId);
          if (onComplete) onComplete(job);
        } else if (job.status === 'failed' || job.status === 'cancelled') {
          clearInterval(intervalId);
          if (onError) onError(new Error(job.error_message || 'Video generation failed'));
        }
      } catch (err) {
        clearInterval(intervalId);
        if (onError) onError(err);
      }
    }, pollInterval);

    return () => clearInterval(intervalId);
  }
}

window.generationService = new GenerationService();

