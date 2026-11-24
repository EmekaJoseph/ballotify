/**
 * The organiser help centre (/help). Plain data, so guides are easy to edit:
 * each article has short steps and optional tips. Keep the wording simple:
 * many readers are first-time election organisers on a phone.
 */
export interface HelpArticle {
    id: string;
    section: string;
    title: string;
    summary: string;
    steps?: string[];
    tips?: string[];
}

export const HELP_SECTIONS = [
    { key: 'start', label: 'Getting started', icon: 'bi-rocket-takeoff' },
    { key: 'voters', label: 'Voters and codes', icon: 'bi-people' },
    { key: 'results', label: 'Results and records', icon: 'bi-bar-chart-line' },
    { key: 'paid', label: 'Award nights and paid voting', icon: 'bi-trophy' },
    { key: 'more', label: 'More features', icon: 'bi-stars' },
    { key: 'account', label: 'Plans and your account', icon: 'bi-person-gear' },
    { key: 'trouble', label: 'Problems and questions', icon: 'bi-life-preserver' },
] as const;

export const QUICK_START: { title: string; text: string; article: string }[] = [
    { title: 'Create an event', text: 'Pick a template (church, AGM, board, school, award night) or start blank.', article: 'create-event' },
    { title: 'Build the ballot', text: 'Add categories and candidates, or motions for Yes / No / Abstain.', article: 'build-ballot' },
    { title: 'Add your voters', text: 'Import a spreadsheet (download our template) or add people one by one.', article: 'add-voters' },
    { title: 'Send the codes', text: 'By email, SMS or WhatsApp, or print voter slips.', article: 'send-codes' },
    { title: 'Watch it live', text: 'Turnout and results update as people vote. Present them on a projector.', article: 'during-voting' },
    { title: 'Share the record', text: 'Download the PDF report and certify the results when voting closes.', article: 'after-voting' },
];

