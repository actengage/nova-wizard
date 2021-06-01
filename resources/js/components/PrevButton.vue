<template>
    <progress-button
        type="button"
        class="mr-3"
        :disabled="disabled"
        :processing="processing"
        @click.native="$emit('click')">
        Prev Step
    </progress-button>
</template>

<script>
export default {
    data() {
        return {
            disabled: this.$attrs.disabled,
            processing: this.$attrs.proccessing
        };
    }
};
</script>