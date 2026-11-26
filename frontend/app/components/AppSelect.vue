<template>
    <div class="app-select" :class="[`size-${size}`, `variant-${variant}`, { open, disabled, block }]">
        <button :id="id" ref="trigger" type="button" class="as-trigger" role="combobox" aria-haspopup="listbox"
            :aria-expanded="open" :aria-controls="listId" :aria-label="ariaLabel" :aria-activedescendant="open && active >= 0 ? optionId(active) : undefined"
            :disabled="disabled" :title="title" @click="toggle" @keydown="onTriggerKey">
            <i v-if="icon" class="bi as-icon" :class="icon" aria-hidden="true"></i>
            <span class="as-value" :class="{ 'is-empty': !selected }">{{ selected ? selected.label : placeholder }}</span>
            <i class="bi bi-chevron-down as-caret" aria-hidden="true"></i>
        </button>

        <Teleport to="body">
            <Transition name="as-pop">
                <div v-if="open" ref="menu" class="as-menu" :class="[{ up: placeUp }, `size-${size}`]" :style="menuStyle"
                    @keydown="onMenuKey" @mousedown.prevent>
                    <div v-if="isSearchable" class="as-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input ref="searchInput" v-model="query" type="text" :placeholder="searchPlaceholder" aria-label="Search options"
                            autocomplete="off" spellcheck="false" @mousedown.stop @keydown="onMenuKey" />
                    </div>
                    <ul :id="listId" ref="list" class="as-list" role="listbox" :aria-label="ariaLabel">
                        <li v-for="(o, i) in filtered" :id="optionId(i)" :key="String(o.value)" role="option" class="as-option"
                            :class="{ active: i === active, selected: isSelected(o), disabled: o.disabled }"
                            :aria-selected="isSelected(o)" :aria-disabled="o.disabled || undefined"
                            @mouseenter="active = i" @click="choose(o)">
                            <i v-if="o.icon" class="bi as-opt-icon" :class="o.icon" aria-hidden="true"></i>
                            <span class="as-opt-text">
                                <span class="as-opt-label">{{ o.label }}</span>
                                <span v-if="o.hint" class="as-opt-hint">{{ o.hint }}</span>
                            </span>
                            <i v-if="isSelected(o)" class="bi bi-check2 as-check" aria-hidden="true"></i>
                        </li>
                        <li v-if="!filtered.length" class="as-empty">{{ emptyText }}</li>
                    </ul>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts" generic="T extends string | number | null">
/**
 * The app's dropdown. Looks like the rest of the UI (no system-blue lists),
 * works with the keyboard (arrows, Enter, Esc, Home/End, type to jump), adds a
 * search box for long lists, and opens upward when there's no room below.
 * The menu is teleported to <body>, so cards and dialogs never clip it.
 */
export interface SelectOption<V> { value: V; label: string; hint?: string; icon?: string; disabled?: boolean }

const props = withDefaults(defineProps<{
    options: SelectOption<T>[];
    placeholder?: string;
    size?: 'sm' | 'md' | 'lg';
    /** field = like a form input; pill = compact rounded (headers, toolbars). */
    variant?: 'field' | 'pill';
    icon?: string;
    disabled?: boolean;
    /** Full width (default for fields). */
    block?: boolean;
    /** Show a search box: true/false, or automatically for lists longer than 10. */
    searchable?: boolean | 'auto';
    searchPlaceholder?: string;
    emptyText?: string;
    id?: string;
    ariaLabel?: string;
    title?: string;
    /** Menu at least this wide (px); defaults to the trigger's width. */
    menuMinWidth?: number;
}>(), {
    placeholder: 'Choose…', size: 'md', variant: 'field', icon: '', disabled: false, block: true, searchable: 'auto',
    searchPlaceholder: 'Search…', emptyText: 'No matches', id: undefined, ariaLabel: undefined, title: undefined, menuMinWidth: 0,
});
const model = defineModel<T>();
const emit = defineEmits<{ change: [value: T] }>();

const uid = useId();
const listId = `as-list-${uid}`;
const optionId = (i: number) => `as-opt-${uid}-${i}`;

const open = ref(false);
const active = ref(-1);
const query = ref('');
const trigger = ref<HTMLButtonElement | null>(null);
const menu = ref<HTMLElement | null>(null);
const list = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const placeUp = ref(false);
const menuStyle = ref<Record<string, string>>({});

const selected = computed(() => props.options.find(o => o.value === model.value) ?? null);
const isSelected = (o: SelectOption<T>) => o.value === model.value;
const isSearchable = computed(() => (props.searchable === 'auto' ? props.options.length > 10 : props.searchable));
const fold = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const filtered = computed(() => {
    const q = fold(query.value.trim());
    return q ? props.options.filter(o => fold(o.label).includes(q) || fold(o.hint ?? '').includes(q)) : props.options;
});

