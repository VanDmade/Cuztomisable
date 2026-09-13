<template>
    <div v-if="wysiwyg" class="cz-form-input cz-form-wysiwyg" :class="{ 'is-invalid': errorList.length > 0 }">
        <label v-if="label != null && label != ''" :for="id" class="form-label cz-form-label cz-form-label-static">{{ label }}</label>
        <div ref="editor" class="cz-quill-editor" :style="{ 'min-height': height }"></div>
        <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
        </ul>
    </div>
    <div v-else class="form-floating cz-form-input cz-form-textarea"
        :class="{ 'cz-no-label': label == null || label == '' }">
        <textarea
            v-model="value"
            :id="id"
            class="form-control cz-form-control"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :maxlength="maxlength"
            :style="{ 'height': height }"
            @input="errorList = []"></textarea>
        <label v-if="label != null && label != ''" :for="id" class="form-label cz-form-label">{{ label }}</label>
        <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const WYSIWYG_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ align: [] }],
    ['blockquote', 'link'],
    ['clean'],
];

export default {
    data: function() {
        return {
            id: 'cz-textarea_'+Math.random().toString(16).slice(2),
            // Calculates the height based on the total rows to allow for floating labels with ease
            height: ((parseInt(this.rows) + 1) * 25) + 'px',
            errorList: [],
            quill: null,
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    watch: {
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
        },
        modelValue: function(value) {
            // Only syncs from outside (e.g. the value arriving after an async fetch) - if this
            // fired because of the editor's own text-change, its HTML already matches and this
            // is a no-op, so typing never gets its cursor position clobbered.
            if (this.wysiwyg && this.quill && this.quill.root.innerHTML !== (value ?? '')) {
                this.quill.root.innerHTML = value ?? '';
            }
        },
        disabled: function(value) {
            if (this.quill) {
                this.quill.enable(!value && !this.readonly);
            }
        },
        readonly: function(value) {
            if (this.quill) {
                this.quill.enable(!this.disabled && !value);
            }
        },
    },
    mounted: function() {
        if (this.wysiwyg && this.$refs.editor) {
            this.quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: this.placeholder,
                modules: { toolbar: WYSIWYG_TOOLBAR },
            });
            this.quill.root.innerHTML = this.modelValue ?? '';
            this.quill.enable(!this.disabled && !this.readonly);
            this.quill.on('text-change', () => {
                this.errorList = [];
                this.$emit('update:modelValue', this.quill.root.innerHTML);
            });
        }
    },
    beforeUnmount: function() {
        // Quill doesn't offer a formal destroy()
        this.quill = null;
    },
    props: {
        modelValue: { type: [String, Number], default: '' },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        rows: { type: [Number, String], default: 2 },
        maxlength: { type: Number, default: null },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        wysiwyg: { type: Boolean, default: false },
    }
}
</script>
