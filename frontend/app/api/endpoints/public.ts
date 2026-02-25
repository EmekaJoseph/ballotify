import api from '../index';

export const publicApi = {
    /**
     * Get public event data by token
     */
    getEventByToken: (token: string) =>
        api.get(`/public/events/${token}`),

    /**
     * Validate voter code for an event (by token)
     */
    validateCodeByToken: (token: string, code: string) =>
        api.post(`/public/events/${token}/code/validate`, { code }),

    /**
     * Validate voter code for an event (by event ID)
     */
    validateCodeByEvent: (eventId: number | string, code: string) =>
        api.post(`/public/events/${eventId}/code/validate`, { code }),

    /**
     * Get public event results by token
     */
    getResultsByToken: (token: string) =>
        api.get(`/public/events/${token}/results`),

    /**
     * Cast a vote for an event
     */
    castVote: (token: string, payload: { code: string; choices: { category_id: number; candidate_id: number }[] }) =>
        api.post(`/public/events/${token}/vote`, payload),
};
