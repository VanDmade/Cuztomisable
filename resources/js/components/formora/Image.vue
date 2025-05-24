<template>
    <div class="fm-image-container lazy-image-wrapper" :id="elementId">
        <img
            v-if="visible"
            :src="src"
            :alt="alt"
            :class="[imgClass, 'fade-in']"
            loading="lazy" />
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            visible: false,
            elementId: 'fm-image_'+Math.random().toString(16).slice(2),
        }
    },
    mounted: function() {
        const target = document.getElementById(this.elementId)
        if (!target) return

        const observer = new IntersectionObserver(([entry], obs) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    this.visible = true;
                }, 50);
                obs.disconnect()
            }
        }, {
            rootMargin: '106px'
        });
        observer.observe(target)
    },
    props: {
        src: { type: String, required: true },
        alt: { type: String, default: '' },
        imgClass: { type: String, default: '' }
    },
}
</script>
<style scoped>
    .lazy-image-wrapper {
        display: block;
        width: inherit;
        height: inherit;
    }
</style>