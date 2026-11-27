<template>
    <LegalPage title="Terms of Service" updated="27 November 2026">
        <p>
            These terms apply to anyone who creates an account on E-voting by Agudatechy (“organisers”), to their
            co-organisers, and to anyone who votes or nominates using it. By using the service you agree to them.
        </p>

        <h2>The service</h2>
        <p>
            The service provides tools to create secret ballots, issue one-time voting codes, collect votes online, collect
            nominations and publish results. We work to keep it available and accurate, but it is provided “as is”. Organisers
            remain responsible for how they run their election and for any rules, constitutions or laws that apply to it.
        </p>

        <h2>Accounts and approval</h2>
        <ul>
            <li>New accounts must be approved by Agudatechy before they can create events. We may decline or withdraw approval.</li>
            <li>Keep your login details secure. We recommend turning on two-step login.</li>
            <li>The event owner is responsible for everything done on the event, including by co-organisers they invite. Only invite people you trust, with the smallest role they need.</li>
            <li>You must be at least 18, or have permission from a parent, guardian or your institution, to create an account.</li>
        </ul>

        <h2>Organiser responsibilities</h2>
        <ul>
            <li>Only upload voter details (names, emails, phone numbers, member IDs) you are allowed to use, and only for running your election.</li>
            <li>Only send codes and reminders to people who expect them. Spam and unsolicited messages are not allowed.</li>
            <li>Give voters accurate information about the election (dates, candidates, how results are published).</li>
            <li>Review nominations before they appear on your ballot. Remove anything unlawful, hateful or misleading.</li>
        </ul>

        <h2>Acceptable use</h2>
        <p>
            You must not use the service to break the law, to mislead voters, to buy or sell voting codes, to interfere with
            another organiser’s election, or to attempt to guess codes or overload the service. We use automated checks against
            bots and may suspend accounts or events that break these terms.
        </p>

        <h2>Integrity of votes</h2>
        <p>
            Votes are secret: the service does not record how any individual voted. Once a code has been used the vote cannot
            be changed or withdrawn. Voters receive a receipt that shows their ballot was counted without revealing their
            choices. After the first vote the ballot structure is locked, and later edits are recorded in the event’s activity log.
            Certified results can be checked by anyone on the verification page.
        </p>

        <h2>Your content</h2>
        <p>
            You keep ownership of the content you and your co-organisers add (event details, names, photos, descriptions). You
            give us permission to store and display it as needed to run your election.
        </p>

        <h2>Availability and liability</h2>
        <p>
            Services we rely on (internet and email networks) can fail. To the extent permitted by law, we are not liable for
            indirect or consequential losses, or for the outcome of any election run on the service. Nothing in these terms
            limits liability that cannot legally be limited.
        </p>

        <h2>Ending your use</h2>
        <p>
            You can delete events at any time, and delete your account from your account page. We may suspend accounts that
            break these terms. We may update these terms; we will show the date of the latest version above, and continued use
            means you accept it.
        </p>
    </LegalPage>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'public' });
useHead({ title: 'Terms of Service · E-voting' });
</script>
