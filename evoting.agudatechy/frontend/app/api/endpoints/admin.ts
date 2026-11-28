import api from '../index';
import type { AccountLogEntry, AuditEntry, BallotEvent, Candidate, CandidateImportResult, CandidateImportRow, Category, EventResults, EventTemplate, GeneratedCode, MemberRole, MessagingStatus, Paginated, PlatformEvent, PlatformOverview, PlatformUser, ResultsVisibility, SystemHealth, TeamInfo, TeamMember, User, Voter, VoterContact, NominationSummary, ResultCertificateInfo } from '~/types';

type Id = number | string;

export interface EventPayload {
    name?: string;
    description?: string | null;
    results_visibility?: ResultsVisibility;
    starts_at?: string | null;
    ends_at?: string | null;
    reminder_at?: string | null;
    /** Create only: start from a template. */
    template?: string | null;
    nominations_enabled?: boolean;
    nominations_ends_at?: string | null;
}

/** Sign-in either completes, or asks for the authenticator code. */
export type LoginResponse = { token: string; user: User } | { two_factor: true; challenge: string };

export const adminApi = {
    // Authentication
    register: (data: { name: string; email: string; password: string; terms: boolean; captcha_token?: string }) =>
        api.post<{ token: string; user: User }>('/auth/register', data),
    login: (data: { email: string; password: string }) => api.post<LoginResponse>('/auth/login', data),
    twoFactorChallenge: (data: { challenge: string; code: string }) =>
        api.post<{ token: string; user: User }>('/auth/two-factor', data),
    forgotPassword: (email: string) => api.post<{ message: string }>('/auth/forgot-password', { email }),
    resetPassword: (data: { token: string; email: string; password: string; password_confirmation: string }) =>
        api.post<{ message: string }>('/auth/reset-password', data),
    /** `link` is the signed path from the verification email (starts with /api/…). */
    verifyEmail: (link: string) => api.get<{ verified: boolean }>(link.replace(/^\/api(?=\/)/, '')),
    resendVerification: () => api.post<{ message: string }>('/auth/email/resend'),
    logout: () => api.post('/auth/logout'),
    getMe: () => api.get<User>('/me'),
    updateMe: (data: { name?: string; current_password?: string; password?: string }) => api.patch<User>('/me', data),
    /** First-run tour seen or skipped. */
    markOnboarded: () => api.post<User>('/me/onboarded'),
    /** Everything we hold about the account, as JSON. */
    exportMyData: () => api.get<Record<string, unknown>>('/me/export'),
    deleteAccount: (password: string) => api.delete<{ message: string }>('/me', { data: { password } }),
    // Two-step login
    twoFactorSetup: (password: string) => api.post<{ secret: string; otpauth_url: string }>('/me/two-factor', { password }),
    twoFactorConfirm: (code: string) => api.post<{ recovery_codes: string[]; user: User }>('/me/two-factor/confirm', { code }),
    twoFactorDisable: (password: string) => api.delete<{ user: User }>('/me/two-factor', { data: { password } }),
    twoFactorStatus: () => api.get<{ enabled: boolean; recovery_codes_left: number }>('/me/two-factor'),
    twoFactorRecoveryCodes: (password: string) => api.post<{ recovery_codes: string[] }>('/me/two-factor/recovery-codes', { password }),

    // Events
    getTemplates: () => api.get<EventTemplate[]>('/event-templates'),
    getEvents: () => api.get<BallotEvent[]>('/events'),
    createEvent: (data: EventPayload) => api.post<BallotEvent>('/events', data),
    getEvent: (eventId: Id) => api.get<BallotEvent>(`/events/${eventId}`),
    updateEvent: (eventId: Id, data: EventPayload) => api.patch<BallotEvent>(`/events/${eventId}`, data),
    /** Event logo (multipart: logo, or remove_logo=1). */
    updateLogo: (eventId: Id, form: FormData) => api.post<BallotEvent>(`/events/${eventId}/logo`, form),
    deleteEvent: (eventId: Id) => api.delete(`/events/${eventId}`),
    /** Copies the ballot (no voters, votes or dates) into a new event. */
    duplicateEvent: (eventId: Id) => api.post<BallotEvent>(`/events/${eventId}/duplicate`),
    getVotingLink: (eventId: Id) =>
        api.get<{ token: string; public_url: string; results_url: string }>(`/events/${eventId}/voting-link`),
    /** Organiser view of the tally, whatever the public visibility setting. */
    getResults: (eventId: Id) => api.get<EventResults>(`/events/${eventId}/results`),
    getActivity: (eventId: Id) => api.get<AuditEntry[]>(`/events/${eventId}/activity`),

    // Nominations and certified results
    getNominations: (eventId: Id) => api.get<NominationSummary>(`/events/${eventId}/nominations`),
    promoteNominee: (eventId: Id, data: { category_id: number; name: string }) => api.post(`/events/${eventId}/nominations/promote`, data),
    setNomineeSuggestions: (eventId: Id, data: { category_id: number; names: string[] }) =>
        api.put<{ category_id: number; suggestions: string[] }>(`/events/${eventId}/nominations/suggestions`, data),
    getCertificates: (eventId: Id) => api.get<ResultCertificateInfo[]>(`/events/${eventId}/certificates`),
    issueCertificate: (eventId: Id) => api.post<ResultCertificateInfo>(`/events/${eventId}/certificates`),

    // Team (co-organisers)
    getTeam: (eventId: Id) => api.get<TeamInfo>(`/events/${eventId}/members`),
    addMember: (eventId: Id, data: { email: string; role: MemberRole }) => api.post<TeamMember>(`/events/${eventId}/members`, data),
    updateMember: (eventId: Id, memberId: Id, role: MemberRole) => api.patch(`/events/${eventId}/members/${memberId}`, { role }),
    removeMember: (eventId: Id, memberId: Id) => api.delete(`/events/${eventId}/members/${memberId}`),

    // Categories
    getCategories: (eventId: Id) => api.get<Category[]>(`/events/${eventId}/categories`),
    createCategory: (eventId: Id, data: { name: string; max_choices?: number | null; shuffle_candidates?: boolean }) =>
        api.post<Category>(`/events/${eventId}/categories`, data),
    updateCategory: (eventId: Id, categoryId: Id, data: { name?: string; max_choices?: number | null; shuffle_candidates?: boolean }) =>
        api.patch<Category>(`/events/${eventId}/categories/${categoryId}`, data),
    deleteCategory: (eventId: Id, categoryId: Id) => api.delete(`/events/${eventId}/categories/${categoryId}`),

    // Candidates (multipart so a photo can be attached; update uses POST because
    // PHP does not parse multipart bodies on PATCH requests)
    getCandidates: (eventId: Id) => api.get<Candidate[]>(`/events/${eventId}/candidates`),
    createCandidate: (eventId: Id, form: FormData) => api.post<Candidate>(`/events/${eventId}/candidates`, form),
    updateCandidate: (eventId: Id, candidateId: Id, form: FormData) =>
        api.post<Candidate>(`/events/${eventId}/candidates/${candidateId}`, form),
    deleteCandidate: (eventId: Id, candidateId: Id) => api.delete(`/events/${eventId}/candidates/${candidateId}`),
    importCandidates: (eventId: Id, candidates: CandidateImportRow[]) =>
        api.post<CandidateImportResult>(`/events/${eventId}/candidates/import`, { candidates }),

    // Voters
    getVoters: (eventId: Id) => api.get<Voter[]>(`/events/${eventId}/voters`),
    /** Code is generated by the server when omitted. */
    createVoter: (eventId: Id, data: VoterContact & { code?: string | null }) =>
        api.post<Voter>(`/events/${eventId}/voters`, data),
    eraseVoterContacts: (eventId: Id) => api.post<{ erased: number }>(`/events/${eventId}/voters/erase-contacts`),
    updateVoter: (eventId: Id, voterId: Id, data: VoterContact & { code?: string }) =>
        api.patch<Voter>(`/events/${eventId}/voters/${voterId}`, data),
    deleteVoter: (eventId: Id, voterId: Id) => api.delete(`/events/${eventId}/voters/${voterId}`),
    /** Delete several voters at once (votes already cast stay counted). */
    deleteVoters: (eventId: Id, ids: Id[]) => api.post<{ deleted: number; voted: number }>(`/events/${eventId}/voters/bulk-delete`, { ids }),
    /** Test runs: erase every vote and result; voters and codes stay (owner only, never once closed or certified). */
    resetVotes: (eventId: Id, confirmName: string) => api.post<{ erased: number; message: string }>(`/events/${eventId}/reset-votes`, { confirm_name: confirmName }),
    generateVoters: (eventId: Id, data: { count: number }) =>
        api.post<{ voters: GeneratedCode[] }>(`/events/${eventId}/voters/generate`, data),
    importVoters: (eventId: Id, data: { voters: VoterContact[] }) =>
        api.post<{ voters: GeneratedCode[] }>(`/events/${eventId}/voters/import`, data),

    // Invitations (email; WhatsApp is shared from the browser)
    inviteVoters: (eventId: Id, data: { channel: 'email'; audience: 'not_invited' | 'pending' | 'selected'; voter_ids?: number[] }) =>
        api.post<{ queued: number; email: number; queue: string }>(`/events/${eventId}/voters/invite`, data),
    /** Remind everyone who hasn't voted. */
    remindVoters: (eventId: Id) =>
        api.post<{ queued: number; email: number; queue: string }>(`/events/${eventId}/voters/remind`, { channel: 'email' }),
    /** The organiser shared this code themselves (WhatsApp). */
    markShared: (eventId: Id, voterId: Id) => api.post<Voter>(`/events/${eventId}/voters/${voterId}/shared`, { channel: 'whatsapp' }),
    messagingStatus: () => api.get<MessagingStatus>('/messaging/status'),
};

