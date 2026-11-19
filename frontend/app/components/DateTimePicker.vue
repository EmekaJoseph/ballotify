<template>
    <div ref="root" class="dtp" :class="{ open }">
        <!-- Trigger: looks like an input, shows a readable date -->
        <div class="dtp-field form-control" :class="{ 'is-invalid': invalid, disabled }">
            <button :id="id" type="button" class="dtp-trigger" :disabled="disabled" aria-haspopup="dialog" :aria-expanded="open"
                @click="toggle">
                <i class="bi bi-calendar3 dtp-icon"></i>
                <span v-if="model" class="dtp-value">{{ display }}</span>
                <span v-else class="dtp-placeholder">{{ placeholder }}</span>
            </button>
            <button v-if="model && !disabled" type="button" class="dtp-clear" aria-label="Clear date" @click="clear">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Popover (desktop) / bottom sheet (phone) -->
        <Transition name="dtp-pop">
            <div v-if="open" class="dtp-layer" :class="{ up: openUp }">
                <div class="dtp-scrim" @click="open = false"></div>
                <div class="dtp-panel" :class="{ right: alignRight }" role="dialog" :aria-label="label || 'Choose date and time'"
                    @keydown.esc.stop="open = false">
                    <div class="dtp-sheet-title">{{ label || 'Choose date and time' }}</div>

                    <div class="dtp-presets">
                        <button v-for="p in presets" :key="p.label" type="button" class="dtp-chip" @click="applyPreset(p.at())">{{ p.label }}</button>
                    </div>

                    <div class="dtp-head">
                        <button type="button" class="dtp-nav" aria-label="Previous month" @click="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                        <div class="dtp-month">{{ monthLabel }}</div>
                        <button type="button" class="dtp-nav" aria-label="Next month" @click="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
                    </div>

                    <div class="dtp-grid dtp-dow">
                        <span v-for="d in weekdays" :key="d">{{ d }}</span>
                    </div>
                    <div class="dtp-grid">
                        <button v-for="c in cells" :key="c.key" type="button" class="dtp-day"
                            :class="{ muted: !c.inMonth, today: c.isToday, selected: c.isSelected }"
                            :disabled="c.disabled" :aria-pressed="c.isSelected" :aria-label="c.aria" @click="pickDay(c.date)">
                            {{ c.date.getDate() }}
                        </button>
                    </div>

                    <div class="dtp-time">
                        <i class="bi bi-clock text-muted"></i>
                        <select v-model.number="hour12" class="form-select form-select-sm" aria-label="Hour">
                            <option v-for="h in 12" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold text-muted">:</span>
                        <select v-model.number="minute" class="form-select form-select-sm" aria-label="Minute">
                            <option v-for="m in minuteOptions" :key="m" :value="m">{{ String(m).padStart(2, '0') }}</option>
                        </select>
                        <div class="dtp-ampm" role="group" aria-label="AM or PM">
                            <button type="button" :class="{ active: !pm }" @click="pm = false">AM</button>
                            <button type="button" :class="{ active: pm }" @click="pm = true">PM</button>
                        </div>
                    </div>

                    <div v-if="minWarning" class="dtp-warn"><i class="bi bi-exclamation-circle"></i> {{ minWarning }}</div>

                    <div class="dtp-foot">
                        <button type="button" class="btn btn-ghost btn-sm" @click="clear">Clear</button>
                        <button type="button" class="btn btn-primary btn-sm" @click="open = false">Done</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
/**
 * Date + time picker. v-model is a local "YYYY-MM-DDTHH:mm" string (same format
 * as <input type="datetime-local">), or '' for none.
 */
const props = withDefaults(defineProps<{
    id?: string;
    placeholder?: string;
    label?: string;
    /** Earliest allowed value, same format. Days before it are disabled. */
    min?: string;
    invalid?: boolean;
    disabled?: boolean;
    /** Time used when a day is picked before any time is set, "HH:mm". */
    defaultTime?: string;
}>(), { id: undefined, placeholder: 'Pick a date', label: '', min: '', invalid: false, disabled: false, defaultTime: '09:00' });

