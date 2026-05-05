<template>
    <div class="fm-form-input"
        :class="{ 'fm-no-label': (label == null || label == '') && !dense, 'form-floating': !notFloating }">
        <select
            v-model="value"
            :id="id"
            class="form-control form-select fm-form-control fm-form-select"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': isEmpty }, inputClass]"
            :disabled="disabled"
            :readonly="readonly"
            @input="errorList = []">
            <option v-if="newEntry" :value="newEntryValue">{{ newEntryText }}</option>
            <option v-if="getItems().length == ''" value="no no no! You cannot select me!" disabled>{{ emptyText }}</option>
            <option
                v-if="!required || isEmpty"
                value=""
                :hidden="required && (!isEmpty || placeholder === null)">{{ placeholder }}</option>
            <option v-for="(item, itemIndex) in getItems()"
                :value="yesNo ? item.value : (typeof(item[inputValue]) == 'undefined' ? item : item[inputValue])">{{ yesNo ? item.text : (typeof(item[inputText]) == 'undefined' ? item : item[inputText]) }}</option>
        </select>
        <label v-if="label != null && label != ''" :for="id" class="form-label fm-form-label" :class="{ 'empty': isEmpty }">{{ label }}</label>
        <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-select_'+Math.random().toString(16).slice(2),
            yesOrNo: [{ value: '1', text: 'Yes'}, { value: '0', text: 'No'}],
            errorList: [],
        }
    },
    methods: {
        getItems: function() {
            return this.yesNo ? this.yesOrNo : this.items;
        },
        isValueEmpty: function(value) {
            if (value == null) {
                return true;
            }
            if (typeof value === 'string') {
                const normalized = value.trim().toLowerCase();
                return normalized === '' || normalized === 'null' || normalized === 'undefined';
            }
            return false;
        }
    },
    computed: {
        isEmpty: function() {
            return this.isValueEmpty(this.value);
        },
        value: {
            get: function () {
                return this.isValueEmpty(this.modelValue) ? '' : this.modelValue;
            },
            set: function (value) {
                if (this.isValueEmpty(value)) {
                    value = '';
                }
                // Allows for ease of creation of method if a new entry is selected and it'll trigger functionality in parent component
                if (this.newEntry && value == this.newEntryValue) {
                    this.$emit('newEntrySelected');
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
        modelValue: { type: [String, Number], default: '' },
        items: { type: [Array, Object], default: [] },
        inputValue: { type: String, default: 'value' },
        inputText: { type: String, default: 'text' },
        label: { type: String, default: null },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        required: { type: Boolean, default: true },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        yesNo: { type: Boolean, default: false },
        notFloating: { type: Boolean, default: false },
        newEntry: { type: Boolean, default: false },
        newEntryText: { type: String, default: 'New Entry' },
        newEntryValue: { type: [String, Number], default: '0' },
        emptyText: { type: String, default: 'Hmm... Something is missing...' },
        placeholder: { type: String, default: null },
        dense: { type: Boolean, default: false },
    }
}
</script>
