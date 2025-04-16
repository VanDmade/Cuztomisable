<template>
    <div class="form-floating fm-form-input"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <input
            v-model="value"
            :type="type == 'number' ? 'input' : type"
            :id="id"
            class="form-control fm-form-control"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :maxlength="max != null ? max : 1000000"
            @input="errorList = []">
        <label v-if="label != null && label != ''" :for="id" class="fm-form-label">{{ label }}</label>
        <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-input_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    methods: {
        formatValue: function(value) {
            // Checks for a format and then modifies the input 
            if (this.format != null && this.format.indexOf('X') !== false) {
                let list = value.replace(/\D/g, '').split('');
                let newValue = this.format;
                for (let i = 0; i < list.length; i++) {
                    newValue = newValue.replace('X', list[i]);
                }
                value = newValue;
                value = value.split('X');
                value = value[0].replace(/[-()\s]+$/, '');
            }
            return value;
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                value = this.formatValue(value);
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
        format: {
            immediate: true,
            handler: function(format) {
                this.value = this.formatValue(this.value);
            }
        }
    },
    props: {
        modelValue: { type: [String, Number], default: '' },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        type: { type: String, default: 'input' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        format: { type: String, default: null },
        hideDetails: { type: Boolean, default: false },
        max: { type: [String, Number], default: null },
    }
}
</script>