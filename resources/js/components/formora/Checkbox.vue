<template>
    <div class="fm-form-input form-check"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <input
            v-model="value"
            type="checkbox"
            :id="id"
            class="form-check-input"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
            :value="inputTrueValue"
            :readonly="readonly"
            @input="errorList = []">
        <label class="form-check-label" :for="id">{{ label }}</label>
        <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-checkbox_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                if (value == true && this.inputTrueValue != null) {
                    value = this.inputTrueValue;
                }
                if (value == false && this.inputFalseValue != null) {
                    value = this.inputFalseValue;
                }
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
    },
    props: {
        modelValue: { type: [String, Number, Boolean], default: '' },
        label: { type: String, default: '' },
        inputTrueValue: { type: String, default: null },
        inputFalseValue: { type: String, default: null },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
    }
}
</script>