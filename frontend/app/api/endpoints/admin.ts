import api from '../index';

export const adminApi = {
    // Authentication
    register: (data: any) => api.post('/auth/register', data),
    login: (data: any) => api.post('/auth/login', data),
    logout: () => api.post('/auth/logout'),
    getMe: () => api.get('/me'),

    // Events
    getEvents: () => api.get('/events'),
    createEvent: (data: any) => api.post('/events', data),
    getEventDetails: (eventId: number | string) => api.get(`/events/${eventId}`),
    updateEvent: (eventId: number | string, data: any) => api.patch(`/events/${eventId}`, data),
    deleteEvent: (eventId: number | string) => api.delete(`/events/${eventId}`),
    getVotingLink: (eventId: number | string) => api.get(`/events/${eventId}/voting-link`),

    // Categories
    createCategory: (eventId: number | string, data: { name: string }) =>
        api.post(`/events/${eventId}/categories`, data),

    // Candidates
    createCandidate: (eventId: number | string, formData: FormData) =>
        api.post(`/events/${eventId}/candidates`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }),
    updateCandidate: (eventId: number | string, candidateId: number | string, formData: FormData) =>
        api.patch(`/events/${eventId}/candidates/${candidateId}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }),
    deleteCandidate: (eventId: number | string, candidateId: number | string) =>
        api.delete(`/events/${eventId}/candidates/${candidateId}`),

    // Voters
    getVoters: (eventId: number | string) => api.get(`/events/${eventId}/voters`),
    createVoter: (eventId: number | string, data: { code: string; name?: string }) =>
        api.post(`/events/${eventId}/voters`, data),
    updateVoter: (eventId: number | string, voterId: number | string, data: any) =>
        api.patch(`/events/${eventId}/voters/${voterId}`, data),
    deleteVoter: (eventId: number | string, voterId: number | string) =>
        api.delete(`/events/${eventId}/voters/${voterId}`),
    generateVoters: (eventId: number | string, data: { count?: number; names?: string[] }) =>
        api.post(`/events/${eventId}/voters/generate`, data),
    importVoters: (eventId: number | string, data: { names: string[] }) =>
        api.post(`/events/${eventId}/voters/import`, data),
};
