import deepDiff from 'deep-diff';
import { Errors } from 'laravel-nova';
import FillFormFields from './components/FillFormFields';
import ProgressBar from './components/ProgressBar';
import SubmitButton from './components/SubmitButton';

export default (Nova, Vue) => ({
    components: {
        SubmitButton
    },
    
    data() {
        return {
            currentStep: null,
            events: [],
            fillComponent: null,
            formData: {},
            fieldData: {},
            sessionId: null,
            steps: null,
            nextButton: null,
            prevButton: null,
            progressBar: null,
            saveChangesButton: null,
            subject: null,
            submittedViaNextButton: false,
            totalSteps: null
        };
    },

    watch: {

        steps(value, oldValue) {
            if(!oldValue && value) {
                this.createFillComponent(value);
                this.fillComponent.fill(this.formData);                
            }
        },

        ['subject.fields'](fields) {
            _.each(fields, (field, i) => {
                if(this.fieldData[field.attribute]) {
                    Object.assign(field, this.fieldData[field.attribute], {
                        panel: field.panel
                    });
                }
            });

        },

        submittedViaNextButton(value) {
            if(value) {
                this.$root.$refs.loading.start();
            }
            else {
                this.$root.$refs.loading.finish();
            }
            
            this.nextButton.disabled = value;
            this.nextButton.processing = value;
        }
    },

    async beforeRouteUpdate({ query: { step }}, from, next) {
        next();

        this.subject.loading = true;
        this.nextButton.label = this.lastStep() && 'Finish';
        this.prevButton.disabled = parseInt(step) === 1;
    
        await this.subject.getFields();
    },

    methods: {

        getFormData() {
            const formData = new FormData();

            formData.append('viaResource', this.viaResource);
            formData.append('viaResourceId', this.viaResourceId);
            formData.append('viaRelationship', this.viaRelationship);

            return Object.entries(this.formData).reduce((formData, [step, stepFormData]) => {
                Array.from(stepFormData).forEach(([key, value]) => {
                    formData.append(key, value);
                });

                return formData;
            }, formData);
        },

        createFillComponent(steps) {                    
            this.fillComponent = new (Vue.extend(FillFormFields))({
                parent: this,
                propsData: {
                    steps,
                    resourceName: this.resourceName,
                    resourceId: this.resourceId,
                    viaResource: this.viaResource,
                    viaResourceId: this.viaResourceId,
                    viaRelationship: this.viaRelationship,
                }
            });

            this.fillComponent.$mount();

            return this.fillComponent;
        },
        
        createNextButton() {
            const el = document.createElement('div');
    
            this.$el.querySelector('[type=submit]').parentElement.append(el);

            this.nextButton = new (Vue.extend(SubmitButton))({
                el,
                parent: this,
                propsData: {
                    classes: 'ml-3',
                    label: this.__(
                        !this.lastStep() ? 'Next Step' : 'Finish & Close'
                    )
                }
            });

            this.$el.querySelector('form').addEventListener('submit', e => {
                this.nextButton.disabled = true;
                this.nextButton.processing = true;
            });

            return this.nextButton;
        },

        createProgressBar() {
            const el = document.createElement('div');

            this.$el.querySelector('form').insertBefore(
                el, this.$el.querySelector('form').children[0]
            );
    
            this.progressBar = new (Vue.extend(ProgressBar))({
                el,
                parent: this,
                propsData: {
                    currentStep: this.currentStep,
                    steps: this.steps,
                }
            });

            this.progressBar.$on('click', async(step) => {
                this.$root.$refs.loading.start();

                try {
                    await this.validateStep();

                    this.$router.push({
                        query: { step }
                    });
                }
                catch (e) {
                    this.validateRequestFailed(e);
                    this.focusOnFirstError(e);
                }

                this.$root.$refs.loading.finish();
            });

            this.progressBar.$on('finish', async() => {
                this.$root.$refs.loading.start();
                this.progressBar.processing = true;

                try {
                    await this.validateStep();
                    await this.submitMultiStepForm();
                }
                catch (e) {
                    this.validateRequestFailed(e);
                    this.focusOnFirstError(e);
                }

                this.progressBar.processing = false;
                this.$root.$refs.loading.finish();
            });
        },
        
        createSaveChangesButton() {
            const el = document.createElement('div');
    
            this.$el.querySelector('[type=submit]').parentElement.append(el);

            this.saveChangesButton = new (Vue.extend(SubmitButton))({
                el,
                parent: this,
                propsData: {
                    label: 'Save & Continue',
                    type: 'button'
                }
            });

            this.saveChangesButton.$on('click', async(e) => {
                this.$root.$refs.loading.start();
                this.saveChangesButton.processing = true;

                try {
                    await this.validateStep();
                    await this.submitMultiStepForm(false);
                }
                catch (e) {
                    this.validateRequestFailed(e);
                    this.focusOnFirstError(e);
                }

                this.saveChangesButton.processing = false;
                this.$root.$refs.loading.finish();
            });
            
            return this.saveChangesButton;
        },

        createPrevButton() {
            const el = document.createElement('div');
        
            this.$el.querySelector('[type=submit]').parentElement.append(el);

            this.prevButton = new (Vue.extend(SubmitButton))({
                el,
                parent: this,
                propsData: {
                    classes: 'ml-3',
                    type: 'button',
                    label: this.__('Prev Step')
                }
            });

            this.prevButton.disabled = this.currentStep === 1;

            this.prevButton.$on('click', async() => {
                this.$root.$refs.loading.start();
                this.prevButton.processing = true;

                try {
                    await this.validateStep();

                    this.$router.push({
                        query: {
                            step: this.currentStep - 1
                        }
                    });    
                }
                catch (e) {
                    this.validateRequestFailed(e);
                    this.focusOnFirstError(e);
                }

                this.prevButton.processing = false;
                this.$root.$refs.loading.finish();
            });

            return this.prevButton;
        },

        focusOnFirstError(e) {
            if(e.response && e.response.data) {
                const entries = Object.entries(e.response.data.errors);

                if(entries.length) {
                    const [ key ] = entries[0];

                    const step = this.steps.indexOf(_.find(this.steps, step => {
                        return !!_.find(step.fields, ({ attribute }) => key === attribute);
                    })) + 1;

                    if(step && this.currentStep !== step) {
                        this.$router.push({
                            query: { step }
                        });
                    }
                }
            }
        },

        hideDefaultSubmitButtons() {
            Array.from(this.$parent.$el.querySelectorAll('button'))
                .filter(child => child !== this.$el)
                .forEach(el => el.style.display = 'none');
        },

        initialize() {
            if(this.$el.querySelector('[type=submit]')) {
                this.hideDefaultSubmitButtons();
                
                if(this.resourceId) {
                    this.createSaveChangesButton();
                }

                this.createPrevButton();
                this.createNextButton();
                this.createProgressBar();
            }
        },
        
        lastStep() {
            return this.currentStep === this.totalSteps;
        },

        async submitViaNextButton(e) {
            e.preventDefault();
            
            this.submittedViaNextButton = true;
            this.canLeave = true;

            try {
                await this.validateStep();

                if(!this.lastStep()) {
                    this.$router.push({
                        query: {
                            step: this.currentStep + 1
                        }
                    });
                }
                else {
                    await this.submitMultiStepForm();
                }
            }
            catch (e) {
                this.validateRequestFailed(e);
                this.focusOnFirstError(e);
            }

            this.submittedViaNextButton = false;
        },

        validateFormData() {
            const formData = this.formData[this.currentStep] = new FormData();
            
            formData.set('editing', true);
            formData.set('editMode', this.resourceId ? 'update' : 'create');

            return _.tap(formData, formData => {
                _.each(this.subject.fields, field => field.fill(formData));
            });
        },

        async validateStep() {
            const formData = this.validateFormData();

            const uri = `/nova-vendor/wizard/validate/${this.resourceName}${this.resourceId ? `/${this.resourceId}` : ''}`;

            const { data: { fields } } = await Nova.request().post(uri, formData);
                
            fields.forEach(field => {
                this.fieldData[field.attribute] = field;
            });
    
            this.subject.validationErrors = new Errors();
        },

        async submitMultiStepForm(shouldRedirect = true) {
            const {
                data: {
                    redirect
                }
            } = await (
                this.subject.createRequest || this.subject.updateRequest
            )();

            Nova.success(
                this.__('The :resource was saved!', {
                    resource: this.resourceInformation.singularLabel.toLowerCase(),
                })
            );

            shouldRedirect && this.$router.push({ path: redirect }, () => {
                window.scrollTo(0, 0);
            });
        },

        validateRequestFailed(error) {
            this.submittedViaNextButton = false;

            if(this.resourceInformation.preventFormAbandonment) {
                this.canLeave = false;
            }

            if(!error.response) {
                throw error;
            }

            if(error.response.status == 422) {
                this.subject.validationErrors = new Errors(error.response.data.errors);
          
                Nova.error(this.__('There was a problem submitting the form.'));
            }

            if(error.response.status == 409) {
                Nova.error(
                    this.__(
                        'Another user has updated this resource since this page was loaded. Please refresh the page and try again.'
                    )
                );
            }
        },

        on(key, fn) {
            const handler = (...args) => {
                fn(...args);
            };

            this.events.push({
                key, handler
            });

            Nova.$on(key, handler);
        },

        once(key, fn) {
            const handler = (...args) => {
                fn(...args);
            };
            
            this.events.push({
                key, handler
            });

            Nova.$once(key, handler);
        },
    },

    destroyed() {
        this.events.forEach(({ key, handler }) => Nova.$off(key, handler));
    },

    created() {
        this.on('nova.wizard.request', config => {
            if(this.sessionId) {
                config.headers['wizard-session-id'] = this.sessionId;
            }
        });

        // These events are broken apart to be executed in specific orders.
        this.once('nova.wizard.response', () => {
            this.subject = this.$children[0]
                && this.$children[0].submitViaCreateResource
                ? this.$children[0]
                : this;

            this.subject.createResourceFormData = () => this.getFormData();
            this.subject.submitViaCreateResource = async(e) => this.submitViaNextButton(e);
            this.subject.submitViaUpdateResource = async(e) => this.submitViaNextButton(e);
            this.resourceInformation = this.subject.resourceInformation;       

            this.$watch(() => this.subject.loading, () => {
                this.initialize();
            });
        });

        this.on('nova.wizard.response', ({ data: { steps }, headers }) => {
            if(headers['wizard-session-id']) {
                this.sessionId = headers['wizard-session-id'];
                this.currentStep = Number(headers['wizard-current-step']);
                this.totalSteps = Number(headers['wizard-total-steps']);


                if(steps) {
                    this.steps = steps;
                }
            }
        });
    }
});