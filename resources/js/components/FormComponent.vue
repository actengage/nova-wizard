<template>
    <div>
        <component
            :is="`${mode}-${field.component}`"
            ref="field"
            :field="field"
            :resource-id="resourceId"
            :resource-name="resourceName"
            :via-resource="viaResource"
            :via-resource-id="viaResourceId"
            :via-relationship="viaRelationship" />
    </div>
</template>

<script>
export default {
    props: {
        field: {
            type: Object,
            required: true
        },
        mode: {
            type: String,
            default: 'form'
        },
        resourceId: [String, Number],
        resourceName: String,
        viaResource: String,
        viaResourceId: [String, Number],
        viaRelationship: [String, Number]
    },
    methods: {
        async prepareToFill() {
            this.$mount();

            if(this.$refs.field.availableResources) {
                return await this.waitforAvailableResources();
            }
            
            return await Promise.resolve();
        },

        async fill(formData) {
            await this.prepareToFill();

            this.$refs.field.fill(formData);
            this.filled = true;
        },
        
        waitforAvailableResources() {
            return new Promise(resolve => {
                const unwatch = this.$watch('$refs.field.availableResources', () => {
                    unwatch();

                    resolve();
                }, {
                    deep: true
                });
            });
        }
    },
    data: () => ({
        filled: false
    })
}
</script>
