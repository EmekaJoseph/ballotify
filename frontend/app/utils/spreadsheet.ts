/**
 * Reading lists from files: Excel (.xlsx) or CSV / plain text. Every import
 * (voters, candidates, suggested names) turns the file into CSV text, so the
 * same column matching and preview works for all of them.
 */

/** What the file pickers accept. */
export const TABLE_FILE_ACCEPT = '.xlsx,.csv,.txt,text/csv,text/plain,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

const XLSX_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
const isExcel = (file: File) => /\.xlsx$/i.test(file.name) || file.type === XLSX_TYPE;

function cellText(v: unknown): string {
    if (v === null || v === undefined) return '';
    if (v instanceof Date) return v.toISOString().slice(0, 10);
    return String(v).trim();
}

/** Plain CSV (not toCsv: that guards exports against formulas and would turn "+234…" into "'+234…"). */
function plainCsv(rows: string[][]): string {
    const esc = (c: string) => (/[",\r\n]/.test(c) ? `"${c.replace(/"/g, '""')}"` : c);
    return rows.map(r => r.map(esc).join(',')).join('\n');
}

/** The file's first sheet (Excel) or its text (CSV), as CSV text. Throws a readable message on failure. */
export async function readTableFile(file: File): Promise<string> {
    if (/\.xls$/i.test(file.name)) {
        throw new Error('Old Excel files (.xls) can’t be read. In Excel, choose File → Save As → Excel Workbook (.xlsx) or CSV, then load that.');
    }
    if (!isExcel(file)) return file.text();

    // Loaded only when someone imports an Excel file, so the app stays small.
    const { readSheet } = await import('read-excel-file/browser');
    let rows: unknown[][];
    try {
        rows = (await readSheet(file)) as unknown[][];
    } catch {
        throw new Error('That Excel file couldn’t be read. Check it opens in Excel, or save it as CSV and load that.');
    }
    return plainCsv(rows.map(r => r.map(cellText)).filter(r => r.some(c => c !== '')));
}

/** Just the names from a file: the column headed Name / Nominee / Candidate, else the first column. */
export function namesFromTable(csv: string): string[] {
    const rows = parseCsv(csv).filter(r => r.some(c => c.trim()));
    if (!rows.length) return [];
    const header = rows[0]!.map(h => h.trim().toLowerCase());
    const col = header.findIndex(h => /\b(full ?name|name|nominee|candidate)\b/.test(h));
    const body = col >= 0 ? rows.slice(1) : rows;
    return body.map(r => (r[col >= 0 ? col : 0] ?? '').replace(/\s+/g, ' ').trim()).filter(Boolean);
}
