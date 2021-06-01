<template>
    <div>
        <div 
            v-for="({ fields }, i) in steps"
            ref="steps"
            :key="i"
            :data-step="Number(i) + 1">
            <component
                :is="`${mode}-${field.component}`"
                v-for="(field, index) in fields"
                :ref="`fields[${Number(i) + 1}]`"
                :key="`${i}-${index}`"
                :resource-id="resourceId"
                :resource-name="resourceName"
                :field="field"
                :via-resource="viaResource"
                :via-resource-id="viaResourceId"
                :via-relationship="viaRelationship" />
        </div>
    </div>
</template>

<script>
export default {
    props: {
        steps: Array,
        mode: {
            type: String,
            default: 'form'
        },
        resourceName: String,
        resourceId: [String, Number],
        viaResource: String,
        viaResourceId: [String, Number],
        viaRelationship: [String, Number]
    },
    methods: {
        /*
        mergeInto(parentData) {
            return Array.from(this.fill(new FormData()))
                .filter(([ key ]) => {
                    return !parentData.has(key);
                })
                .reduce((parentData, [key, value]) => {
                    parentData.append(key, value);

                    return parentData;
                }, parentData);
        },
        */
        fill(formData) {
            this.$refs.steps.forEach(step => {
                const index = Number(step.getAttribute('data-step'));

                formData[index] = new FormData();

                if(this.$refs[`fields[${index}]`]) {
                    this.$refs[`fields[${index}]`].forEach(field => {
                        field.fill(formData[index]);
                    });
                }
            });
        }
    }
};
</script>