const model = defineModel<string>({ default: '' });

const root = ref<HTMLElement | null>(null);
const open = ref(false);
const alignRight = ref(false);
const openUp = ref(false);

const pad = (n: number) => String(n).padStart(2, '0');
const toStr = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
const parse = (s?: string | null) => {
    if (!s) return null;
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? null : d;
};
const sameDay = (a: Date, b: Date) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

const value = computed(() => parse(model.value));
const minDate = computed(() => parse(props.min));

const display = computed(() => {
    const d = value.value;
    if (!d) return '';
    // Compact: the year only when it isn't this year.
    const date = d.toLocaleDateString(undefined, {
        weekday: 'short', day: 'numeric', month: 'short',
        ...(d.getFullYear() !== new Date().getFullYear() ? { year: 'numeric' as const } : {}),
    });
    const time = d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    return `${date} · ${time}`;
});

// ---- calendar
const view = ref(startOfMonth(value.value ?? minDate.value ?? new Date()));
function startOfMonth(d: Date) { return new Date(d.getFullYear(), d.getMonth(), 1); }
function shiftMonth(n: number) { view.value = new Date(view.value.getFullYear(), view.value.getMonth() + n, 1); }

const monthLabel = computed(() => view.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));
// Week starts on Monday.
const weekdays = computed(() => Array.from({ length: 7 }, (_, i) =>
    new Date(2024, 0, 1 + i).toLocaleDateString(undefined, { weekday: 'narrow' })));

const cells = computed(() => {
    const first = view.value;
    const offset = (first.getDay() + 6) % 7;
    const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset);
    const today = new Date();
    const min = minDate.value;
    const minDay = min ? new Date(min.getFullYear(), min.getMonth(), min.getDate()) : null;
    return Array.from({ length: 42 }, (_, i) => {
        const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        return {
            key: date.toDateString(),
            date,
            inMonth: date.getMonth() === first.getMonth(),
            isToday: sameDay(date, today),
            isSelected: !!value.value && sameDay(date, value.value),
            disabled: !!minDay && date < minDay,
            aria: date.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
        };
    });
});

// ---- time (edits the current value; before a day is chosen, it's held until one is)
const minuteOptions = Array.from({ length: 12 }, (_, i) => i * 5);
const [dh, dm] = props.defaultTime.split(':').map(Number);
const pending = reactive({ h: dh ?? 9, m: dm ?? 0 });

const hours24 = computed(() => value.value?.getHours() ?? pending.h);
const hour12 = computed({
    get: () => ((hours24.value + 11) % 12) + 1,
    set: (h: number) => setTime((h % 12) + (pm.value ? 12 : 0), minute.value),
});
const minute = computed({
    get: () => value.value?.getMinutes() ?? pending.m,
    set: (m: number) => setTime(hours24.value, m),
});
const pm = computed({
    get: () => hours24.value >= 12,
    set: (isPm: boolean) => setTime((hours24.value % 12) + (isPm ? 12 : 0), minute.value),
});

function setTime(h: number, m: number) {
    const d = value.value;
    if (!d) { pending.h = h; pending.m = m; return; }
    model.value = toStr(new Date(d.getFullYear(), d.getMonth(), d.getDate(), h, m));
}

function pickDay(day: Date) {
    let d = new Date(day.getFullYear(), day.getMonth(), day.getDate(), hours24.value, minute.value);
    // Picking today with a time that's already gone: move to the next whole hour.
    const now = new Date();
    if (!value.value && sameDay(d, now) && d < now) d = nextHour(now);
    model.value = toStr(d);
    if (!sameDay(day, view.value) && day.getMonth() !== view.value.getMonth()) view.value = startOfMonth(day);
}

