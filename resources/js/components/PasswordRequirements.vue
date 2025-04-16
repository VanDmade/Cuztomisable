<template>
    <div id="password-requirements" :class="completed ? 'completed' : ''">
        <h5>Password Requirements</h5>
        <div v-if="minimum != null" class="requirement" :class="checked('minimum') ? 'checked' : ''">
            <span class="material-icons requirement-check">{{ checked('minimum') ? 'check_circle_outline' : 'radio_button_unchecked' }}</span>
            <span class="requirement-title">{{ minimum }} Minimum Characters</span>
        </div>
        <div v-if="maximum != null && maximum != 0" class="requirement" :class="checked('maximum') ? 'checked' : ''">
            <span class="material-icons requirement-check">{{ checked('maximum') ? 'check_circle_outline' : 'radio_button_unchecked' }}</span>
            <span class="requirement-title">{{ maximum }} Maximum Characters</span>
        </div>
        <div v-if="special != null && special != 0" class="requirement" :class="checked('special') ? 'checked' : ''">
            <span class="material-icons requirement-check">{{ checked('special') ? 'check_circle_outline' : 'radio_button_unchecked' }}</span>
            <span class="requirement-title">{{ special }} Special Characters</span>
        </div>
        <div v-if="uppercase != null && uppercase != 0" class="requirement" :class="checked('uppercase') ? 'checked' : ''">
            <span class="material-icons requirement-check">{{ checked('uppercase') ? 'check_circle_outline' : 'radio_button_unchecked' }}</span>
            <span class="requirement-title">{{ uppercase }} Uppercase Characters</span>
        </div>
        <div v-if="numbers != null && numbers != 0" class="requirement" :class="checked('numbers') ? 'checked' : ''">
            <span class="material-icons requirement-check">{{ checked('numbers') ? 'check_circle_outline' : 'radio_button_unchecked' }}</span>
            <span class="requirement-title">{{ numbers }} Numbers</span>
        </div>
    </div>
</template>
<script>
export default {
    emits: ['completed'],
    data: function() {
        return {
            completed: false,
            minimum: 8,
            maximum: null,
            uppercase: 1,
            numbers: 1,
            special: 1,
        }
    },
    created: function() {
        if (typeof(this.$cuztomisable['passwords']) == 'undefined') {
            return;
        }
        var requirements = this.$cuztomisable['passwords']['requirements'] ?? null;
        if (requirements == null) {
            return;
        }
        // Initializes the settings for the password requirements
        this.minimum = requirements['min'] ?? 8;
        this.maximum = requirements['max'] ?? null;
        this.uppercase = requirements['uppercase_characters'] ?? 1;
        this.special = requirements['special_characters'] ?? 1;
        this.numbers = requirements['numbers'] ?? 1;
    },
    methods: {
        check: function() {
            this.completed = false;
            // Checks to see if the requirements are met.
            if ((this.checked('minimum') || this.minimum == null) &&
                (this.checked('maximum') || this.maximum == null) &&
                this.checked('uppercase') && this.checked('numbers') &&
                this.checked('special')) {
                this.completed = true;
                this.$emit('completed', true);
            } else {
                this.$emit('completed', false);
            }
        },
        checked: function(key) {
            var length = this.password.length;
            var totalUppercase = length - this.password.replace(/[A-Z]/g, '').length;
            var totalNumbers = length - this.password.replace(/[0-9]/g, '').length;
            var totalSpecials = length - this.password.replace(/[@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/!?]/g, '').length;
            switch (key) {
                case 'minimum': return this.minimum == null || length >= this.minimum;
                case 'maximum': return this.maximum == null || length <= this.maximum;
                case 'uppercase': return totalUppercase >= this.uppercase;
                case 'numbers': return totalNumbers >= this.numbers;
                case 'special': return totalSpecials >= this.special;
            }
            return false;
        },
    },
    watch: {
        password: {
            immediate: true,
            handler: function(password) {
                this.check();
            },
            deep: true,
        },
    },
    props: {
        password: { type: String, default: '' },
    },
}
</script>