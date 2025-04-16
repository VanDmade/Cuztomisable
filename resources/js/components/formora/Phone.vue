<template>
    <div class="form-floating fm-form-phone"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <div class="fm-flex-container" v-if="value != ''">
            <fm-select
                v-if="$cuztomisable.locations.country_codes.length > 1"
                v-model="value.country_code"
                :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass, 'fm-form-country-code']"
                :items="$cuztomisable.locations.country_codes"
                :disabled="disabled"
                :readonly="readonly"
                hideDetails />
            <fm-input
                :label="label"
                v-model="value.number"
                :class="[{
                    'is-invalid': errorList.length > 0,
                    'empty': value == '' || value == null,
                    'fm-form-phone-normal-borders': $cuztomisable.locations.country_codes.length <= 1 },
                    inputClass,
                    'fm-form-phone-input'
                ]"
                max="15"
                type="number"
                :format="format"
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
            format: null,
            errorList: [],
        }
    },
    created: function() {
        if (this.modelValue == '') {
            this.value = {
                id: null,
                country_code: this.$cuztomisable.locations.default_country_code ?? '',
                number: '',
                extension: '',
                mobile: 0,
                default: 0,
            };
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
        'value.country_code': {
            immediate: true,
            handler: function(code) {
                this.format = null;
                for (let i = 0; i < this.$cuztomisable.locations.country_codes.length; i++) {
                    if (code == this.$cuztomisable.locations.country_codes[i].value) {
                        this.format = this.$cuztomisable.locations.country_codes[i].format ?? null;
                    }
                }
            }
        },
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
        },
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
        hideDetails: { type: Boolean, default: false },
    }
}
</script>
<style type="text/css">
    .fm-flex-container {
        display: flex;
        align-items: center;
    }
    .fm-form-country-code {
        width: 120px;
    }
    .fm-form-country-code select {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .fm-form-phone-input {
        flex: 1;
    }
    .fm-form-phone-input input {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .fm-form-phone-input.fm-form-phone-normal-borders input {
        border-radius: 0.375rem;
    }
</style>