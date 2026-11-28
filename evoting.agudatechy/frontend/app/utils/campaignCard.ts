import QRCode from 'qrcode';
import { colorFor, initials } from './format';

/**
 * Draws a candidate's campaign card (an image to share on WhatsApp, Instagram,
 * X...) onto a canvas: photo, name, category, event, and a QR code to the
 * voting page. Square (1080×1080) for posts, story (1080×1920) for statuses.
 */
export interface CampaignCardInput {
    name: string;
    category: string;
    eventName: string;
    /** Photo as a same-origin blob (null = initials). */
    photo: Blob | null;
    voteUrl: string;
    labels: { vote: string; forCategory: string; scan: string };
    format: 'square' | 'story';
}

const FONT = '"Plus Jakarta Sans", "Noto Sans", system-ui, sans-serif';

export async function drawCampaignCard(canvas: HTMLCanvasElement, o: CampaignCardInput): Promise<void> {
    const W = 1080;
    const H = o.format === 'story' ? 1920 : 1080;
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d')!;
    const brand = '#058580';
    try {
        await Promise.all([document.fonts.load(`800 80px ${FONT}`), document.fonts.load(`600 36px ${FONT}`)]);
    } catch { /* the fallback font is fine */ }

    // Background: deep night with brand and violet glows.
    ctx.fillStyle = '#0b1220';
    ctx.fillRect(0, 0, W, H);
    glow(ctx, W * 0.95, H * 0.05, W * 0.8, hexA(brand, 0.55));
    glow(ctx, W * 0.0, H * 0.95, W * 0.9, 'rgba(124, 92, 255, .35)');

    const story = o.format === 'story';
    let y = story ? 170 : 90;

    // Event name
    ctx.textAlign = 'center';
    ctx.fillStyle = 'rgba(255,255,255,.75)';
    ctx.font = `700 ${story ? 40 : 34}px ${FONT}`;
    for (const line of wrap(ctx, o.eventName.toUpperCase(), W - 160, 2)) {
        ctx.fillText(line, W / 2, y);
        y += story ? 52 : 44;
    }

    // Photo in a ring
    const d = story ? 560 : 380;
    const cy = y + (story ? 60 : 30) + d / 2;
    ctx.save();
    ctx.beginPath(); ctx.arc(W / 2, cy, d / 2 + 14, 0, Math.PI * 2); ctx.fillStyle = brand; ctx.fill();
    ctx.beginPath(); ctx.arc(W / 2, cy, d / 2 + 5, 0, Math.PI * 2); ctx.fillStyle = '#0b1220'; ctx.fill();
    ctx.beginPath(); ctx.arc(W / 2, cy, d / 2, 0, Math.PI * 2); ctx.clip();
    const img = o.photo ? await loadImage(o.photo) : null;
    if (img) {
        const s = Math.max(d / img.width, d / img.height);
        ctx.drawImage(img, W / 2 - (img.width * s) / 2, cy - (img.height * s) / 2, img.width * s, img.height * s);
    } else {
        ctx.fillStyle = colorFor(o.name);
        ctx.fillRect(W / 2 - d / 2, cy - d / 2, d, d);
        ctx.fillStyle = '#fff';
        ctx.font = `800 ${d * 0.38}px ${FONT}`;
        ctx.textBaseline = 'middle';
        ctx.fillText(initials(o.name), W / 2, cy + d * 0.02);
        ctx.textBaseline = 'alphabetic';
    }
    ctx.restore();

    // "VOTE" pill, name, category
    y = cy + d / 2 + (story ? 120 : 82);
    ctx.font = `800 ${story ? 44 : 36}px ${FONT}`;
    const vote = o.labels.vote.toUpperCase();
    const pw = ctx.measureText(vote).width + 64;
    const ph = story ? 72 : 60;
    roundRect(ctx, W / 2 - pw / 2, y - ph + (story ? 16 : 14), pw, ph, ph / 2);
    ctx.fillStyle = brand; ctx.fill();
    ctx.fillStyle = '#fff';
    ctx.fillText(vote, W / 2, y);
    y += story ? 120 : 92;

    let size = story ? 96 : 76;
    ctx.font = `800 ${size}px ${FONT}`;
    while (size > 44 && wrap(ctx, o.name, W - 140, 3).length > 2) { size -= 6; ctx.font = `800 ${size}px ${FONT}`; }
    for (const line of wrap(ctx, o.name, W - 140, 2)) {
        ctx.fillText(line, W / 2, y);
        y += size * 1.12;
    }
    ctx.fillStyle = 'rgba(255,255,255,.8)';
    ctx.font = `600 ${story ? 44 : 36}px ${FONT}`;
    for (const line of wrap(ctx, o.labels.forCategory, W - 160, 2)) {
        ctx.fillText(line, W / 2, y + 6);
        y += story ? 56 : 46;
    }

    // QR code to the voting page
    const q = story ? 300 : 190;
    const qx = story ? W / 2 - q / 2 : W - q - 70;
    const qy = story ? H - q - 230 : H - q - 70;
    roundRect(ctx, qx - 18, qy - 18, q + 36, q + 36, 26); ctx.fillStyle = '#fff'; ctx.fill();
    const qr = await loadImageUrl(await QRCode.toDataURL(o.voteUrl, { margin: 0, width: q * 2, color: { dark: '#0b1220', light: '#ffffff' } }));
    ctx.drawImage(qr, qx, qy, q, q);
    ctx.textAlign = story ? 'center' : 'left';
    ctx.fillStyle = '#fff';
    ctx.font = `800 ${story ? 44 : 36}px ${FONT}`;
    const tx = story ? W / 2 : 70;
    const ty = story ? qy + q + 90 : qy + q / 2 - 6;
    ctx.fillText(o.labels.scan, tx, ty);
    ctx.fillStyle = 'rgba(255,255,255,.65)';
    ctx.font = `600 ${story ? 30 : 26}px ${FONT}`;
    const host = o.voteUrl.replace(/^https?:\/\//, '').replace(/\/vote\/.*/, '');
    ctx.fillText(host, tx, ty + (story ? 50 : 44));
}

// ---------------------------------------------------------------- helpers
function glow(ctx: CanvasRenderingContext2D, x: number, y: number, r: number, color: string) {
    const g = ctx.createRadialGradient(x, y, 0, x, y, r);
    g.addColorStop(0, color);
    g.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);
}
function hexA(hex: string, a: number) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
}
function roundRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}
/** Word-wrap to at most `max` lines (the last one ends in … if cut). */
function wrap(ctx: CanvasRenderingContext2D, text: string, width: number, max: number): string[] {
    const words = text.trim().split(/\s+/);
    const lines: string[] = [];
    let line = '';
    for (const w of words) {
        const next = line ? `${line} ${w}` : w;
        if (ctx.measureText(next).width <= width || !line) { line = next; continue; }
        lines.push(line);
        line = w;
    }
    if (line) lines.push(line);
    if (lines.length > max) {
        const kept = lines.slice(0, max);
        let last = kept[max - 1]!;
        while (last.length > 1 && ctx.measureText(last + '…').width > width) last = last.slice(0, -1);
        kept[max - 1] = last + '…';
        return kept;
    }
    return lines;
}
function loadImage(blob: Blob): Promise<HTMLImageElement | null> {
    const url = URL.createObjectURL(blob);
    return loadImageUrl(url).catch(() => null).finally(() => URL.revokeObjectURL(url));
}
function loadImageUrl(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}
