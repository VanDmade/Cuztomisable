<template>
    <div class="cz-carousel" :id="elementId" :style="{ 'width': width }">
        <div class="cz-carousel-container">
            <button @click="prevImage" class="cz-carousel-nav left" :disabled="!hasPrev">‹</button>
            <img :src="images[activeIndex]" class="cz-carousel-image" />
            <button @click="nextImage" class="cz-carousel-nav right" :disabled="!hasNext">›</button>
        </div>
        <div class="cz-carousel-thumbs" v-if="images.length > 1">
            <img v-for="(img, i) in images"
                :key="'thumb-' + i"
                :src="img"
                class="cz-carousel-thumb"
                :class="{ active: i === activeIndex }"
                @click="activeIndex = i" />
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            elementId: 'cz-carousel_'+Math.random().toString(16).slice(2),
            activeIndex: 0
        }
    },
    methods: {
        prevImage: function() {
            if (this.hasPrev) {
                this.activeIndex--;
            }
        },
        nextImage: function() {
            if (this.hasNext) {
                this.activeIndex++;
            }
        },
    },
    computed: {
        hasPrev: function() {
            return this.activeIndex > 0;
        },
        hasNext: function() {
            return this.activeIndex < this.images.length - 1;
        }
    },
    props: {
        images: { type: [Array, Object], default: function() { return []; } },
        width: { type: String, default: '100%' },
    },
}
</script>