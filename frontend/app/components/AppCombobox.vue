<template>
    <div class="app-combo" :class="[`size-${size}`, { open: showMenu }]">
        <div class="ac-field">
            <input :id="id" ref="input" :value="model" type="text" class="form-control" :class="size === 'lg' ? 'form-control-lg' : ''"
                role="combobox" aria-autocomplete="list" :aria-expanded="showMenu" :aria-controls="listId"
                :aria-activedescendant="showMenu && active >= 0 ? optionId(active) : undefined"
                :placeholder="placeholder" :maxlength="maxlength" autocomplete="off" spellcheck="false"
                @input="onInput" @focus="openMenu" @click="openMenu" @keydown="onKey" @blur="close" />
            <button v-if="options.length" type="button" class="ac-toggle" tabindex="-1" :aria-label="toggleLabel"
                @mousedown.prevent @click="toggle">
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
        </div>

        <Teleport to="body">
            <Transition name="as-pop">
                <ul v-if="showMenu" :id="listId" ref="list" class="ac-menu" :class="{ up: placeUp }" :style="menuStyle" role="listbox" @mousedown.prevent>
                    <li v-for="(o, i) in filtered" :id="optionId(i)" :key="o" role="option" class="ac-option"
                        :class="{ active: i === active }" :aria-selected="i === active" @mouseenter="active = i" @click="choose(o)">
                        <i class="bi bi-person" aria-hidden="true"></i><span class="text-truncate">{{ o }}</span>
                    </li>
                </ul>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
/**
 * A text field with suggestions: pick one from the list, or type anything.
 * The list narrows as you type (ignoring case and accents); arrows + Enter pick,
 * Esc closes. The menu is teleported to <body>, so cards never clip it.
 */
const props = withDefaults(defineProps<{
    options: string[];
    placeholder?: string;
    size?: 'md' | 'lg';
    maxlength?: number;
    id?: string;
    toggleLabel?: string;
}>(), { placeholder: '', size: 'md', maxlength: undefined, id: undefined, toggleLabel: 'Show suggestions' });
const model = defineModel<string>({ default: '' });

const uid = useId();
const listId = `ac-list-${uid}`;
const optionId = (i: number) => `ac-opt-${uid}-${i}`;

const open = ref(false);
const active = ref(-1);
const input = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);
const placeUp = ref(false);
const menuStyle = ref<Record<string, string>>({});
/** Show everything when the field holds an exact suggestion (just picked), else filter by what's typed. */
const typed = ref(false);

const fold = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
const filtered = computed(() => {
    const q = fold(model.value);
    if (!q || !typed.value) return props.options;
    return props.options.filter(o => fold(o).includes(q));
});
const showMenu = computed(() => open.value && filtered.value.length > 0);

function place() {
    const el = input.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const gap = 6;
    const below = window.innerHeight - r.bottom - gap - 8;
    const above = r.top - gap - 8;
    const wanted = Math.min(300, 44 * Math.min(filtered.value.length, 7) + 12);
    placeUp.value = below < Math.min(wanted, 200) && above > below;
    menuStyle.value = {
        left: `${r.left}px`, width: `${r.width}px`, maxHeight: `${Math.max(140, Math.min(320, placeUp.value ? above : below))}px`,
        ...(placeUp.value ? { bottom: `${window.innerHeight - r.top + gap}px` } : { top: `${r.bottom + gap}px` }),
    };
}
function openMenu() {
    if (!props.options.length) return;
    open.value = true;
    active.value = -1;
    nextTick(place);
}
function close() { open.value = false; active.value = -1; }
function toggle() {
    if (open.value) return close();
    typed.value = false;
    input.value?.focus();
    openMenu();
}

function onInput(e: Event) {
    model.value = (e.target as HTMLInputElement).value;
    typed.value = true;
    open.value = true;
    active.value = -1;
    nextTick(place);
}
function choose(o: string) {
    model.value = o;
    typed.value = false;
    close();
}
function move(step: number) {
    const n = filtered.value.length;
    if (!n) return;
    if (!open.value) openMenu();
    active.value = active.value < 0 ? (step > 0 ? 0 : n - 1) : (active.value + step + n) % n;
    nextTick(() => list.value?.querySelector<HTMLElement>('.ac-option.active')?.scrollIntoView({ block: 'nearest' }));
}
function onKey(e: KeyboardEvent) {
    switch (e.key) {
        case 'ArrowDown': e.preventDefault(); move(1); break;
        case 'ArrowUp': e.preventDefault(); move(-1); break;
        case 'Enter':
            // Enter picks the highlighted suggestion; with none highlighted it submits the form as usual.
            if (showMenu.value && active.value >= 0) { e.preventDefault(); choose(filtered.value[active.value]!); }
            break;
        case 'Escape': if (showMenu.value) { e.preventDefault(); e.stopPropagation(); close(); } break;
        case 'Tab': close(); break;
    }
}

function onViewport(e: Event) {
    if (!showMenu.value) return;
    if (e.type === 'scroll' && list.value?.contains(e.target as Node)) return;
    place();
}
onMounted(() => {
    window.addEventListener('resize', onViewport);
    window.addEventListener('scroll', onViewport, true);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', onViewport);
    window.removeEventListener('scroll', onViewport, true);
});
watch(() => props.options, close);
defineExpose({ focus: () => input.value?.focus() });
</script>

<style scoped>
.app-combo { position: relative; }
.ac-field { position: relative; }
.ac-field .form-control { padding-right: 2.6rem; }
.ac-toggle {
    position: absolute; top: 50%; right: .4rem; transform: translateY(-50%);
    width: 32px; height: 32px; display: grid; place-items: center; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); cursor: pointer;
}
.ac-toggle:hover { background: var(--surface-2); color: var(--ink); }
.ac-toggle i { font-size: .8rem; transition: transform .2s var(--ease, ease); }
.open .ac-toggle i { transform: rotate(180deg); color: var(--brand-600); }

.ac-menu {
    position: fixed; z-index: 1650; list-style: none; margin: 0; padding: .35rem; overflow-y: auto; overscroll-behavior: contain;
    background: var(--surface); color: var(--ink); border: 1px solid var(--line); border-radius: 14px;
    box-shadow: var(--shadow-lg, 0 24px 60px -20px rgba(15, 23, 42, .28)), 0 2px 6px rgba(15, 23, 42, .06);
}
.ac-option { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: 10px; cursor: pointer; font-size: .95rem; user-select: none; min-width: 0; }
.ac-option i { color: var(--muted); flex-shrink: 0; }
.ac-option.active { background: var(--brand-50); }

.as-pop-enter-active, .as-pop-leave-active { transition: opacity .14s ease, transform .14s ease; }
.as-pop-enter-from, .as-pop-leave-to { opacity: 0; transform: translateY(-4px) scale(.98); }
.ac-menu.up.as-pop-enter-from, .ac-menu.up.as-pop-leave-to { transform: translateY(4px) scale(.98); }
</style>

<style>
:root[data-theme="dark"] .ac-option.active { background: rgba(20, 145, 138, .18); }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .ac-option.active { background: rgba(20, 145, 138, .18); } }
</style>