// ----- open / close / position
function place() {
    const el = trigger.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const gap = 6;
    const wanted = Math.min(320, 44 * Math.min(filtered.value.length || 1, 7) + (isSearchable.value ? 52 : 0) + 12);
    const below = window.innerHeight - r.bottom - gap - 8;
    const above = r.top - gap - 8;
    placeUp.value = below < Math.min(wanted, 220) && above > below;
    const width = Math.max(r.width, props.menuMinWidth);
    const left = Math.min(Math.max(8, r.left), window.innerWidth - width - 8);
    const maxH = Math.max(140, Math.min(360, placeUp.value ? above : below));
    menuStyle.value = {
        left: `${left}px`, width: `${width}px`, maxHeight: `${maxH}px`,
        ...(placeUp.value ? { bottom: `${window.innerHeight - r.top + gap}px` } : { top: `${r.bottom + gap}px` }),
    };
}
async function openMenu() {
    if (props.disabled || open.value) return;
    query.value = '';
    open.value = true;
    active.value = Math.max(0, filtered.value.findIndex(o => isSelected(o)));
    place();
    await nextTick();
    if (isSearchable.value) searchInput.value?.focus();
    scrollActive();
}
function close(refocus = true) {
    if (!open.value) return;
    open.value = false;
    if (refocus) trigger.value?.focus();
}
const toggle = () => (open.value ? close() : openMenu());

function choose(o: SelectOption<T>) {
    if (o.disabled) return;
    const changed = o.value !== model.value;
    model.value = o.value;
    close();
    if (changed) emit('change', o.value);
}

// ----- keyboard
function move(step: number) {
    const n = filtered.value.length;
    if (!n) return;
    let i = active.value;
    for (let k = 0; k < n; k++) {
        i = (i + step + n) % n;
        if (!filtered.value[i]!.disabled) break;
    }
    active.value = i;
    scrollActive();
}
function scrollActive() {
    nextTick(() => list.value?.querySelector<HTMLElement>('.as-option.active')?.scrollIntoView({ block: 'nearest' }));
}
let typed = '';
let typedTimer: ReturnType<typeof setTimeout> | undefined;
function typeahead(ch: string) {
    typed += ch.toLowerCase();
    clearTimeout(typedTimer);
    typedTimer = setTimeout(() => { typed = ''; }, 600);
    const i = filtered.value.findIndex(o => !o.disabled && fold(o.label).startsWith(fold(typed)));
    if (i >= 0) { active.value = i; scrollActive(); }
}
function onTriggerKey(e: KeyboardEvent) {
    // Open without a search box: focus stays here, so this button drives the list.
    if (open.value) {
        if (e.key === ' ') { e.preventDefault(); if (filtered.value[active.value]) choose(filtered.value[active.value]!); return; }
        onMenuKey(e);
        return;
    }
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
        e.preventDefault();
        openMenu();
    } else if (e.key.length === 1 && /\S/.test(e.key) && !e.ctrlKey && !e.metaKey && !e.altKey) {
        // Closed: typing picks the next matching option straight away (like a native select).
        const i = props.options.findIndex(o => !o.disabled && fold(o.label).startsWith(fold(e.key)));
        if (i >= 0) choose(props.options[i]!);
    }
}
function onMenuKey(e: KeyboardEvent) {
    const inSearch = e.target === searchInput.value;
    switch (e.key) {
        case 'ArrowDown': e.preventDefault(); move(1); break;
        case 'ArrowUp': e.preventDefault(); move(-1); break;
        case 'Home': if (!inSearch) { e.preventDefault(); active.value = 0; scrollActive(); } break;
        case 'End': if (!inSearch) { e.preventDefault(); active.value = filtered.value.length - 1; scrollActive(); } break;
        case 'Enter': e.preventDefault(); if (filtered.value[active.value]) choose(filtered.value[active.value]!); break;
        case 'Escape': e.preventDefault(); e.stopPropagation(); close(); break; // only this menu, not the dialog behind it
        case 'Tab': close(false); break;
        default:
            if (!inSearch && e.key.length === 1 && /\S/.test(e.key)) typeahead(e.key);
    }
}
watch(query, () => { active.value = filtered.value.length ? 0 : -1; if (open.value) place(); });