/** Platform-owner area (only for accounts with is_admin). */
export const platformApi = {
    overview: () => api.get<PlatformOverview>('/admin/overview'),
    /** filter: all | awaiting | approved | suspended | unverified | admins */
    users: (params: { search?: string; filter?: string; page?: number }) =>
        api.get<Paginated<PlatformUser>>('/admin/users', { params }),
    /** Approve a new account (it can then create events), or withdraw the approval. */
    setApproval: (userId: Id, approved: boolean) => api.post<PlatformUser>(`/admin/users/${userId}/approval`, { approved }),
    suspend: (userId: Id) => api.post<PlatformUser>(`/admin/users/${userId}/suspend`),
    unsuspend: (userId: Id) => api.post<PlatformUser>(`/admin/users/${userId}/unsuspend`),
    events: (params: { search?: string; status?: string; page?: number }) =>
        api.get<Paginated<PlatformEvent>>('/admin/events', { params }),
    /** Account log: group admin | signin | security; failed = failed sign-ins only. */
    logs: (params: { search?: string; group?: string; failed?: boolean; page?: number }) =>
        api.get<Paginated<AccountLogEntry>>('/admin/logs', { params: { ...params, failed: params.failed ? 1 : undefined } }),
    health: () => api.get<SystemHealth>('/admin/health'),
    /** uuid 'all' = every failed job. */
    retryFailedJob: (uuid: string) => api.post(`/admin/health/failed-jobs/${uuid}/retry`),
    forgetFailedJob: (uuid: string) => api.delete(`/admin/health/failed-jobs/${uuid}`),
};
