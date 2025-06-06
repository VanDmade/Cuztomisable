<template>
    <div class="fm-image-container lazy-image-wrapper" :id="elementId">
        <input
            type="file"
            :ref="elementId"
            @change="onFileChange"
            accept="image/*"
            class="form-control d-none" />
        <i v-if="changed()" @click="reset" class="fm-image-reset cursor--pointer material-icons">close</i>
        <div class="image-container" :class="{ 'cursor--pointer': uploader }" @click="triggerFileInput">
            <img
                v-if="visible"
                :src="url"
                :alt="alt"
                :class="[imgClass, 'fade-in', { 'fm-image-hover': uploader }]"
                loading="lazy"
                :ref="elementId+'-img'"
                @load="setImageDimensions" />
            <ul v-if="!hideDetails" class="form-errors fm-form-errors mb-0 mt-1">
                <li v-for="(error, i) in errorList" :key="elementId+'-error-'+i" class="form-error fm-form-error">{{ error }}</li>
            </ul>
            <div v-if="$refs[elementId] != null && details">
                <strong>Dimensions:</strong> {{ width }} x {{ height }} px<br />
                <strong>File Size:</strong> {{ formatedSize }}
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            visible: false,
            elementId: 'fm-image_'+Math.random().toString(16).slice(2),
            url: this.src,
            errorList: [],
            width: 0,
            height: 0,
            size: 0,
        }
    },
    mounted: function() {
        const target = document.getElementById(this.elementId);
        if (!target) return;
        const observer = new IntersectionObserver(([entry], obs) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    this.visible = true;
                }, 50);
                obs.disconnect();
            }
        }, {
            rootMargin: '106px'
        });
        observer.observe(target);
    },
    methods: {
        changed: function() {
            return this.src != this.url;
        },
        setImageDimensions: function() {
            const img = this.$refs[this.elementId+'-img'];
            if (img) {
                this.width = img.naturalWidth;
                this.height = img.naturalHeight;
            }
        },
        onFileChange: function(event) {
            this.errorList = [];
            const file = event.target.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const imageUrl = URL.createObjectURL(file);
            const tempImg = new Image();
            tempImg.onload = () => {
                if (!this.square || tempImg.naturalWidth === tempImg.naturalHeight) {
                    this.size = file.size;
                    this.url = imageUrl;
                    this.width = tempImg.naturalWidth;
                    this.height = tempImg.naturalHeight;
                    this.$emit('update:modelValue', file);
                } else {
                    this.errorList.push('Only square images are allowed.');
                    // Cleans up the image
                    URL.revokeObjectURL(imageUrl);
                }
            };
            tempImg.onerror = () => {
                console.log('Failed to load the image.');
                URL.revokeObjectURL(imageUrl);
            };
            tempImg.src = imageUrl;
        },
        reset: function() {
            this.url = this.src;
            this.errorList = [];
            this.$emit('update:modelValue', '');
        },
        triggerFileInput: function() {
            if (!this.uploader) {
                return false;
            }
            this.$refs[this.elementId].click();
        },
    },
    computed: {
        formatedSize: function() {
            if (this.size < 1024) return `${this.size} bytes`;
            if (this.size < 1024 * 1024) return `${(this.size / 1024).toFixed(2)} KB`;
            return `${(this.size / (1024 * 1024)).toFixed(2)} MB`;
        },
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
        modelValue: { type: [File, String], default: '' },
        src: { type: String, required: true },
        alt: { type: String, default: '' },
        imgClass: { type: String, default: '' },
        uploader: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        details: { type: Boolean, default: false },
        square: { type: Boolean, default: true },
    },
}
</script>
<style scoped>
    .lazy-image-wrapper {
        display: block;
        width: inherit;
        height: inherit;
    }
    .fm-image-reset {
        position: absolute;
        top: 18px;
        right: 18px;
        z-index: 99;
        border-radius: 50px;
        background: #ccc;
        padding: 2px;
        text-align: center;
    }
    .fm-image-reset:hover, .fm-image-reset:focus {
        box-shadow: 0 0 4px rgba(0,0,0,0.15);
        color: #0f0f0f;
    }
    .fm-image-hover:hover {
        box-shadow: 0 0 12px rgba(0,0,0,0.025);
    }
</style>