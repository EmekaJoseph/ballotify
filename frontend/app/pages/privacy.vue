<template>
    <LegalPage title="Privacy Policy" updated="20 November 2026">
        <p>
            This policy explains what personal data {{ company }} (“we”) processes when organisers run votes on Ballotify and
            when people vote, nominate or pay to vote. It is written with the Nigeria Data Protection Act 2023 (NDPA) and the
            EU/UK GDPR in mind.
        </p>

        <h2>Who is responsible</h2>
        <p>
            For <b>organiser accounts</b> and for <b>payments</b>, we are the data controller. For <b>voter data</b> that an
            organiser uploads (names, emails, phone numbers, member IDs), the organiser is the controller and we process it on
            their behalf, only to run their vote.
        </p>

        <h2>What we collect</h2>
        <ul>
            <li><b>Organisers and co-organisers:</b> name, email address, password (stored only as a secure hash), optional two-step login settings, and, for paid voting, payout bank details (bank, account number and account name).</li>
            <li><b>Voters:</b> whatever the organiser provides (typically a name, email address, phone number and/or member ID) plus a one-time voting code.</li>
            <li><b>People who pay to vote:</b> email address, optional name, the nominee and number of votes, and the payment reference. Card details are handled by our payment providers (Paystack, or Flutterwave as a backup) and never reach us.</li>
            <li><b>Nominators:</b> the nomination, an optional reason and an optional email address.</li>
            <li><b>Candidates</b> who use their profile link: the photo and short description they add.</li>
            <li><b>Technical data:</b> IP addresses are used briefly for rate limiting and security (stored only as a one-way hash for nominations), and kept in server logs for a short period. Our bot check (Cloudflare Turnstile) processes browser signals to tell people from bots.</li>
        </ul>

        <h2>How votes are kept secret</h2>
        <p>
            For code-based votes, Ballotify records only that the code has been used and adds one to each chosen candidate’s
            total. We do not store which candidates a voter chose, so nobody (not the organiser, not us, not anyone with access
            to the database) can see how an individual voted. Ballot receipts are stored only as a hash and are not linked to
            the voter or their choices. USSD votes follow the same rules; we do not keep the phone number used to vote.
            Paid public votes are not secret: the organiser can see the nominee, number of votes and a masked email for each payment.
        </p>

        <h2>Why we use the data</h2>
        <ul>
            <li>To run elections, awards and polls: issuing codes, delivering them by email, SMS or WhatsApp, sending reminders, and counting votes.</li>
            <li>To process payments and pay organisers their share.</li>
            <li>To secure the service: preventing fraud, card testing, brute-force attempts and abuse.</li>
            <li>To contact organisers about their account and payments (receipts, password resets, email confirmation).</li>
        </ul>
        <p>We do not sell personal data, and we do not use voter, nominator or payer data for marketing.</p>

        <h2>Sharing</h2>
        <p>
            We share data only with providers needed to run Ballotify: hosting; email delivery; SMS and USSD providers (such as
            Termii, Africa’s Talking or Twilio); Paystack (and Flutterwave as a backup) for payments, Paystack for payouts; and Cloudflare for bot protection. They
            process it under contract and only for these purposes. We may disclose data where the law requires it.
        </p>

        <h2>How long we keep it</h2>
        <ul>
            <li>Voter email addresses and phone numbers are <b>erased automatically</b> a set number of days after voting closes (30 days by default). Organisers can also erase them at any time.</li>
            <li>Deleting an event removes its voter list, nominations and results.</li>
            <li>Payment records are kept for as long as tax and accounting laws require, even after an event or account is deleted.</li>
            <li>Organiser accounts are kept until the account is deleted.</li>
        </ul>

        <h2>Your rights</h2>
        <p>
            You can ask to access, correct or delete your personal data, object to or restrict its processing, and receive a copy
            of it. Organisers can <b>download their data</b> and <b>delete their account</b> from their account page. Voters should
            first contact the organiser of the vote, who controls their data. You may also complain to the Nigeria Data
            Protection Commission (NDPC) or your local data protection authority.
        </p>

        <h2>Security</h2>
        <p>
            Data is encrypted in transit, passwords are hashed, two-step login is available, results certificates are signed,
            and access to each event is limited to its owner and the co-organisers they invite, according to their role.
        </p>

        <h2>Changes</h2>
        <p>We will update this page if our practices change and show the date of the latest version above.</p>
    </LegalPage>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'public' });
useHead({ title: 'Privacy Policy · Ballotify' });
const company = computed(() => String(useRuntimeConfig().public.companyName || 'Ballotify'));
</script>
