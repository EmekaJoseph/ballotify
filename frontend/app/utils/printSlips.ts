import QRCode from 'qrcode';
import type { GeneratedCode } from '~/types';

const escapeHtml = (s: string) =>
    s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]!));

/**
 * Opens a print-ready sheet of voter slips (code + voting link + QR), 10 per A4 page.
 */
export async function printVoterSlips(eventName: string, voteUrl: string, codes: GeneratedCode[]) {
    const win = window.open('', '_blank');
    if (!win) {
        useToast().error('Allow pop-ups for this site to print voter slips.');
        return;
    }
    win.document.write('<p style="font-family:sans-serif;padding:2rem">Preparing slips…</p>');

    const qr = await QRCode.toDataURL(voteUrl, { margin: 0, width: 220, color: { dark: '#0a1222', light: '#ffffff' } });
    const slips = codes.map(c => `
        <div class="slip">
            <img src="${qr}" alt="">
            <div class="info">
                <div class="ev">${escapeHtml(eventName)}</div>
                ${c.name ? `<div class="name">${escapeHtml(c.name)}</div>` : ''}
                <div class="label">Your voting code</div>
                <div class="code">${escapeHtml(c.code)}</div>
                <div class="url">${escapeHtml(voteUrl)}</div>
            </div>
        </div>`).join('');

    win.document.open();
    win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Voter slips · ${escapeHtml(eventName)}</title>
<style>
@page { size: A4; margin: 10mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; color: #0f172a; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.slip { display: flex; gap: 12px; align-items: center; padding: 12px; height: 54mm; border: 1px dashed #cbd5e1; page-break-inside: avoid; }
.slip img { width: 26mm; height: 26mm; flex-shrink: 0; }
.ev { font-weight: 700; font-size: 11pt; margin-bottom: 2px; }
.name { font-size: 9.5pt; color: #475569; margin-bottom: 6px; }
.label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
.code { font-family: Consolas, 'Courier New', monospace; font-size: 18pt; font-weight: 700; letter-spacing: .12em; color: #0e7c77; }
.url { font-size: 7.5pt; color: #64748b; word-break: break-all; margin-top: 4px; }
</style></head><body><div class="grid">${slips}</div>
<script>window.onload = function () { setTimeout(function () { window.print(); }, 250); };<\/script>
</body></html>`);
    win.document.close();
}
