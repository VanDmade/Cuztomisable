<template>
    <div class="fm-form-input"
        :class="{ 'fm-no-label': label == null || label == '' }">
        <div class="form-check" v-for="(item, radioIndex) in items" :key="radioIndex">
            <input
                v-model="value"
                type="radio"
                :name="id"
                :id="id+'-id_'+radioIndex"
                class="form-check-input"
                :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
                :value="item[inputValue]"
                :readonly="readonly"
                @input="errorList = []">
            <label class="form-check-label" :for="id+'-id_'+radioIndex">{{ item[inputText] }}</label>
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
            id: 'fm-radio_'+Math.random().toString(16).slice(2),
            errorList: [],
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
    },
    props: {
        modelValue: { type: [String, Number], default: '' },
        items: { type: [Array, Object], default: [] },
        inputValue: { type: String, default: 'value' },
        inputText: { type: String, default: 'text' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
    }
}
</script>