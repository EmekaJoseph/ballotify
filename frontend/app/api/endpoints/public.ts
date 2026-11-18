import api from '../index';
import type { Choice, EventResults, PublicEvent } from '~/types';

export const publicApi = {
    /** Ballot for an open event. Responds 403 with {status, message} when not open, 404 when unknown. */
    getEvent: (token: string) => api.get<PublicEvent>(`/public/events/${token}`),

    validateCode: (token: string, code: string) =>
        api.post<{ valid: boolean; message?: string }>(`/public/events/${token}/code/validate`, { code }),

    getResults: (token: string) => api.get<EventResults>(`/public/events/${token}/results`),

    castVote: (token: string, payload: { code: string; choices: Choice[] }) =>
        api.post<{ message: string }>(`/public/events/${token}/vote`, payload),
};
