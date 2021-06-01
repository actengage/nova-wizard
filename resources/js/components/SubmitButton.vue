<template>
    <progress-button
        :type="type"
        :disabled="disabled || processing"
        :processing="processing"
        :class="classes"
        @click.native="$emit('click', $event)">
        {{ label }}
    </progress-button>
</template>

<script>
export default {
    props: {
        label: {
            type: String,
            required: true
        },
        type: {
            type: String,
            default: 'submit'
        },
        classes: [String, Object]
    },
    data() {
        return {
            disabled: this.$attrs.disabled,
            processing: this.$attrs.proccessing
        };
    }
};
</script>