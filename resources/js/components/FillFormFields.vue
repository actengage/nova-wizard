<script>
import FormComponent from './FormComponent';

export default {
    props: {
        mode: {
            type: String,
            default: 'form'
        },
        resourceName: String,
        resourceId: [String, Number],
        viaResource: String,
        viaResourceId: [String, Number],
        viaRelationship: [String, Number],
        Vue: {
            type: Function,
            required: true
        }
    },
    methods: {
        fill(steps, formData) {
            _.each(steps, (step, i) => {
                const key = Number(i) + 1;

                if(!formData[key]) {
                    formData[key] = new FormData();
                }

                _.each(step.fields, field => {
                    const instance = new (this.Vue.extend(FormComponent))({
                        parent: this,
                        propsData: {
                            field,
                            mode: this.mode,
                            resourceId: this.resourceId,
                            resourceName: this.resourceName,
                            viaResource: this.viaResource,
                            viaResourceId: this.viaResourceId,
                            viaRelationship: this.viaRelationship,
                        }
                    });

                    instance.fill(formData[key]);
                });
            });

            this.filled = true;
        }
    },
    data: () => ({
        filled: false
    })
};
</script>