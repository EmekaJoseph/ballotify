<template>
    <LegalPage title="Privacy Policy" updated="18 November 2026">
        <p>
            This policy explains what personal data {{ company }} (“we”) processes when organisers run votes on Ballotify and
            when voters take part. It is written with the Nigeria Data Protection Act 2023 (NDPA) and the EU/UK GDPR in mind.
        </p>

        <h2>Who is responsible</h2>
        <p>
            For <b>organiser accounts</b>, we are the data controller. For <b>voter data</b> that an organiser uploads (names,
            emails, phone numbers), the organiser is the controller and we process it on their behalf, only to run their vote.
        </p>

        <h2>What we collect</h2>
        <ul>
            <li><b>Organisers:</b> name, email address, password (stored only as a secure hash), and optional two-step login settings.</li>
            <li><b>Voters:</b> whatever the organiser provides: typically a name, email address and/or phone number, plus a one-time voting code.</li>
            <li><b>Technical data:</b> IP addresses are used briefly for rate limiting and security, and kept in server logs for a short period.</li>
        </ul>

        <h2>How votes are kept secret</h2>
        <p>
            Ballotify uses a secret ballot. When a vote is cast we record only that the voting code has been used, and add
            one to each chosen candidate’s total. We do not store which candidates a voter chose, so nobody (not the
            organiser, not us, not anyone with access to the database) can see how an individual voted.
        </p>

        <h2>Why we use the data</h2>
        <ul>
            <li>To run elections, awards and polls: issuing codes, delivering them by email or SMS, and counting votes.</li>
            <li>To secure the service: preventing fraud, brute-force attempts and abuse.</li>
            <li>To contact organisers about their account (e.g. password resets, email confirmation).</li>
        </ul>
        <p>We do not sell personal data, and we do not use voter data for marketing.</p>

        <h2>Sharing</h2>
        <p>
            We share data only with service providers needed to run Ballotify: hosting, email delivery and SMS providers
            (such as Termii, Africa’s Talking or Twilio). They process it under contract and only on our instructions.
        </p>

        <h2>How long we keep it</h2>
        <ul>
            <li>Voter email addresses and phone numbers are <b>erased automatically</b> a set number of days after voting closes (30 days by default). Organisers can also erase them at any time.</li>
            <li>Organisers can delete an event, which removes its voter list and results.</li>
            <li>Organiser accounts are kept until the account is closed.</li>
        </ul>

        <h2>Your rights</h2>
        <p>
            You can ask to access, correct or delete your personal data, object to or restrict its processing, and receive a copy
            of it. Voters should first contact the organiser of the vote, who controls their data. You may also complain to the
            Nigeria Data Protection Commission (NDPC) or your local data protection authority.
        </p>

        <h2>Security</h2>
        <p>
            Data is encrypted in transit, passwords are hashed, two-step login is available to organisers, and access to each
            event is limited to the organiser who created it.
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
