<template>
    <div class="form-floating fm-form-address"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <div class="fm-address-container" v-if="value != ''">
            <fm-input
                label="Address"
                v-model="modelValue.address"
                type="text"
                :errors="getError('address')"
                :disabled="disabled" />
            <fm-input
                v-if="$cuztomisable.locations.addresses.address_two"
                label="Address Two"
                v-model="modelValue.address_two"
                type="text"
                :errors="getError('address_two')"
                :disabled="disabled" />
            <fm-input
                v-if="$cuztomisable.locations.addresses.address_three"
                label="Address Three"
                v-model="modelValue.address_three"
                type="text"
                :errors="getError('address_three')"
                :disabled="disabled" />
            <div class="row">
                <div v-if="hasCity" class="col col-md-6 col-12">
                    <fm-input
                        label="City"
                        v-model="modelValue.city"
                        type="text"
                        :errors="getError('city')"
                        :disabled="disabled" />
                </div>
                <div class="col col-md-6 col-12">
                    <fm-select
                        label="State or Province"
                        v-model="modelValue.state"
                        :items="statesOrProvinces"
                        :errors="getError('state')"
                        :disabled="disabled"></fm-select>
                </div>
                <div class="col col-md-6 col-12">
                    <fm-input
                        label="ZIP/Postal Code"
                        v-model="modelValue.code"
                        type="text"
                        :errors="getError('code')"
                        :disabled="disabled" />
                </div>
                <div v-if="$cuztomisable.locations.countries.length > 1" class="col col-md-6 col-12">
                    <fm-select
                        label="Country"
                        v-model="modelValue.country"
                        :items="$cuztomisable.locations.countries ?? []"
                        :errors="getError('country')"
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
    created: function() {
        if (this.modelValue == '') {
            this.value = {
                address: '',
                address_two: '',
                address_three: '',
                city: '',
                state: '',
                code: '',
                country: this.$cuztomisable.locations.default_country ?? '',
            };
        }
    },
    methods: {
        getError: function(value) {
            return typeof(this.errors[this.name]) !== 'undefined' &&
                typeof(this.errors[this.name][value]) !== 'undefined' ? this.errors[this.name][value] : [];
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
        'value.country': {
            immediate: true,
            handler: function(country) {
                for (let i = 0; i < this.$cuztomisable.locations.countries.length; i++) {
                    if (this.$cuztomisable.locations.countries[i].value == this.value.country) {
                        this.statesOrProvinces = this.$cuztomisable.locations.countries[i].states_or_provinces ?? [];
                        this.hasCity = this.$cuztomisable.locations.countries[i].city ?? true;
                        return true;
                    }
                }
                return false;
            }
        }
    },
    props: {
        modelValue: { type: [Object, Array, String, Number], default: '' },
        name: { type: String, default: 'address' },
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