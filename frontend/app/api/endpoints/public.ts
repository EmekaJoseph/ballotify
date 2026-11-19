import api from '../index';
import type { Choice, EventResults, PlanInfo, PublicEvent, ReceiptCheck } from '~/types';

export const publicApi = {
    /** Ballot for an open event. Responds 403 with {status, message} when not open, 404 when unknown. */
    getEvent: (token: string) => api.get<PublicEvent>(`/public/events/${token}`),

    validateCode: (token: string, code: string) =>
        api.post<{ valid: boolean; name?: string | null; message?: string }>(`/public/events/${token}/code/validate`, { code }),

    getResults: (token: string) => api.get<EventResults>(`/public/events/${token}/results`),

    castVote: (token: string, payload: { code: string; choices: Choice[] }) =>
        api.post<{ message: string; receipt?: string }>(`/public/events/${token}/vote`, payload),

    /** Check a ballot receipt (or a paid-vote payment reference). */
    checkReceipt: (token: string, receipt: string) => api.post<ReceiptCheck>(`/public/events/${token}/receipt`, { receipt }),

    /** Paid voting: start a Paystack checkout for N votes. */
    buyVotes: (token: string, data: { candidate_id: number; quantity: number; email: string; name?: string }) =>
        api.post<{ authorization_url: string; reference: string }>(`/public/events/${token}/paid-votes`, data),
    verifyPaidVotes: (reference: string) =>
        api.post<{ status: 'pending' | 'success' | 'failed' | 'late'; message: string; quantity: number; candidate: string; reference: string }>('/public/paid-votes/verify', { reference }),

    /** Free vs Pro pricing for the landing page. */
    plans: () => api.get<PlanInfo>('/public/plans'),
};
