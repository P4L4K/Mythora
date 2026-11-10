/**
 * VYRAL STUDIO - Projects & Scene Management Service
 */
class ProjectsService {
  async listProjects() {
    return await window.apiClient.get('/projects');
  }

  async getProject(id) {
    return await window.apiClient.get(`/projects/${id}`);
  }

  async createProject(projectData) {
    return await window.apiClient.post('/projects', projectData);
  }

  async updateProject(id, projectData) {
    return await window.apiClient.put(`/projects/${id}`, projectData);
  }

  async deleteProject(id) {
    return await window.apiClient.delete(`/projects/${id}`);
  }

  async updateScene(sceneId, sceneData) {
    return await window.apiClient.put(`/scenes/${sceneId}`, sceneData);
  }

  async regenerateScene(sceneId, options = {}) {
    return await window.apiClient.post(`/scenes/${sceneId}/regenerate`, options);
  }
}

window.projectsService = new ProjectsService();