function nextHour(from: Date) {
    const d = new Date(from);
    d.setHours(d.getHours() + 1, 0, 0, 0);
    return d;
}

// ---- presets
const presets = computed(() => {
    const at = (days: number, h: number) => () => {
        const n = new Date();
        return new Date(n.getFullYear(), n.getMonth(), n.getDate() + days, h, 0);
    };
    return [
        { label: 'In 1 hour', at: () => nextHour(new Date()) },
        { label: 'Tomorrow 9 AM', at: at(1, 9) },
        { label: 'Tomorrow 6 PM', at: at(1, 18) },
        { label: 'In a week', at: () => { const d = value.value ?? minDate.value ?? new Date(); const r = new Date(d); r.setDate(r.getDate() + 7); if (!value.value && !minDate.value) r.setHours(9, 0, 0, 0); return r; } },
    ];
});
function applyPreset(d: Date) {
    d.setSeconds(0, 0);
    model.value = toStr(d);
    view.value = startOfMonth(d);
}

const minWarning = computed(() =>
    value.value && minDate.value && value.value <= minDate.value ? 'This is before the opening time.' : '');

// ---- open/close
function toggle() {
    if (open.value) { open.value = false; return; }
    view.value = startOfMonth(value.value ?? minDate.value ?? new Date());
    const rect = root.value?.getBoundingClientRect();
    alignRight.value = !!rect && rect.left + 320 > window.innerWidth - 16;
    // Not enough room below but enough above: open upwards.
    const PANEL_H = 480;
    openUp.value = !!rect && rect.bottom + PANEL_H > window.innerHeight && rect.top > PANEL_H;
    open.value = true;
}
function clear() {
    model.value = '';
    open.value = false;
}

function onDocDown(e: MouseEvent) {
    if (open.value && root.value && !root.value.contains(e.target as Node)) open.value = false;
}
function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape' && open.value) open.value = false;
}
onMounted(() => {
    document.addEventListener('mousedown', onDocDown);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocDown);
    document.removeEventListener('keydown', onKey);
});
</script>

<style scoped>
.dtp { position: relative; }

/* Field */
.dtp-field { display: flex; align-items: center; gap: .25rem; padding: 0; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
.dtp.open .dtp-field { border-color: var(--brand-500); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--brand-500) 18%, transparent); }
.dtp-field.disabled { background: var(--surface-2); cursor: not-allowed; opacity: .7; }
.dtp-trigger {
    flex: 1; min-width: 0; display: flex; align-items: center; gap: .6rem; text-align: left;
    border: 0; background: none; padding: .55rem .9rem; color: var(--ink); font: inherit;
}
.dtp-trigger:disabled { cursor: not-allowed; }
.dtp-icon { color: var(--brand-600); }
.dtp-value { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dtp-placeholder { color: var(--muted); }
.dtp-clear { border: 0; background: none; color: var(--muted); width: 2rem; height: 2rem; margin-right: .3rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; }
.dtp-clear:hover { background: var(--line); color: var(--ink); }
.dtp-field.is-invalid { border-color: var(--danger); }

/* Panel */
.dtp-layer { position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 1200; }
.dtp-scrim { display: none; }
.dtp-panel {
    position: absolute; top: 0; left: 0; width: 320px; padding: 1rem;
    background: var(--surface); border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow-lg);
}
.dtp-panel.right { left: auto; right: 0; }
.dtp-layer.up { top: auto; bottom: calc(100% + 6px); }
.dtp-layer.up .dtp-panel { top: auto; bottom: 0; }
.dtp-sheet-title { display: none; }

.dtp-presets { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .85rem; }
.dtp-chip {
    border: 1px solid var(--line); background: var(--surface-2); color: var(--ink-2); font-size: .76rem; font-weight: 600;
    padding: .28rem .6rem; border-radius: 999px; transition: all .15s;
}
.dtp-chip:hover { border-color: var(--brand-500); color: var(--brand-700); background: var(--brand-50); }

