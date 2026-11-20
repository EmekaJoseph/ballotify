<template>
    <LegalPage title="Terms of Service" updated="20 November 2026">
        <p>
            These terms apply to anyone who creates an account on Ballotify (“organisers”), to their co-organisers, and to
            anyone who votes, nominates or pays to vote using it. By using Ballotify you agree to them.
        </p>

        <h2>The service</h2>
        <p>
            Ballotify provides tools to create ballots, issue one-time voting codes, collect votes (online, and by USSD where
            available), run paid public voting, collect nominations and publish results. We work to keep the service available
            and accurate, but it is provided “as is”. Organisers remain responsible for how they run their vote and for any
            rules, constitutions or laws that apply to it.
        </p>

        <h2>Accounts and co-organisers</h2>
        <ul>
            <li>Keep your login details secure. We recommend turning on two-step login.</li>
            <li>The event owner is responsible for everything done on the event, including by co-organisers they invite. Only invite people you trust, with the smallest role they need.</li>
            <li>You must be at least 18, or have permission from a parent, guardian or your institution, to create an account.</li>
        </ul>

        <h2>Organiser responsibilities</h2>
        <ul>
            <li>Only upload voter details (names, emails, phone numbers, member IDs) you are allowed to use, and only for running your vote.</li>
            <li>Only send codes and reminders to people who expect them. Spam and unsolicited messages are not allowed.</li>
            <li>If you switch on voter self-registration, make sure the contact details on your list belong to the right people: codes are sent to those details.</li>
            <li>Give voters accurate information about the vote (dates, candidates, prices, how results are published).</li>
            <li>Review nominations and candidate profiles before they appear on your ballot. Remove anything unlawful, hateful or misleading.</li>
        </ul>

        <h2>Plans and payments</h2>
        <ul>
            <li>Free events have the limits shown on our pricing page. Upgrading an event to Pro is a one-off payment for that event; it is not a subscription.</li>
            <li>Payments are processed by Paystack. We never see or store full card details. A receipt is emailed after each payment.</li>
            <li><b>Refunds for Pro upgrades:</b> if you haven’t added voters to the event (or, for paid voting, taken any votes), ask within 7 days of paying and we will refund you. After that, or once the event has been used, upgrades are non-refundable except where the law requires otherwise.</li>
        </ul>

        <h2>Paid public voting</h2>
        <ul>
            <li>Organisers may only use paid voting for lawful contests (for example award nights and pageants), not for public elections, gambling or lotteries, and must follow any rules that apply to them.</li>
            <li>Each vote is a payment for that number of votes for the chosen nominee. Votes are added only after Paystack confirms the payment, and are final once counted.</li>
            <li>Payments that arrive after voting has closed are not counted and are refunded to the payer.</li>
            <li>We keep a platform fee (shown in your dashboard) from each payment. The rest belongs to the organiser. With automatic payouts it is paid to the verified bank account by Paystack; otherwise we pay it by bank transfer after the event.</li>
            <li>We may hold or reverse funds connected to suspected fraud, stolen cards or chargebacks, and deduct refunds and chargebacks from what is owed to the organiser.</li>
        </ul>

        <h2>Acceptable use</h2>
        <p>
            You must not use Ballotify to break the law, to mislead voters, to buy or sell voting codes, to interfere with
            another organiser’s vote, to test stolen payment cards, or to attempt to guess codes or overload the service. We use
            automated checks against bots and may suspend accounts or events that break these terms.
        </p>

        <h2>Integrity of votes</h2>
        <p>
            Code-based votes are secret: Ballotify does not record how any individual voted. Once a code has been used the vote
            cannot be changed or withdrawn. Voters receive a receipt that shows their ballot was counted without revealing their
            choices. After the first vote the ballot structure is locked, and later edits are recorded in the event’s activity log.
            Certified results can be checked by anyone on the verification page.
        </p>

        <h2>Your content</h2>
        <p>
            You keep ownership of the content you, your co-organisers and your candidates add (event details, names, photos,
            descriptions, logos). You give us permission to store and display it as needed to run your vote.
        </p>

        <h2>Availability and liability</h2>
        <p>
            Services we rely on (internet, SMS, USSD and payment networks) can fail. To the extent permitted by law, we are not
            liable for indirect or consequential losses, or for the outcome of any vote run on the service. Nothing in these
            terms limits liability that cannot legally be limited.
        </p>

        <h2>Ending your use</h2>
        <p>
            You can delete events at any time, and delete your account from your account page (once any money owed to you
            from paid voting has been settled). We may suspend accounts that break these terms. We may update these terms; we
            will show the date of the latest version above, and continued use means you accept it.
        </p>
    </LegalPage>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'public' });
useHead({ title: 'Terms of Service · Ballotify' });
</script>
