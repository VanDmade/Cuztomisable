<template>
    <div class="form-floating fm-form-phone"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <div class="fm-flex-container" v-if="value != ''">
            <fm-select
                v-if="$cuztomisable.locations.country_codes.length > 1"
                v-model="value.country_code"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.country_code == '' || value.country_code == null },
                    inputClass,
                    'fm-form-phone-country-code-input'
                ]"
                :items="$cuztomisable.locations.country_codes"
                :disabled="disabled"
                :readonly="readonly"
                hideDetails />
            <fm-input
                :label="label"
                v-model="value.number"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.number == '' || value.number == null,
                    'fm-form-phone-normal-borders-left': $cuztomisable.locations.country_codes.length <= 1,
                    'fm-form-phone-normal-borders-right': !extension },
                    inputClass,
                    'fm-form-phone-input'
                ]"
                max="15"
                type="number"
                :format="format"
                :disabled="disabled"
                :readonly="readonly"
                @change="errorList = []"
                hideDetails />
            <fm-input
                v-if="extension"
                label="Ext."
                v-model="value.extension"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.extension == '' || value.extension == null },
                    inputClass,
                    'fm-form-phone-extension-input'
                ]"
                type="number"
                :disabled="disabled"
                :readonly="readonly"
                hideDetails />
        </div>
        <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-phone_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    created: function() {
        if (this.modelValue == '') {
            this.value = {
                country_code: this.$cuztomisable.locations.default_country_code ?? '',
                number: '',
                extension: '',
                mobile: this.isMobile,
                default: this.isDefault,
            };
        }
    },
    computed: {
        value: {
            get: function () {

                return this.modelValue;
            },
            set: function (value) {
                value.mobile = this.isMobile;
                value.default = this.isDefault;
                if (typeof(value.country_code) !== 'undefined' && value.country_code == '') {
                    value.country_code = this.$cuztomisable.locations.default_country_code ?? '';
                }
                this.$emit('update:modelValue', value);
            }
        },
        format: {
            get: function() {
                let code = this.value.country_code;
                let format = null;
                // Iterates through the codes to find the correct phone format
                for (const item of this.$cuztomisable.locations.country_codes) {
                    if (item.value == code) {
                        format = item.format ?? null;
                    }
                }
                return format;
            }
        }
    },
    watch: {
        'value.country_code': {
            handler: function(code) {
                if (code == '') {
                    if (this.value == '' || this.value == null) {
                        this.value = { country_code: '', number: '' };
                    }
                    this.value.country_code = this.$cuztomisable.locations.default_country_code ?? '';
                }
            },
            deep: true,
        },
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
        },
    },
    props: {
        modelValue: { type: [Object, String, Number], default: '' },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        type: { type: String, default: 'input' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        extension: { type: Boolean, default: false },
        isMobile: { type: Boolean, default: false },
        isDefault: { type: Boolean, default: false },
    }
}
</script>