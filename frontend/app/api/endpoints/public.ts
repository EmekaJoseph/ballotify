import api from '../index';
import type { CandidateProfile, Choice, EventResults, NominationForm, PlanInfo, PublicEvent, ReceiptCheck, ResultCertificateInfo } from '~/types';

export const publicApi = {
    /** Ballot for an open event. Responds 403 with {status, message} when not open, 404 when unknown. */
    getEvent: (token: string) => api.get<PublicEvent>(`/public/events/${token}`),

    validateCode: (token: string, code: string) =>
        api.post<{
            valid: boolean; name?: string | null; message?: string; categories?: number[];
            /** Votes this ballot counts as (own weight + proxies). */
            weight?: number; own_weight?: number;
            /** Members this voter also votes for (proxy). */
            proxy_for?: string[];
            /** Refused because someone holds this member's proxy. */
            proxy?: boolean;
        }>(`/public/events/${token}/code/validate`, { code }),

    /** A candidate's photo (through the API, so campaign cards can draw it). */
    candidatePhotoUrl: (token: string, candidateId: number) => `${String(api.defaults.baseURL ?? '').replace(/\/+$/, '')}/public/events/${token}/candidates/${candidateId}/photo`,

    getResults: (token: string) => api.get<EventResults>(`/public/events/${token}/results`),

    castVote: (token: string, payload: { code: string; choices: Choice[] }) =>
        api.post<{ message: string; receipt?: string }>(`/public/events/${token}/vote`, payload),

    /** Check a ballot receipt (or a paid-vote payment reference). */
    checkReceipt: (token: string, receipt: string) => api.post<ReceiptCheck>(`/public/events/${token}/receipt`, { receipt }),

    /** Self-registration: the code is sent to the contact the organiser has on file. */
    requestCode: (token: string, data: { member_id: string; captcha_token?: string }) =>
        api.post<{ message: string; sent_to: string[] }>(`/public/events/${token}/request-code`, data),

    nominationForm: (token: string) => api.get<NominationForm>(`/public/events/${token}/nominations`),
    nominate: (token: string, data: { category_id: number; nominee_name: string; reason?: string; email?: string; captcha_token?: string }) =>
        api.post<{ message: string }>(`/public/events/${token}/nominations`, data),

    candidateProfile: (token: string) => api.get<CandidateProfile>(`/public/candidate-profile/${token}`),
    updateCandidateProfile: (token: string, form: FormData) => api.post<CandidateProfile>(`/public/candidate-profile/${token}`, form),

    verifyCertificate: (serial: string) => api.get<ResultCertificateInfo>(`/public/certificates/${encodeURIComponent(serial)}`),

    /** Paid voting: start a Paystack checkout for N votes. */
    buyVotes: (token: string, data: { candidate_id: number; quantity: number; email: string; name?: string; captcha_token?: string }) =>
        api.post<{ authorization_url: string; reference: string }>(`/public/events/${token}/paid-votes`, data),
    verifyPaidVotes: (reference: string) =>
        api.post<{ status: 'pending' | 'success' | 'failed' | 'late'; message: string; quantity: number; candidate: string; reference: string }>('/public/paid-votes/verify', { reference }),

    /** Free vs Pro pricing for the landing page. */
    plans: () => api.get<PlanInfo>('/public/plans'),
};
