<template>
    <div class="fm-messages">
        <div v-for="(item, index) in messages" class="fm-message-container">
            <div v-if="item.text != ''"
                class="fm-message shadow fade-in"
                :class="item.error ? 'fm-message-error' : 'fm-message-success'">{{ item.text }}</div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            messages: [],
            template: {
                id: 0,
                text: '',
                error: false,
            },
            counter: 1,
        }
    },
    methods: {

    },
    watch: {
        message: {
            handler: function(value) {
                if (value.text == '') {
                    return;
                }
                let template = JSON.parse(JSON.stringify(this.template));
                template.id = value.id;
                template.text = value.text;
                template.error = value.error;
                this.messages.push(template);
                // Counts the words to add to or remove a few seconds to make sure the user can read the message.
                let totalWords = value.text.split(' ').length;
                // The average reader can read 3-4 words per second, so 5 should be more than enough.
                let averageReadSpeed = Math.ceil(totalWords / 5) * 2000;
                setTimeout(() => {
                    for (var i = 0; i < this.messages.length; i++) {
                        if (this.messages[i].id == template.id) {
                            this.messages[i].text = '';
                        }
                    }
                }, this.length > averageReadSpeed ? this.length : averageReadSpeed);
            },
            deep: true,
        }
    },
    props: {
        message: { type: [Array, Object], default: [] },
        length: { type: Number, default: 4000 },
        error: { type: Boolean, default: false },
    }
}
</script>