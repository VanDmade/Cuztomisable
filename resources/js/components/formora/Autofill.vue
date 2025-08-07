<template>
    <div class="form-floating fm-form-input fm-form-autofill"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <input
            v-model="value"
            type="text"
            :id="id"
            class="form-control fm-form-control"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :list="datalist"
            @input="errorList = []"
            @keydown.enter.prevent="handleEnter">
        <label v-if="label != null && label != ''" :for="id" class="fm-form-label">{{ label }}</label>
        <datalist :id="datalist">
            <option v-for="(option, i) in list" :key="`${id}-option-${i}`" :value="option[listText]">{{ option[listText] }}</option>
        </datalist>
        <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-autofill_'+Math.random().toString(16).slice(2),
            datalist: 'datalist_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    methods: {
        handleEnter: function() {
            if (!this.value || !this.list || this.list.length === 0) return;
            const match = this.list.find(item =>
                item[this.listText].toLowerCase().includes(this.value.toLowerCase())
            );
            if (match) {
                this.value = match[this.listText];
            }
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
        }
    },
    props: {
        modelValue: { type: [String, Number], default: '' },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        list: { type: [Array, Object], default: [] },
        listText: { type: String, default: 'text' }
    }
}
</script>