export const HELP_ARTICLES: HelpArticle[] = [
    // ------------------------------------------------------------ getting started
    {
        id: 'create-event', section: 'start', title: 'Create your first event',
        summary: 'An event is one election, AGM vote, poll or award night.',
        steps: [
            'Sign up and confirm your email address (check your inbox, and the spam folder).',
            'Click New event. Choose a template, or Blank to start from scratch.',
            'Give it a name, e.g. “2026 Annual General Meeting”.',
            'Choose how people vote: Secret ballot with codes (elections, AGMs) or Paid public voting (award nights).',
            'Choose who sees the results: live as votes come in, after voting closes, or only you.',
            'Set when voting opens and closes. You can leave them empty and open or close it yourself.',
        ],
        tips: ['Drafts are free and unlimited. An event only counts towards your plan once it has voters.'],
    },
    {
        id: 'build-ballot', section: 'start', title: 'Build the ballot',
        summary: 'Categories (positions), candidates, voting methods and motions.',
        steps: [
            'Open the event and go to the Ballot tab.',
            'Type a category, e.g. “President”, and click Add category.',
            'Click Candidate to add people: a name, an optional photo and a short description.',
            'Many candidates? Click Import candidates and use the Download template button.',
            'Choose each category’s voting method: Choose one, Pick up to N, Approval (tick everyone you approve of) or Ranked choice.',
            'For a Yes / No decision, click Add motion, write the motion and choose the majority it needs.',
        ],
        tips: [
            'Once the first vote is cast, the ballot locks: you can still fix spelling and photos, and every change is logged.',
            'The shuffle button shows candidates in a random order on each ballot, so nobody benefits from being first.',
        ],
    },
    {
        id: 'add-voters', section: 'voters', title: 'Add your voters',
        summary: 'Each voter gets a one-time code. Only people on your list can vote.',
        steps: [
            'Open the event and go to the Voters tab.',
            'Click Import, then Download template. Fill in names, and email and/or phone numbers.',
            'Optional columns: a Member ID (for self-registration) and a Group (e.g. a branch or unit).',
            'Save the sheet as CSV and click Load a .csv file. Check the preview, then import.',
            'No contact details? Click Generate codes and hand out printed slips instead.',
        ],
        tips: ['Phone numbers like 0801 234 5678 are converted to international format automatically.'],
    },
    {
        id: 'send-codes', section: 'voters', title: 'Send voting codes',
        summary: 'Email, SMS, WhatsApp or printed slips.',
        steps: [
            'On the Voters tab, select voters (or all) and click Send codes.',
            'Choose email, SMS or both. Each voter gets their own code and the voting link.',
            'Prefer WhatsApp? Click the WhatsApp button on the Voters tab to send each voter their code from your phone (free).',
            'Use Remind to message only the people who haven’t voted yet.',
            'For in-person voting, click Generate codes, then Print slips.',
        ],
        tips: [
            'Sending by email or SMS needs a confirmed email address and a Pro event.',
            'A voter who already voted is never messaged again.',
        ],
    },
    {
        id: 'voter-groups', section: 'voters', title: 'Categories only some voters see (voter groups)',
        summary: 'E.g. only the Lagos branch votes for the Lagos Branch Rep.',
        steps: [
            'Give voters groups: a Group column when importing, or the Groups field when editing a voter.',
            'On the Ballot tab, click “Everyone” under a category name.',
            'Choose Only some groups and tick the groups allowed.',
            'Voters only see the categories for their groups, on the web and on USSD.',
        ],
        tips: ['A voter can be in several groups, e.g. “Lagos; Youth”.'],
    },
    {
        id: 'self-registration', section: 'voters', title: 'Let voters get their own code (self-registration)',
        summary: 'Voters enter their membership number; the code goes to the contact you have on file.',
        steps: [
            'Make sure your voter list has a Member ID for each person.',
            'Go to Settings → Voting options and switch on Voter self-registration.',
            'Set what you call the ID, e.g. “Membership number”.',
            'Voters see “Don’t have your code?” on the voting page.',
        ],
        tips: ['The code is only sent to the email or phone you imported, so knowing someone’s ID isn’t enough to vote as them.'],
    },
    // ------------------------------------------------------------ during and after
    {
        id: 'during-voting', section: 'results', title: 'During voting',
        summary: 'Turnout, live results and presenting on a screen.',
        steps: [
            'The Overview tab shows turnout and votes over time.',
            'The Results tab shows the count as it happens (only you see it if results are private).',
            'Click Present to show results full screen on a projector.',
            'For award nights, click Reveal to show one category at a time and reveal the winner on a click.',
        ],
        tips: ['A ballot left untouched on a shared phone closes itself after about a minute, so the next person can’t use it.'],
    },
    {
        id: 'after-voting', section: 'results', title: 'After voting: report, certificate and export',
        summary: 'A complete, printable record of the vote.',
        steps: [
            'Go to the Results tab and click the report icon (Printable report / PDF).',
            'The report shows winners, votes, percentages, turnout, and (optionally) who voted and when. Never how anyone voted.',
            'In the print window, choose Save as PDF.',
            'Click Certify results to get a signed certificate with a QR code anyone can scan to check it.',
            'CSV gives you the full count as a spreadsheet.',
        ],
    },
    {
        id: 'ranked-motions', section: 'results', title: 'Understanding ranked choice and motions',
        summary: 'How winners are decided for the special voting methods.',
        steps: [
            'Ranked choice: if nobody has more than half of the first choices, the last candidate is eliminated and their ballots move to each voter’s next choice. This repeats until someone has more than half.',
            'The results show “How the count went”, round by round.',
            'Motions pass by the majority you chose (simple, two-thirds or three-quarters) of the Yes and No votes. Abstentions don’t count towards it.',
            'Approval voting shows the share of voters who picked each candidate, so the percentages can add up to more than 100%.',
        ],
    },
    // ------------------------------------------------------------ paid voting
    {
        id: 'paid-voting', section: 'paid', title: 'Run an award night with paid voting',
        summary: 'Fans pay per vote. Votes count once the payment is confirmed.',
        steps: [
            'Create the event with Paid public voting and set the price per vote.',
            'Add categories and nominees with photos.',
            'Share the voting link. There are no codes: anyone can vote as many times as they pay for.',
            'Fans pay by card, bank transfer or USSD, and get a receipt by email.',
            'Use the Earnings tab to follow the money.',
        ],
        tips: ['Payments that arrive after voting closes are not counted and are marked for refund.'],
    },
    {
        id: 'payouts', section: 'paid', title: 'Getting paid',
        summary: 'Your share of the vote payments.',
        steps: [
            'Go to the event’s Earnings tab.',
            'Enter your bank and account number. We check the account name with the bank.',
            'Automatic payouts: your share is sent to your account after each payment (usually the next working day).',
            'The platform fee is shown on the same tab.',
        ],
    },
    {
        id: 'campaign-cards', section: 'paid', title: 'Campaign cards',
        summary: 'A shareable picture for each candidate, with a QR code to the ballot.',
        steps: [
            'On the Ballot tab, click the picture icon next to a candidate.',
            'Choose Square (for posts) or Status / Story (for WhatsApp status).',
            'Download it or share it straight from your phone.',
            'Candidates can make their own from their profile link, and fans from the paid voting page.',
        ],
    },
    // ------------------------------------------------------------ more features
    {
        id: 'ussd', section: 'more', title: 'USSD voting (no smartphone needed)',
        summary: 'Voters dial a short code and vote with their keypad.',
        steps: [
            'Go to Settings → Voting options and switch on USSD voting (Pro).',
            'Voters dial the code shown, enter the event number, then their voting code.',
            'They choose by number, one screen per category, then confirm.',
        ],
        tips: ['New codes on USSD events are numbers only, because keypads can’t type letters.'],
    },
    {
        id: 'nominations', section: 'more', title: 'Collect nominations first',
        summary: 'Let people suggest nominees before voting starts.',
        steps: [
            'Go to Settings → Voting options and switch on Nominations.',
            'Share the nomination link. People suggest names for each category.',
            'On the Nominations tab, add the ones you accept to the ballot.',
        ],
    },
    {
        id: 'team', section: 'more', title: 'Work with a team',
        summary: 'Invite co-organisers with the right access.',
        steps: [
            'Go to the Team tab and invite people by email (Pro).',
            'Editor: ballot, settings, voters and results. Voter manager: the voter list and codes. Viewer: read only.',
            'Only you, the owner, can delete the event or manage the team.',
        ],
    },
    {
        id: 'languages', section: 'more', title: 'Voting pages in other languages',
        summary: 'English, Yorùbá, Hausa, Igbo and French.',
        steps: [
            'Voting pages open in English.',
            'To open them in another language, go to Settings → Voting options → Voting page language.',
            'Voters can always switch with the language button at the top of the page.',
        ],
    },
    {
        id: 'branding', section: 'more', title: 'Your logo and colours',
        summary: 'Make the ballot look like yours (Pro).',
        steps: ['Go to Settings → Branding.', 'Upload your logo and pick your colour.', 'Pro events also hide “Powered by Ballotify”.'],
    },
    // ------------------------------------------------------------ account
    {
        id: 'plans', section: 'account', title: 'Free and Pro',
        summary: 'You pay per event, only for the events that need more.',
        steps: [
            'Free events work for small votes, with limits on voters and features.',
            'Upgrade one event to Pro from its page for more voters, email and SMS codes, branding, USSD, a team and more.',
            'Your receipts are on the Account page.',
        ],
    },
    {
        id: 'two-step', section: 'account', title: 'Protect your account with two-step login',
        summary: 'A code from Google Authenticator when you sign in.',
        steps: [
            'Go to Account and click Set up two-step login.',
            'Scan the QR code with Google Authenticator and type the 6-digit code.',
            'Save your recovery codes somewhere safe: they get you in if you lose your phone.',
        ],
    },
    // ------------------------------------------------------------ troubleshooting
    {
        id: 'no-code', section: 'trouble', title: 'A voter didn’t get their code',
        summary: 'Check, resend, or share it another way.',
        steps: [
            'On the Voters tab, check the person’s email or phone number is correct.',
            'Filter by “Delivery failed” to see messages that bounced, and why.',
            'Ask them to check their spam folder, then resend.',
            'Or send it with the WhatsApp button on the Voters tab, or give them the code in person.',
        ],
    },
    {
        id: 'code-used', section: 'trouble', title: '“This code has already been used”',
        summary: 'Each code votes once. Because the ballot is secret, a used code can’t be reset.',
        steps: [
            'Check the voter typed the right code (O and 0, I and 1 are easy to mix up).',
            'If someone else used their code, you can’t undo that vote: nobody can tell which vote it was.',
            'Give the person a new code only if your rules allow it, and note it in your records.',
        ],
    },
    {
        id: 'change-ballot', section: 'trouble', title: 'I need to change the ballot after voting started',
        summary: 'Spelling and photos: yes. Adding or removing candidates: no.',
        steps: [
            'You can fix names, descriptions and photos at any time. Each change is in the Activity log and counted on the results page.',
            'Adding, removing or moving candidates is locked, so nobody can change the race once votes are in.',
            'If something big is wrong, close the event, duplicate it (Settings → Advanced), fix it and run it again.',
        ],
    },
    {
        id: 'payment-missing', section: 'trouble', title: 'A fan paid but their votes don’t show',
        summary: 'Votes are added once the payment is confirmed.',
        steps: [
            'Most payments confirm within a minute; a few take longer.',
            'The fan can enter their payment reference on the results page to check its status.',
            'Payments made after voting closed are not counted and are marked for refund on the Earnings tab.',
        ],
    },
    {
        id: 'secret', section: 'trouble', title: 'Can anyone see how I voted?',
        summary: 'No. Not the organiser, not us.',
        steps: [
            'Ballotify only keeps a running total for each candidate. Nothing links a vote to a voter or a code.',
            'The organiser can see who has voted (to send reminders), never what they chose.',
            'Each voter gets a receipt to check their ballot was counted, without it showing their choices.',
        ],
    },
];
