import api from '../index';
import type {
    AuditEntry, BallotEvent, Candidate, CandidateImportResult, CandidateImportRow, Category, Earnings, EventResults, EventTemplate,
    GeneratedCode, InviteChannel, MemberRole, MessagingStatus, MyPayment, Paginated, PaymentReceiptInfo, PlanInfo, PlanSettings,
    PlatformEvent, PlatformOverview, PlatformPayment, PlatformPayout, PlatformUser, ResultsVisibility, SystemHealth, TeamInfo,
    TeamMember, User, Voter, VoterContact, VotingMode, NominationSummary, ResultCertificateInfo, Bank,
} from '~/types';

type Id = number | string;

export interface EventPayload {
    language?: string;
    name?: string;
    description?: string | null;
    results_visibility?: ResultsVisibility;
    voting_mode?: VotingMode;
    vote_price_naira?: number | null;
    starts_at?: string | null;
    ends_at?: string | null;
    reminder_at?: string | null;
    reminder_channel?: InviteChannel | null;
    /** Create only: start from a template. */
    template?: string | null;
    self_registration?: boolean;
    member_id_label?: string | null;
    ussd_enabled?: boolean;
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
    updateMe: (data: {
        name?: string; current_password?: string; password?: string;
        payout_bank_name?: string | null; payout_account_number?: string | null; payout_account_name?: string | null;
    }) => api.patch<User>('/me', data),
    /** First-run tour seen or skipped. */
    markOnboarded: () => api.post<User>('/me/onboarded'),
    /** Everything we hold about the account, as JSON. */
    exportMyData: () => api.get<Record<string, unknown>>('/me/export'),
    deleteAccount: (password: string) => api.delete<{ message: string }>('/me', { data: { password } }),
    // Payout account (automatic payouts via Paystack)
    banks: () => api.get<{ banks: Bank[]; verified: boolean }>('/billing/banks'),
    resolveAccount: (data: { bank_code: string; account_number: string }) => api.post<{ account_name: string }>('/billing/resolve-account', data),
    savePayoutAccount: (data: { bank_code: string; account_number: string }) => api.put<User>('/me/payout-account', data),

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
    deleteEvent: (eventId: Id) => api.delete(`/events/${eventId}`),
    /** Copies the ballot (no voters, votes or dates) into a new event. */
    duplicateEvent: (eventId: Id) => api.post<BallotEvent>(`/events/${eventId}/duplicate`),
    getVotingLink: (eventId: Id) =>
        api.get<{ token: string; public_url: string; results_url: string }>(`/events/${eventId}/voting-link`),
    /** Organiser view of the tally, whatever the public visibility setting. */
    getResults: (eventId: Id) => api.get<EventResults>(`/events/${eventId}/results`),
    getActivity: (eventId: Id) => api.get<AuditEntry[]>(`/events/${eventId}/activity`),
    /** Paid voting: money raised, fee, payouts. */
    getEarnings: (eventId: Id) => api.get<Earnings>(`/events/${eventId}/earnings`),
    /** Pro: brand colour and logo (multipart: brand_color, logo, remove_logo). */
    updateBranding: (eventId: Id, form: FormData) => api.post<BallotEvent>(`/events/${eventId}/branding`, form),

    // Nominations and certified results
    getNominations: (eventId: Id) => api.get<NominationSummary>(`/events/${eventId}/nominations`),
    promoteNominee: (eventId: Id, data: { category_id: number; name: string }) => api.post(`/events/${eventId}/nominations/promote`, data),
    getCertificates: (eventId: Id) => api.get<ResultCertificateInfo[]>(`/events/${eventId}/certificates`),
    issueCertificate: (eventId: Id) => api.post<ResultCertificateInfo>(`/events/${eventId}/certificates`),
    /** Private link for a candidate to edit their own photo and description. */
    candidateProfileLink: (eventId: Id, candidateId: Id) => api.get<{ url: string }>(`/events/${eventId}/candidates/${candidateId}/profile-link`),
    revokeCandidateProfileLink: (eventId: Id, candidateId: Id) => api.delete(`/events/${eventId}/candidates/${candidateId}/profile-link`),

    // Team (co-organisers)
    getTeam: (eventId: Id) => api.get<TeamInfo>(`/events/${eventId}/members`),
    addMember: (eventId: Id, data: { email: string; role: MemberRole }) => api.post<TeamMember>(`/events/${eventId}/members`, data),
    updateMember: (eventId: Id, memberId: Id, role: MemberRole) => api.patch(`/events/${eventId}/members/${memberId}`, { role }),
    removeMember: (eventId: Id, memberId: Id) => api.delete(`/events/${eventId}/members/${memberId}`),

