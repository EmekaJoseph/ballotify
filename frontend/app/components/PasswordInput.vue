<template>
    <div class="input-icon pw-field">
        <i v-if="icon" class="bi" :class="icon"></i>
        <input :id="id" v-model="model" :type="visible ? 'text' : 'password'" v-bind="$attrs"
            class="form-control form-control-lg pw-input" :class="[{ 'is-invalid': invalid }, { 'no-icon': !icon }]" />
        <button type="button" class="pw-reveal" :aria-label="visible ? 'Hide password' : 'Show password'"
            :aria-pressed="visible" :aria-controls="id" :title="visible ? 'Hide password' : 'Show password'"
            @click="visible = !visible">
            <i class="bi" :class="visible ? 'bi-eye-slash' : 'bi-eye'"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
/** Password input with a floating show/hide (eye) toggle. Extra attributes go to the <input>. */
defineOptions({ inheritAttrs: false });
withDefaults(defineProps<{ id: string; icon?: string; invalid?: boolean }>(), { icon: 'bi-lock', invalid: false });
const model = defineModel<string>({ default: '' });
const visible = ref(false);
</script>

<style scoped>
.pw-input { padding-right: 3rem; }
.pw-input.no-icon { padding-left: 1rem; }
/* Keep room for the eye when Bootstrap adds its invalid icon. */
.pw-input.is-invalid { padding-right: 4.5rem; background-position: right 3rem center; }
.pw-reveal {
    position: absolute; right: .45rem; top: 50%; transform: translateY(-50%);
    width: 2.25rem; height: 2.25rem; display: grid; place-items: center;
    border: 0; border-radius: 999px; background: transparent; color: var(--muted);
    transition: background-color .15s, color .15s;
}
.pw-reveal:hover { background: var(--line); color: var(--ink); }
.pw-reveal:focus-visible { outline: 2px solid var(--brand-500); outline-offset: 1px; }
/* Undo the global .input-icon > i rule for the button's own icon. */
.pw-reveal .bi { position: static; transform: none; color: inherit; font-size: 1.1rem; }
/* Hide Edge's built-in reveal button so there's only one eye. */
.pw-input::-ms-reveal, .pw-input::-ms-clear { display: none; }
</style>
