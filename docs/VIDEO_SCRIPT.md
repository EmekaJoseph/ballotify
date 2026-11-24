# Ballotify: 3-minute organiser tour (video script)

**Goal:** a first-time organiser (church secretary, association chair, school union officer, award organiser) watches this once and can run their first vote.

**Format:** screen recording of the dashboard with a voice-over, plus a phone for the voter's view. About 3 minutes, 1080p. Add captions: many people watch with the sound off.

**Before recording:**
- Use a fresh demo account and the demo data (`php artisan migrate:fresh --seed`).
- Light mode, browser zoom 110%, notifications off.
- Use generic names: "Annual Elections 2026", "Lagos Branch Rep".

When it's uploaded, put the link in the frontend `.env` as `NUXT_PUBLIC_HELP_VIDEO_URL`. It then appears at the top of the Help centre.

---

| # | Time | On screen | Voice-over |
|---|---|---|---|
| 1 | 0:00 – 0:12 | Landing page, then the phone showing a ballot | "Ballotify runs secret-ballot elections, AGM votes and award nights online, with one-time codes and live results. Here's your first vote in three minutes." |
| 2 | 0:12 – 0:35 | **New event** → choose the **Church / ministry** or **AGM** template → name it → dates | "Click New event and pick a template, or start blank. Give it a name, choose whether results show live or after voting closes, and set when voting opens and closes." |
| 3 | 0:35 – 1:05 | **Ballot** tab: add a candidate with a photo; open a category's method menu (Choose one / Approval / Ranked choice); click **Add motion** | "On the Ballot tab, add your positions and candidates with photos. Each position can be choose-one, pick several, or ranked choice. For decisions, add a motion: members vote Yes, No or Abstain, and you choose the majority it needs." |
| 4 | 1:05 – 1:35 | **Voters** tab → **Import** → **Download template** → load the filled CSV → preview → import | "Next, your voters. Download our template, fill in names with an email or phone number, and load it back. Add a group, like a branch, to show some positions only to the right people." |
| 5 | 1:35 – 1:55 | **Send codes** (email/SMS) and the **WhatsApp** button; show **Print slips** briefly | "Send each person their own code by email, SMS or WhatsApp, or print voter slips for in-person voting. Each code works once." |
| 6 | 1:55 – 2:20 | Phone: open the link → enter the code → pick candidates → review → submit → receipt. Show the language button. | "Voters open the link, enter their code, choose and submit. It works on any phone, in English, Yorùbá, Hausa, Igbo or French, and even by USSD without data. Their choices are secret: nobody can see how anyone voted." |
| 7 | 2:20 – 2:40 | **Results** tab updating; click **Present** | "Turnout and results update live. Click Present to show them on a projector at your meeting." |
| 8 | 2:40 – 2:55 | Report icon → PDF; **Certify results** → QR code | "When voting closes, download the full report as a PDF and certify the results. Anyone can scan the QR code to check they're genuine." |
| 9 | 2:55 – 3:05 | Help centre page with the WhatsApp button | "Stuck? Open Help, or chat with us on WhatsApp. We're happy to help you set up." |

---

## Shorter cuts (for WhatsApp status and Instagram)

Use the same footage, cut to 30 seconds each, in portrait:
1. **"Run your AGM vote online":** steps 2, 3 (the motion part), 6 and 8.
2. **"Church elections without paper":** steps 2, 4, 6 and 7.
3. **"Award night voting that pays":** a paid event, the nominee page, paying, the live leaderboard and the campaign card.