.dtp-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .4rem; }
.dtp-month { font-weight: 800; color: var(--ink); }
.dtp-nav { width: 2rem; height: 2rem; border: 0; border-radius: 10px; background: none; color: var(--ink-2); display: grid; place-items: center; }
.dtp-nav:hover { background: var(--line); }

.dtp-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.dtp-dow span { text-align: center; font-size: .7rem; font-weight: 700; color: var(--muted); padding: .3rem 0; text-transform: uppercase; }
.dtp-day {
    aspect-ratio: 1; border: 0; border-radius: 10px; background: none; color: var(--ink); font-size: .86rem; font-weight: 600;
    position: relative; transition: background-color .12s;
}
@media (hover: hover) {
    .dtp-day:hover:not(:disabled):not(.selected) { background: var(--brand-50); color: var(--brand-700); }
}
.dtp-day.muted { color: var(--muted); opacity: .55; }
.dtp-day.today::after { content: ''; position: absolute; left: 50%; bottom: 5px; width: 4px; height: 4px; margin-left: -2px; border-radius: 50%; background: var(--brand-500); }
.dtp-day.selected { background: var(--brand-600); color: #fff; box-shadow: 0 4px 12px color-mix(in srgb, var(--brand-600) 35%, transparent); }
.dtp-day.selected::after { background: #fff; }
.dtp-day:disabled { opacity: .25; cursor: not-allowed; text-decoration: line-through; }
.dtp-day:focus-visible, .dtp-nav:focus-visible, .dtp-chip:focus-visible { outline: 2px solid var(--brand-500); outline-offset: 1px; }

.dtp-time { display: flex; align-items: center; gap: .4rem; margin-top: .85rem; padding-top: .85rem; border-top: 1px solid var(--line); }
.dtp-time .form-select { width: auto; padding-right: 1.8rem; font-weight: 600; }
.dtp-ampm { margin-left: auto; display: inline-flex; padding: 2px; background: var(--surface-2); border: 1px solid var(--line); border-radius: 10px; }
.dtp-ampm button { border: 0; background: none; padding: .22rem .6rem; border-radius: 8px; font-size: .78rem; font-weight: 700; color: var(--muted); }
.dtp-ampm button.active { background: var(--surface); color: var(--brand-700); box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,.08)); }

.dtp-warn { margin-top: .6rem; font-size: .78rem; color: var(--danger); }
.dtp-foot { display: flex; justify-content: space-between; margin-top: .85rem; }

/* Phone: bottom sheet */
@media (max-width: 575.98px) {
    .dtp-layer, .dtp-layer.up { position: fixed; inset: 0; z-index: 1600; }
    .dtp-scrim { display: block; position: absolute; inset: 0; background: rgba(10, 18, 34, .45); backdrop-filter: blur(3px); }
    .dtp-panel, .dtp-panel.right {
        top: auto; bottom: 0; left: 0; right: 0; width: 100%; border-radius: 22px 22px 0 0; border: 0;
        padding: 1.1rem 1rem calc(1rem + env(safe-area-inset-bottom));
    }
    .dtp-sheet-title { display: block; font-weight: 800; color: var(--ink); margin-bottom: .75rem; }
    .dtp-day { font-size: .95rem; }
}

.dtp-pop-enter-active, .dtp-pop-leave-active { transition: opacity .15s ease, transform .15s ease; }
.dtp-pop-enter-from, .dtp-pop-leave-to { opacity: 0; transform: translateY(-4px); }
@media (max-width: 575.98px) {
    .dtp-pop-enter-from .dtp-panel, .dtp-pop-leave-to .dtp-panel { transform: translateY(30px); }
    .dtp-pop-enter-from, .dtp-pop-leave-to { transform: none; }
}
</style>
