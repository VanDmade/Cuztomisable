<template>
    <div class="fm-form-multi-entries">
        <div class="fm-form-multi-entries-label">
            <div class="mt-4">
                <h5 v-if="label != null" class="mt-0" :class="{ 'mb-4': notes == null, 'mb-1': notes != null }">{{ label }}</h5>
                <p v-if="notes != null" class="notes">{{ notes }}</p>
            </div>
            <div class="fm-form-multi-entries-add">
                <button type="button" @click="add" class="btn btn-primary btn-sm mt-4">{{ addLabel }}</button>
            </div>
        </div>
        <div class="fm-form-multi-entry" v-for="(mainItem, multiIndex) in value">
            <div v-for="(item, alternativeIndex) in ([mainItem].concat(typeof(mainItem.alternatives) !== 'undefined' ? mainItem.alternatives : []))">
                <div :class="{ 'input-group': !breakpoint('sm') }">
                    <span v-if="steps && !breakpoint('sm')" class="input-group-text">{{ item.steps == null ? (multiIndex + 1) : item.steps }}</span>
                    <slot :value="value" :index="multiIndex" :alternativeIndex="alternativeIndex == 0 ? null : (alternativeIndex - 1)" name="entry"></slot>
                    <button
                        v-if="value.length > 1 && reorganize && alternativeIndex == 0"
                        type="button"
                        :disabled="multiIndex == 0"
                        class="btn fm-btn-reorder fm-btn-reorder-upward btn-outline-secondary px-1"
                        @click="reorderUp(multiIndex)"><span class="material-icons">arrow_upward</span></button>
                    <button
                        v-if="value.length > 1 && reorganize && alternativeIndex == 0"
                        type="button"
                        :disabled="multiIndex == value.length - 1"
                        class="btn fm-btn-reorder fm-btn-reorder-downward btn-outline-secondary px-1"
                        @click="reorderDown(multiIndex)"><span class="material-icons">arrow_downward</span></button>
                    <div class="fm-button-container" :class="{ 'd-grid mt-4': breakpoint('sm'), 'fm-button-remove-border-radius': settings }">
                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="removeAll == false && value.length == 1 && alternativeIndex == 0"
                            @click="remove(multiIndex, alternativeIndex == 0 ? null : (alternativeIndex - 1))">{{ isAskingToRemove(multiIndex, alternativeIndex == 0 ? null : (alternativeIndex - 1)) ? 'Are your sure?' : removeLabel }}</button>
                    </div>
                    <slot :value="value" :index="multiIndex" :alternativeIndex="alternativeIndex == 0 ? null : (alternativeIndex - 1)" name="settings"></slot>
                </div>
                <div :class="{ 'fm-form-entry-flex': !breakpoint('sm') }" class="mb-2" style="min-height: 18px;">
                    <p class="fm-form-entry-output notes pl-1 mb-0">
                        <slot :value="value" :index="multiIndex" :alternativeIndex="alternativeIndex == 0 ? null : (alternativeIndex - 1)" name="output"></slot>
                    </p>
                    <p v-if="slugs" class="notes mb-0 pl-1">{{ item.slug }}</p>
                </div>
                <ul v-if="!hideDetails && (hideDetailsWhenNoErrors && getErrors(multiIndex).length > 0)" class="form-errors fm-form-errors mb-2">
                    <li v-for="(error, i) in getErrors(multiIndex)"
                        :key="id+'-error-'+i"
                        class="form-error fm-form-error">{{ error }}</li>
                </ul>
            </div>
        </div>
        <div v-if="breakpoint('sm')" class="fm-button-container d-grid">
            <button v-if="addButton == true" type="button" @click="add" class="btn btn-primary">{{ addLabel }}</button>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-multi_'+Math.random().toString(16).slice(2),
            errorList: [],
            asking: [],
            counter: 0,
        }
    },
    mounted: function() {
        // Adds one to begin with
        if (this.startWithOne && this.value.length == 0) {
            this.add();
        }
    },
    methods: {
        add: function() {
            this.counter++;
            var template = JSON.parse(JSON.stringify(this.template));
            template.id = 'NEW-'+this.counter;
            template.slug = 'slug-'+Math.random().toString(16).slice(10);
            this.value.push(template);
            this.$emit('add');
        },
        remove: function(index, alternativeIndex = null) {
            if (this.removeAll == false && this.value.length == 1 && alternativeIndex == null) {
                return false;
            }
            // Clears the errors
            if (typeof(this.errorList[index]) != 'undefined') {
                this.errorList[index] = [];
            }
            let key = index + (alternativeIndex != null ? ('-' + alternativeIndex) : '');
            if (this.isAskingToRemove(index, alternativeIndex)) {
                this.asking[key] = false;
                this.fullyRemove(index, alternativeIndex);
            } else {
                this.asking[key] = true;
                setTimeout(() => {
                    this.asking[key] = false;
                }, 2500);
            }
        },
        isAskingToRemove: function(index, alternativeIndex = null) {
            let key = index + (alternativeIndex != null ? ('-' + alternativeIndex) : '');
            return typeof(this.asking[key]) !== 'undefined' ? this.asking[key] : false; 
        },
        fullyRemove: function(index, alternativeIndex = null) {
            if (this.removeAll == false && this.value.length == 1 && alternativeIndex == null) {
                return false;
            }
            if (alternativeIndex != null && typeof(this.value[index].alternatives) !== 'undefined') {
                this.value[index].alternatives.splice(alternativeIndex, 1);
            } else {
                this.value.splice(index, 1);
            }
            this.$emit('remove');
        },
        reorderUp: function(index) {
            if (index == 0) {
                return;
            }
            let current = this.value[index];
            let before = this.value[index-1];
            this.value[index] = before;
            this.value[index-1] = current;
        },
        reorderDown: function(index) {
            if (index == this.value.length - 1) {
                return;
            }
            let current = this.value[index];
            let before = this.value[index+1];
            this.value[index] = before;
            this.value[index+1] = current;
        },
        getErrors: function(index, key = '') {
            return typeof(this.errorList[index]) !== 'undefined' ? this.errorList[index + (key == '' ? '' : ('.'+key))] : [];
        },
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
        modelValue: { type: Array, default: [] },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        hideDetailsWhenNoErrors: { type: Boolean, default: false },
        removeAll: { type: Boolean, default: false },
        startWithOne: { type: Boolean, default: true },
        label: { type: String, default: null },
        notes: { type: String, default: null },
        addLabel: { type: String, default: 'Add' },
        addButton: { type: Boolean, default: true },
        removeLabel: { type: String, default: 'Remove' },
        slugs: { type: Boolean, default: false },
        steps: { type: Boolean, default: false },
        reorganize: { type: Boolean, default: true },
        settings: { type: Boolean, default: false },
        template: { type: Object, default: { id: '', value: '', slug: '' } },
    }
}
</script>