<template>
    <div class="form-floating cz-form-phone"
        :class="{ 'cz-no-label': label == null || label == '' }">
        <div class="cz-flex-container" v-if="value && typeof value === 'object'">
            <cz-select
                v-if="$cuztomisable.locations.country_codes.length > 1"
                v-model="value.country_code"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.country_code == '' || value.country_code == null },
                    inputClass,
                    'cz-form-phone-country-code-input'
                ]"
                :items="$cuztomisable.locations.country_codes"
                :disabled="disabled"
                :readonly="readonly"
                hideDetails />
            <cz-input
                :label="label"
                v-model="value.number"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.number == '' || value.number == null,
                    'cz-form-phone-normal-borders-left': $cuztomisable.locations.country_codes.length <= 1,
                    'cz-form-phone-normal-borders-right': !extension },
                    inputClass,
                    'cz-form-phone-input'
                ]"
                max="15"
                type="number"
                :format="format"
                :disabled="disabled"
                :readonly="readonly"
                @change="errorList = []"
                hideDetails />
            <cz-input
                v-if="extension"
                label="Ext."
                v-model="value.extension"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value.extension == '' || value.extension == null },
                    inputClass,
                    'cz-form-phone-extension-input'
                ]"
                type="number"
                :disabled="disabled"
                :readonly="readonly"
                hideDetails />
        </div>
        <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'cz-phone_'+Math.random().toString(16).slice(2),
            errorList: [],
        }
    },
    mounted: function() {
        this.ensureDefaultCountryCode();
    },
    methods: {
        defaultCountryCode: function() {
            return this.$cuztomisable?.locations?.default_country_code ?? '';
        },
        ensureDefaultCountryCode: function() {
            const value = this.modelValue;
            if (!value || typeof value !== 'object') {
                return;
            }
            if (!value.country_code) {
                this.$emit('update:modelValue', {
                    ...value,
                    country_code: this.defaultCountryCode(),
                    mobile: this.isMobile,
                    default: this.isDefault,
                });
            }
        },
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                if (!value) return;
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
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
        },
        value: {
            immediate: true,
            handler: function(value) {
                if (!value || typeof value !== 'object') {
                    this.$emit('update:modelValue', {
                        country_code: this.defaultCountryCode(),
                        number: '',
                        extension: '',
                        mobile: this.isMobile,
                        default: this.isDefault,
                    });
                    return;
                }
                if (!value.country_code) {
                    this.$emit('update:modelValue', {
                        ...value,
                        country_code: this.defaultCountryCode(),
                        mobile: this.isMobile,
                        default: this.isDefault,
                    });
                }
            },
            deep: true,
        },
        '$cuztomisable.locations.default_country_code': {
            immediate: true,
            handler: function() {
                this.ensureDefaultCountryCode();
            },
        },
        'value.country_code': {
            handler: function(code) {
                if (!code) {
                    if (this.value == '' || this.value == null) {
                        this.value = { country_code: '', number: '' };
                    }
                    this.value.country_code = this.defaultCountryCode();
                }
            },
            deep: true,
        },
    },
    props: {
        modelValue: { type: [Object, String, Number], default: () => ({}) },
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