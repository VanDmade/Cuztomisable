<template>
    <div class="form-floating fm-form-input">
        <div v-if="image" class="fm-form-uploader" @click="upload(false)">
            <img v-show="!multiple || total <= 1" :src="default" :id="id" class="fm-image fm-image-preview" :class="{ 'fm-image-cursor': !selected }">
            <div :id="id + '-container'" class="fm-image-container" :class="{ 'scroll': total > 1 }"></div>
            <div v-if="!selected" class="fm-form-upload-text">
                Browse for File Upload
                <ul v-if="!hideDetails && errorList.length > 0" class="form-errors fm-form-errors mb-2">
                    <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
                </ul>
            </div>
            <div v-else-if="!multiple || total <= 1" class="fm-form-upload-clear" @click="clear"><span class="material-icons">close</span></div>
        </div>
        <div class="d-grid" v-if="selected">
            <button type="button" class="btn btn-primary btn-sm mt-3" block @click="upload(true)">Upload</button>
        </div>
        <div v-if="description != null" class="fm-upload-description">{{ description }}</div>
        <input v-show="false" type="file" :ref="'uploader-'+id" :id="'uploader-'+id" @change="change" :multiple="multiple">
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-file_'+Math.random().toString(36).slice(2),
            key: 0,
            default: this.$url+'default.png',
            selected: false,
            details: {},
            total: 0,
            loading: false,
            errorList: [],
            initialValue: [],
        }
    },
    mounted: function() {
        if (this.value == null) {
            return;
        }
        if (typeof(this.value.id) !== 'undefined') {
            this.value = [this.value];
        }
        setTimeout(() => {
            var container = document.getElementById(this.id+'-container');
            var previewImage = document.getElementById(this.id);
            if (this.value.length == 1) {
                previewImage.src = this.value[0].path;
            } else {
                for (let i = 0; i < this.value.length; i++) {
                    let [imageContainer, image, clear] = this.setupImage(this.value[i], i);
                    imageContainer.appendChild(image);
                    imageContainer.appendChild(clear);
                    container.appendChild(imageContainer);
                }
            }
            if (this.value.length > 0) {
                this.total = this.value.length;
                this.selected = true;
            }
        }, 50);
    },
    methods: {
        upload: function(ignoreSelected = false) {
            if (!this.selected || ignoreSelected == true) {
                this.errorList = [];
                this.$refs['uploader-'+this.id].click();
            }
        },
        view: function(event) {
            if (!this.image) {
                return false;
            }
            this.errorList = [];
            this.loading = true;
            let input = event.target;
            var container = document.getElementById(this.id+'-container');
            var previewImage = document.getElementById(this.id);
            var _this = this;
            if (input.files && input.files.length > 0) {
                this.total = input.files.length;
                let reader = new FileReader();
                function readFile(index) {
                    if (index >= input.files.length) return;
                    reader.onload = function(event) {
                        setTimeout(() => {
                            let [imageContainer, image, clear] = _this.setupImage(event, index);
                            let extension = input.files[index].name.split('.');
                            // Gets the details for the specific image to do appropraite comparisons
                            try {
                                // Creates the details object for this specific image, if needed, it can be setup to be used.
                                let details = {
                                    name: input.files[index].name,
                                    type: input.files[index].type,
                                    extension: extension[extension.length - 1],
                                    width: image.naturalWidth,
                                    height: image.naturalHeight,
                                    ratio: _this.reduce((image.naturalWidth / image.naturalHeight * 10), 10),
                                    size: input.files[index].size,
                                };
                                // Checks to make sure the type is correctly set
                                if (_this.accept.split(',').indexOf(details.type) == -1) {
                                    throw new Error(`The file\'s extension must be one of the following types: ${_this.accept}.`);
                                }
                                // Checks to make sure the image uploaded is a square
                                if (_this.square && image.naturalHeight != image.naturalWidth) {
                                    throw new Error('The image selected must be a square.');
                                }
                                // Checks to make sure the ratio is correctly uploaded
                                if (_this.ratio != details.ratio && _this.ratio != null) {
                                    throw new Error(`The image\'s ratio does not match ${_this.ratio}.`);
                                }
                                // Determines how the image should be displayed
                                if (_this.multiple && _this.total > 1) {
                                    imageContainer.appendChild(image);
                                    imageContainer.appendChild(clear);
                                    container.appendChild(imageContainer);
                                } else {
                                    previewImage.src = image.src;
                                }
                            } catch ({ name, message }) {
                                if (_this.total == 1) {
                                    _this.clear();
                                } else {
                                    _this.value.splice(index, 1);
                                    _this.total--;
                                }
                                _this.errorList = [message];
                            }
                            readFile(index+1);
                        }, 50 * (index + 1));
                    }
                    reader.readAsDataURL(input.files[index]);
                }
                readFile(0);
            }
            setTimeout(() => {
                this.loading = false;
            }, 1000);
        },
        change: function(event) {
            this.selected = true;
            this.view(event);
            let value = this.value != null ? Array.from(this.value) : [];
            let files = Array.from(event.target.files); 
            if (!this.multiple) {
                value = [];
            }
            // Appends the files to the current list
            for (let i = 0; i < files.length; i++) {
                value.push({
                    id: 'NEW-' + (this.key++),
                    file: files[i],
                    path: null,
                });
            }
            this.total = value.length;
            this.value = value;
        },
        clear: function() {
            this.errorList = [];
            this.value = '';
            let image = document.getElementById(this.id);
            image.src = this.default;
            this.details = {};
            setTimeout(() => {
                this.selected = false;
            }, 10);
        },
        setThumbnail: function(event) {
            // Makes sure not to mix up click functionality
            if (event.target.className.indexOf('fm-form-upload-clear') != -1 ||
                event.target.className.indexOf('material-icons') != -1) {
                return;
            }
            let containers = document.querySelectorAll('.fm-form-multiple-image-container');
            const target = event.target.closest('.fm-form-multiple-image-container');
            let index = Array.from(containers).indexOf(target);
            document.getElementById(this.id+'-container').prepend(target);
            // Removes the current file from it's current location and then adds it to the front
            let thumbnail = this.value.splice(index, 1);
            this.value.unshift(thumbnail[0]);
        },
        setupImage: function(event, index = 0) {
            let _this = this;
            // Creates hte container for the clear and image
            let imageContainer = document.createElement('div');
            imageContainer.className = 'fm-form-multiple-image-container';
            if (_this.thumbnail) {
                // Adds an event to allow the user to select a new thumbnail without re-uploading
                imageContainer.addEventListener('click', (event) => {
                    _this.setThumbnail(event);
                });
            }
            // Creates the actual image to be displayed
            let image = document.createElement('img');
            image.className = ['fm-image',
                index == 0 && _this.thumbnail ? ' fm-thumbnail-image' : '',
                _this.thumbnail ? ' fm-thumbnail-selector' : '',
            ].join(' ');
            // Determines if the event is the object from the server or actual DOM event
            image.src = typeof(event.id) != 'undefined' ? event.path : event.target.result;
            // Creates the icon to be clicked
            let icon = document.createElement('span');
            icon.className = 'material-icons';
            icon.textContent = 'close';
            // Creates the icon to be used to clear
            let clear = document.createElement('span');
            clear.className = 'fm-form-upload-clear ' + index;
            clear.appendChild(icon);
            clear.addEventListener('click', (event) => {
                _this.remove(event);
            });
            return [imageContainer, image, clear];
        },
        remove: function(event) {
            let buttons = document.querySelectorAll('.fm-form-upload-clear');
            const target = event.target.closest('.fm-form-upload-clear');
            let index = Array.from(buttons).indexOf(target);
            // Removes the image
            target.closest('.fm-form-multiple-image-container').remove();
            this.value.splice(index, 1);
            this.total--;
            if (this.total == 1) {
                // Gets the other containers to find the last image
                let containers = document.getElementsByClassName('fm-form-multiple-image-container');
                if (containers.length == 1) {
                    let image = containers[0].querySelector('img');
                    let previewImage = document.getElementById(this.id);
                    containers[0].remove();
                    previewImage.src = image.src;
                }
            } else if (this.total == 0) {
                this.selected = false;
            }
        },
        reduce: function (numerator, denominator){
            // Finds the greatest common denominator
            var gcd = function gcd(numerator, denominator) {
                return denominator ? gcd(denominator, numerator % denominator) : numerator;
            };
            gcd = gcd(numerator, denominator);
            // Outputs the ratio / fraction for use an comparison
            return (numerator / gcd) + '/' + (denominator / gcd);
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
        modelValue: {
            immediate: true,
            handler: function(value) {
                let image = document.getElementById(this.id);
            },
            deep: true,
        },
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
            deep: true,
        },
    },
    props: {
        modelValue: { type: [File, String, Array, Object], default: '' },
        label: { type: String, default: null },
        accept: { type: String, default: 'image/png,image/jpeg,image/jpg' },
        image: { type: Boolean, default: true },
        square: { type: Boolean, default: true },
        ratio: { type: String, default: null },
        multiple: { type: Boolean, default: false },
        errors: { type: [Array, Object], default: [] },
        thumbnail: { type: Boolean, default: false },
        description: { type: String, default: null },
        disabled: { type: Boolean, default: false },
        placeholder: { type: String, default: '' },
        hideDetails: { type: Boolean, default: false },
    }
}
</script>