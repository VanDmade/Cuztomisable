<template>
    <div class="cz-form-input form-check"
        :class="{ 'cz-no-label': label == null || label == '' }">
        <input
            v-model="value"
            type="checkbox"
            :id="id"
            class="form-check-input"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
            :disabled="disabled"
            :value="inputTrueValue"
            :readonly="readonly"
            @input="errorList = []">
        <label v-if="!noLabel" class="form-check-label" :for="id">{{ label }}</label>
        <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'cz-checkbox_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue || this.inputTrueValue == this.modelValue ? true : false;
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
        noLabel: { type: Boolean, default: false },
        inputTrueValue: { type: [String, Number, Boolean], default: null },
        inputFalseValue: { type: [String, Number, Boolean], default: null },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
    }
}
</script>