// ----- outside clicks, scrolling, resizing
function onDocPointer(e: PointerEvent) {
    const t = e.target as Node;
    if (open.value && !trigger.value?.contains(t) && !menu.value?.contains(t)) close(false);
}
function onViewport(e: Event) {
    if (!open.value) return;
    if (e.type === 'scroll' && menu.value?.contains(e.target as Node)) return; // scrolling the list itself
    place();
}
onMounted(() => {
    document.addEventListener('pointerdown', onDocPointer, true);
    window.addEventListener('resize', onViewport);
    window.addEventListener('scroll', onViewport, true);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointer, true);
    window.removeEventListener('resize', onViewport);
    window.removeEventListener('scroll', onViewport, true);
    clearTimeout(typedTimer);
});
watch(() => props.disabled, (d) => { if (d) close(false); });
defineExpose({ open: openMenu, close });
</script>

<style scoped>
.app-select { position: relative; display: inline-block; min-width: 0; }
.app-select.block { display: block; width: 100%; }

.as-trigger {
    width: 100%; display: flex; align-items: center; gap: .55rem; text-align: left; cursor: pointer;
    border: 1px solid var(--line-strong); border-radius: 12px; background: var(--surface); color: var(--ink);
    padding: .7rem .9rem; font: inherit; line-height: 1.4;
    transition: border-color .15s var(--ease, ease), box-shadow .15s var(--ease, ease), background-color .15s;
}
.as-trigger:hover:not(:disabled) { border-color: var(--brand-500); }
.as-trigger:focus-visible, .open .as-trigger { outline: none; border-color: var(--brand-500); box-shadow: 0 0 0 4px rgba(20, 145, 138, .14); }
.as-trigger:disabled { opacity: .6; cursor: not-allowed; background: var(--surface-2); }
.as-value { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
/* Not ".placeholder": that is Bootstrap's loading-skeleton class (grey pulsing bar). */
.as-value.is-empty { color: var(--placeholder, var(--muted)); }
.as-icon { color: var(--muted); }
.as-caret { font-size: .75rem; color: var(--muted); transition: transform .2s var(--ease, ease); flex-shrink: 0; }
.open .as-caret { transform: rotate(180deg); color: var(--brand-600); }

.size-sm .as-trigger { padding: .38rem .65rem; border-radius: 10px; font-size: .85rem; gap: .4rem; }
.size-lg .as-trigger { padding: .85rem 1rem; border-radius: 14px; font-size: 1.05rem; }

.variant-pill .as-trigger { border-radius: 999px; border-color: var(--line); padding: .4rem .7rem .4rem .8rem; font-size: .85rem; font-weight: 600; height: 36px; }
.variant-pill .as-trigger:hover:not(:disabled) { border-color: var(--line-strong); }

/* ----- menu (teleported to body) ----- */
.as-menu {
    position: fixed; z-index: 1650; display: flex; flex-direction: column; overflow: hidden;
    background: var(--surface); color: var(--ink); border: 1px solid var(--line); border-radius: 14px;
    box-shadow: var(--shadow-lg, 0 24px 60px -20px rgba(15, 23, 42, .28)), 0 2px 6px rgba(15, 23, 42, .06);
}
.as-search { display: flex; align-items: center; gap: .5rem; padding: .55rem .75rem; border-bottom: 1px solid var(--line); color: var(--muted); }
.as-search input { flex: 1; min-width: 0; border: 0; outline: none; background: transparent; color: var(--ink); font: inherit; font-size: .9rem; padding: .2rem 0; }
.as-list { list-style: none; margin: 0; padding: .35rem; overflow-y: auto; overscroll-behavior: contain; }
.as-option {
    display: flex; align-items: center; gap: .6rem; padding: .55rem .7rem; border-radius: 10px; cursor: pointer;
    font-size: .92rem; color: var(--ink); user-select: none;
}
.size-sm.as-menu .as-option { padding: .45rem .6rem; font-size: .86rem; }
.as-option.active { background: var(--brand-50); }
.as-option.selected { color: var(--brand-600); font-weight: 700; }
.as-option.disabled { opacity: .45; cursor: not-allowed; }
.as-opt-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.as-opt-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.as-opt-hint { font-size: .76rem; color: var(--muted); font-weight: 400; white-space: normal; line-height: 1.35; }
.as-opt-icon { color: var(--muted); }
.as-check { color: var(--brand-600); font-size: 1rem; flex-shrink: 0; }
.as-empty { padding: .75rem; text-align: center; color: var(--muted); font-size: .88rem; }

.as-pop-enter-active, .as-pop-leave-active { transition: opacity .14s ease, transform .14s ease; }
.as-pop-enter-from, .as-pop-leave-to { opacity: 0; transform: translateY(-4px) scale(.98); }
.as-menu.up.as-pop-enter-from, .as-menu.up.as-pop-leave-to { transform: translateY(4px) scale(.98); }
</style>

<style>
/* Dark mode: the active row needs a deeper tint than brand-50. */
:root[data-theme="dark"] .as-option.active { background: rgba(20, 145, 138, .18); }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .as-option.active { background: rgba(20, 145, 138, .18); } }
</style>
