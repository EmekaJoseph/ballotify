<template>
    <LegalPage title="Terms of Service" updated="18 November 2026">
        <p>These terms apply to anyone who creates an account on Ballotify (“organisers”) and to anyone who votes using it.</p>

        <h2>The service</h2>
        <p>
            Ballotify provides tools to create ballots, issue one-time voting codes, collect votes and publish results.
            We work to keep the service available and accurate, but it is provided “as is”. Organisers remain responsible
            for how they run their vote and for any rules or laws that apply to it.
        </p>

        <h2>Organiser responsibilities</h2>
        <ul>
            <li>Keep your login details secure. We recommend turning on two-step login.</li>
            <li>Only upload voter details you are allowed to use, and only for running your vote.</li>
            <li>Only send voting codes to people who expect to receive them. Spam and unsolicited messages are not allowed.</li>
            <li>Give voters accurate information about the vote (dates, candidates, how results are published).</li>
        </ul>

        <h2>Acceptable use</h2>
        <p>
            You must not use Ballotify to break the law, to mislead voters, to interfere with another organiser’s vote, or to
            attempt to guess voting codes or overload the service. We may suspend accounts that do.
        </p>

        <h2>Integrity of votes</h2>
        <p>
            Votes are secret: Ballotify does not record how any individual voted. Once a code has been used, the vote cannot
            be changed or withdrawn. After the first vote is cast, the structure of the ballot is locked, and later edits are
            recorded in the event’s activity log.
        </p>

        <h2>Your content</h2>
        <p>
            You keep ownership of the content you add (event details, candidate names and photos). You give us permission to
            store and display it as needed to run your vote.
        </p>

        <h2>Liability</h2>
        <p>
            To the extent permitted by law, we are not liable for indirect or consequential losses, or for the outcome of any
            vote run on the service. Nothing in these terms limits liability that cannot legally be limited.
        </p>

        <h2>Ending your use</h2>
        <p>You can stop using Ballotify and delete your events at any time. We may update these terms; continued use means you accept the latest version.</p>
    </LegalPage>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'public' });
useHead({ title: 'Terms of Service · Ballotify' });
</script>