    // Categories
    getCategories: (eventId: Id) => api.get<Category[]>(`/events/${eventId}/categories`),
    createCategory: (eventId: Id, data: { name: string; max_choices?: number | null; shuffle_candidates?: boolean; method?: 'plurality' | 'ranked' }) =>
        api.post<Category>(`/events/${eventId}/categories`, data),
    updateCategory: (eventId: Id, categoryId: Id, data: { name?: string; max_choices?: number | null; shuffle_candidates?: boolean; method?: 'plurality' | 'ranked' }) =>
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
    generateVoters: (eventId: Id, data: { count: number }) =>
        api.post<{ voters: GeneratedCode[] }>(`/events/${eventId}/voters/generate`, data),
    importVoters: (eventId: Id, data: { voters: VoterContact[] }) =>
        api.post<{ voters: GeneratedCode[] }>(`/events/${eventId}/voters/import`, data),

    // Invitations (email / SMS)
    inviteVoters: (eventId: Id, data: { channel: InviteChannel; audience: 'not_invited' | 'pending' | 'selected'; voter_ids?: number[] }) =>
        api.post<{ queued: number; email: number; sms: number; queue: string }>(`/events/${eventId}/voters/invite`, data),
    /** Remind everyone who hasn't voted (Pro). */
    remindVoters: (eventId: Id, channel: InviteChannel) =>
        api.post<{ queued: number; email: number; sms: number; queue: string }>(`/events/${eventId}/voters/remind`, { channel }),
    /** The organiser shared this code themselves (WhatsApp). */
    markShared: (eventId: Id, voterId: Id) => api.post<Voter>(`/events/${eventId}/voters/${voterId}/shared`, { channel: 'whatsapp' }),
    /** USSD: give unused codes numbers only. */
    numericCodes: (eventId: Id) => api.post<{ replaced: number }>(`/events/${eventId}/voters/numeric-codes`),
    messagingStatus: () => api.get<MessagingStatus>('/messaging/status'),
};

/** Platform-owner area (only for accounts with is_admin). */
export const platformApi = {
    overview: () => api.get<PlatformOverview>('/admin/overview'),
    users: (params: { search?: string; filter?: string; page?: number }) =>
        api.get<Paginated<PlatformUser>>('/admin/users', { params }),
    suspend: (userId: Id) => api.post<PlatformUser>(`/admin/users/${userId}/suspend`),
    unsuspend: (userId: Id) => api.post<PlatformUser>(`/admin/users/${userId}/unsuspend`),
    events: (params: { search?: string; status?: string; page?: number }) =>
        api.get<Paginated<PlatformEvent>>('/admin/events', { params }),
    setEventPlan: (eventId: Id, plan: 'free' | 'pro') =>
        api.post<{ id: number; plan: 'free' | 'pro'; upgraded_via: 'payment' | 'admin' | null }>(`/admin/events/${eventId}/plan`, { plan }),
    setAccountPro: (userId: Id, enabled: boolean) => api.post<PlatformUser>(`/admin/users/${userId}/pro`, { enabled }),
    plans: () => api.get<PlanSettings>('/admin/plans'),
    updatePlans: (data: Partial<Omit<PlanSettings, 'billing_enabled' | 'payments_enabled'>>) => api.put<PlanSettings>('/admin/plans', data),
    payments: (params: { search?: string; status?: string; page?: number }) =>
        api.get<Paginated<PlatformPayment>>('/admin/payments', { params }),
    payouts: () => api.get<PlatformPayout[]>('/admin/payouts'),
    recordPayout: (eventId: Id, data: { amount_naira: number; note?: string }) => api.post(`/admin/events/${eventId}/payouts`, data),
    health: () => api.get<SystemHealth>('/admin/health'),
    /** uuid 'all' = every failed job. */
    retryFailedJob: (uuid: string) => api.post(`/admin/health/failed-jobs/${uuid}/retry`),
    forgetFailedJob: (uuid: string) => api.delete(`/admin/health/failed-jobs/${uuid}`),
};

/** Pay per event (Paystack). */
export const billingApi = {
    plans: () => api.get<PlanInfo>('/billing/plans'),
    /** Returns the Paystack checkout URL to send the organiser to. */
    checkout: (eventId: Id) => api.post<{ authorization_url: string; reference: string }>(`/events/${eventId}/upgrade`),
    verify: (reference: string) =>
        api.post<{ status: 'pending' | 'success' | 'failed'; message: string; event_id: number | null }>('/billing/verify', { reference }),
    myPayments: () => api.get<MyPayment[]>('/billing/payments'),
    receipt: (reference: string) => api.get<PaymentReceiptInfo>(`/billing/payments/${encodeURIComponent(reference)}`),
};
