<template>
    <div class="fm-form-input">
        <div class="input-group">
            <div class="form-floating fm-form-input" :class="{ 'fm-no-label': label == null || label == '' }">
                <input
                    v-model="item.name"
                    type="input"
                    :ref="id"
                    :id="id"
                    class="form-control fm-form-control"
                    :class="[{ 'is-invalid': errorList.length > 0, 'empty': value == '' || value == null }, inputClass]"
                    :disabled="disabled"
                    :readonly="readonly"
                    :placeholder="placeholder"
                    @input="errorList = []; search = true;"
                    v-on:keyup.enter="add"
                    @blur="blur">
                <label v-if="label != null && label != ''" :for="id" class="form-label fm-form-label">{{ label }}</label>
                <div class="fm-tag-selector shadow" v-show="search && searchList().length > 0 && item.name != ''">
                    <div class="fm-tags mb-0 mt-0">
                        <div class="fm-tag-container" v-for="(tag, index) in searchList()" :key="id + '_select_' + index">
                            <span class="fm-tag"
                                @click="addListItem(tag)"
                                :style="{ background: tag.color, color: textColor(tag.color) }">{{ tag.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <input
                type="color"
                v-model="item.color"
                class="form-control form-control-color fm-form-control-color"
                @change="$refs[id].focus()">
        </div>
        <p class="notes mb-2 pl-1">Press enter once you have entered the name of the tag.</p>
        <ul v-if="!hideDetails && errorList.length > 0" class="form-errors fm-form-errors mb-1">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
        </ul>
        <div class="fm-tags mb-2">
            <span class="input-group" v-for="(tag, index) in value" :key="id + '_' + index">
                <span class="fm-tag"
                    :class="{ 'fm-tag-existing': tag.id.toString().indexOf('NEW-') }"
                    @click="edit(tag)"
                    :style="{ background: tag.color, color: textColor(tag.color) }">{{ tag.name }}</span>
                <span class="material-icons" @click="remove(index)">close</span>
            </span>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-tags_'+Math.random().toString(16).slice(2),
            item: {
                id: 'NEW-0',
                color: this.generateColor(),
                name: '',
            },
            counter: 0,
            errorList: [],
        }
    },
    mounted: function() {

    },
    methods: {
        add: function() {
            this.errorList = [];
            // Validates that the tag meets the appropriate character length
            if (this.item.name.length < this.minLength) {
                this.errorList.push(`The tag must be at least ${this.minLength} characters long.`);
                return false;
            }
            if (this.item.name.length > this.maxLength) {
                this.errorList.push(`The tag must be at most ${this.maxLength} characters long.`);
                return false;
            }
            let item = {
                id: JSON.parse(JSON.stringify(this.item.id)),
                color: JSON.parse(JSON.stringify(this.item.color == '' ? this.generateColor() : this.item.color)),
                name: JSON.parse(JSON.stringify(this.item.name)),
            };
            this.item = {
                id: 'NEW-'+(++this.counter),
                color: this.generateColor(),
                name: '',
            };
            for (let i = 0; i < this.value.length; i++) {
                if (this.value[i].name.toLowerCase() == item.name.toLowerCase() || this.value[i].id == item.id) {
                    let previousValue = item.name;
                    // Moves the value to the front of the list to allow for ease of seeing the addition
                    if (this.value[i].id == item.id && previousValue != this.value[i].id) {
                        item.id = 'NEW-NAME-'+(++this.counter);
                    }
                    this.value.splice(i, 1);
                    this.value.unshift(item);
                    return;
                }
            }
            // Appends the list item to the model
            this.value.push(item);
        },
        edit: function(tag) {
            if (tag.id.toString().indexOf('NEW-') == 0) {
                this.item = JSON.parse(JSON.stringify(tag));
                this.$refs[id].focus();
            }
        },
        remove: function(index) {
            this.value.splice(index, 1);
        },
        addListItem: function(item) {
            this.item = JSON.parse(JSON.stringify(item));
            this.item.id = 'EXISTING-'+this.item.id;
            this.add();
            this.search = false;
        },
        blur: function() {
            setTimeout(() => {
                this.search = false;
            }, 150);
        },
        searchList: function() {
            let list = [];
            for (let i = 0; i < this.list.length; i++) {
                if (this.list[i].name.toLowerCase().indexOf(this.item.name.toLowerCase()) !== -1) {
                    list.push(this.list[i]);
                }
            }
            return list;
        },
        generateColor: function() {
            const colorGenerator = () => Math.floor(256 * Math.random());
            const rgbToHex = (value) => {
                let hex = value.toString(16);
                return (hex.length == 1 ? '0' : '') + hex;
            }
            return `#${rgbToHex(colorGenerator())}${rgbToHex(colorGenerator())}${rgbToHex(colorGenerator())}`;
        },
        textColor: function(color) {
            color = color.substring(1);
            let r = parseInt(color.substring(0, 2), 16); // hexToR
            let g = parseInt(color.substring(2, 4), 16); // hexToG
            let b = parseInt(color.substring(4, 6), 16); // hexToB
            return ((r * 0.299) + (g * 0.587) + (b * 0.114)) <= 186 ? 'white' : 'black';
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
        modelValue: { type: Array, default: [] },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        minLength: { type: Number, default: 4 },
        maxLength: { type: Number, default: 32 },
        list: { type: Array, default: [] },
    }
}
</script>