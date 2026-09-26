<template>
    <div class="cz-form-input"
        :class="{ 'form-floating': label != null && label != '', 'cz-no-label': label == null || label == '', 'cz-color-input': type == 'color' }">
        <input
            v-model="value"
            :type="effectiveType"
            :id="id"
            class="form-control cz-form-control"
            :class="[{
                'is-invalid': errorList.length > 0,
                'empty': value === '' || value === null || value === undefined,
                'cz-form-control--has-toggle': type == 'password',
            }, inputClass]"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :maxlength="max != null ? max : 1000000"
            :autocomplete="autocomplete"
            :style="append ? { paddingRight: appendWidth + 'px' } : null"
            @input="errorList = []">
        <span v-if="append" ref="append" class="cz-form-input-append">{{ append }}</span>
        <button
            v-if="type == 'password'"
            type="button"
            class="cz-form-input-toggle"
            tabindex="-1"
            @click="showPassword = !showPassword">
            <span class="material-icons">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
        </button>
        <label v-if="label != null && label != ''" :for="id" class="cz-form-label">{{ label }}</label>
        <div v-if="!hideDetails || link" class="cz-form-input-footer">
            <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
                <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
            </ul>
            <router-link v-if="link" :to="link" class="cz-form-input-link button--link">{{ linkText }}</router-link>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'cz-input_'+Math.random().toString(16).slice(2),
            errorList: [],
            showPassword: false,
            appendWidth: 0,
        }
    },
    mounted: function() {
        this.measureAppend();
    },
    methods: {
        measureAppend: function() {
            // Pads the input so typed text never runs underneath the appended text
            this.$nextTick(() => {
                this.appendWidth = this.$refs.append ? this.$refs.append.offsetWidth + 20 : 0;
            });
        },
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
        effectiveType: function() {
            if (this.type == 'password') {
                return this.showPassword ? 'text' : 'password';
            }
            return this.type == 'number' ? 'input' : this.type;
        },
        value: {
            get: function () {
                return this.formatValue(this.modelValue);
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
        append: function() {
            this.measureAppend();
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
        autocomplete: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        format: { type: String, default: null },
        hideDetails: { type: Boolean, default: false },
        max: { type: [String, Number], default: null },
        link: { type: [String, Object], default: null },
        linkText: { type: String, default: '' },
        // Text shown inside the end of the input, e.g. a unit like "mi"
        append: { type: String, default: null },
    }
}
</script>