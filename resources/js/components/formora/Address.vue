<template>
    <div class="form-floating fm-form-address"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <div class="fm-address-container" v-if="value && typeof value === 'object'">
            <fm-input
                label="Address"
                v-model="value.address"
                type="text"
                :errors="getError('address')"
                @change="clearError('address')"
                :disabled="disabled" />
            <fm-input
                v-if="hasAddressTwo"
                label="Address Two"
                v-model="value.address_two"
                type="text"
                :errors="getError('address_two')"
                @change="clearError('address_two')"
                :disabled="disabled" />
            <fm-input
                v-if="hasAddressThree"
                label="Address Three"
                v-model="value.address_three"
                type="text"
                :errors="getError('address_three')"
                @change="clearError('address_three')"
                :disabled="disabled" />
            <div class="row">
                <div v-if="hasCity" class="col col-md-6 col-12">
                    <fm-input
                        label="City"
                        v-model="value.city"
                        type="text"
                        :errors="getError('city')"
                        @change="clearError('city')"
                        :disabled="disabled" />
                </div>
                <div class="col col-md-6 col-12">
                    <fm-select
                        label="State or Province"
                        v-model="value.state_or_province"
                        :items="statesOrProvinces"
                        :errors="getError('state_or_province')"
                        @change="clearError('state_or_province')"
                        :disabled="disabled"></fm-select>
                </div>
                <div class="col col-md-6 col-12">
                    <fm-input
                        label="ZIP/Postal Code"
                        v-model="value.zip_or_postal_code"
                        type="text"
                        :errors="getError('zip_or_postal_code')"
                        @change="clearError('zip_or_postal_code')"
                        :disabled="disabled" />
                </div>
                <div v-if="$cuztomisable.locations.countries.length > 1" class="col col-md-6 col-12">
                    <fm-select
                        label="Country"
                        v-model="value.country"
                        :items="$cuztomisable.locations.countries ?? []"
                        :errors="getError('country')"
                        @change="clearError('country')"
                        :disabled="disabled"></fm-select>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-address_'+Math.random().toString(16).slice(2),
            errorList: [],
            hasCity: true,
            statesOrProvinces: [],
        }
    },
    methods: {
        getError: function(value) {
            return typeof(this.errorList) !== 'undefined' &&
                typeof(this.errorList[value]) !== 'undefined' ? this.errorList[value] : [];
        },
        clearError: function(value) {
            if (typeof(this.errorList[value]) !== 'undefined') {
                this.errorList[value] = '';
            }
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                if (!value) return;
                value.shipping = this.isShipping;
                value.billing = this.isBilling;
                if (typeof(value.country) !== 'undefined' && value.country == '') {
                    // Defaults the country
                    value.country = this.$cuztomisable.locations.default_country ?? '';
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
        value: {
            immediate: true,
            handler: function(value) {
                if (!value || typeof value !== 'object' || typeof(value.country) == 'undefined') {
                    this.$emit('update:modelValue', {
                        address: '',
                        address_two: '',
                        address_three: '',
                        city: '',
                        state_or_province: '',
                        zip_or_postal_code: '',
                        country: this.$cuztomisable.locations.default_country ?? '',
                        shipping: this.isShipping,
                        billing: this.isBilling,
                    });
                }
            },
            deep: true,
        },
        'value.country': {
            immediate: true,
            handler: function(country) {
                if (this.value != '') {
                    if (country == '') {
                        this.value.country = this.$cuztomisable.locations.default_country;
                    }
                    for (let i = 0; i < this.$cuztomisable.locations.countries.length; i++) {
                        if (this.$cuztomisable.locations.countries[i].value == this.value?.country) {
                            this.statesOrProvinces = this.$cuztomisable.locations.countries[i].states_or_provinces ?? [];
                            this.hasCity = this.$cuztomisable.locations.countries[i].city ?? true;
                            return true;
                        }
                    }
                }
                return false;
            },
            deep: true,
        }
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
        hasAddressTwo: { type: Boolean, default: false },
        hasAddressThree: { type: Boolean, default: false },
        isShipping: { type: Boolean, default: false },
        isBilling: { type: Boolean, default: false },
    }
}
</script>