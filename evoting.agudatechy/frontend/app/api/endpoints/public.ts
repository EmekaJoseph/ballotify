import api from '../index';
import type { Choice, EventResults, NominationForm, NominationResult, PublicEvent, ReceiptCheck, ResultCertificateInfo } from '~/types';

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


    nominationForm: (token: string) => api.get<NominationForm>(`/public/events/${token}/nominations`),
    nominate: (token: string, data: { category_id: number; nominee_name: string; reason?: string; email?: string; captcha_token?: string; device_id?: string }) =>
        api.post<{ message: string }>(`/public/events/${token}/nominations`, data),
    /** Every category at once (several names each): one request, one "I'm human" check. */
    nominateMany: (token: string, data: { nominations: { category_id: number; nominee_name: string; reason?: string }[]; captcha_token?: string; device_id?: string }) =>
        api.post<NominationResult>(`/public/events/${token}/nominations/batch`, data),


    verifyCertificate: (serial: string) => api.get<ResultCertificateInfo>(`/public/certificates/${encodeURIComponent(serial)}`),


};
