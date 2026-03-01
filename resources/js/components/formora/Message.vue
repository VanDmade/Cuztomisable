<template>
    <div class="fm-messages" :style="containerStyle">
        <div v-for="item in messages" :key="item.id" class="fm-message-container">
            <div class="fm-message shadow fade-in" :class="messageClasses(item)">
                <span class="fm-message-text">{{ item.text }}</span>
                <button
                    v-if="item.persistent"
                    type="button"
                    class="fm-message-close"
                    @click="close(item.id)">
                    <span class="material-icons">close</span>
                </button>
            </div>
        </div>
    </div>
</template>
<script>
import notify from '../../utils/notify';

export default {
    computed: {
        messages() {
            return notify.state.items;
        },
        containerStyle() {
            return {
                minWidth: this.minWidth || notify.state.settings.minWidth,
                maxWidth: this.maxWidth || notify.state.settings.maxWidth,
            };
        },
    },
    methods: {
        messageClasses(item) {
            const color = String(item?.color ?? 'success').toLowerCase();
            const classes = [`fm-message-${color}`];

            if (color === 'danger') {
                classes.push('fm-message-error');
            }

            return classes;
        },
        close(id) {
            notify.remove(id);
        },
    },
    watch: {
        message: {
            handler: function(value) {
                if (!value || !value.text) {
                    return;
                }
                const totalWords = value.text.split(' ').length;
                const averageReadSpeed = Math.ceil(totalWords / 5) * 2000;
                const duration = this.length > averageReadSpeed ? this.length : averageReadSpeed;

                notify.push(value, {
                    duration,
                    color: value.color,
                    persistent: value.persistent,
                });
            },
            deep: true,
        }
    },
    props: {
        message: { type: [Array, Object], default: [] },
        length: { type: Number, default: 4000 },
        minWidth: { type: [Number, String], default: null },
        maxWidth: { type: [Number, String], default: null },
    }
}
</script>