import mixin from './mixin';

Nova.booting((Vue, router, store) => {
    let isWizardInstance = false;
    
    // Add a request interceptor to append to current step to the request
    Nova.request().interceptors.request.use(function(config) {
        const step = parseInt(router.currentRoute.query.step || 1);

        config.step = step;

        if(config.method === 'get') {
            config.params = Object.assign(config.params || {}, { step });
        }
        else if(config.data) {
            config.data.set('step', step);
        }
    
        if(isWizardInstance) {
            Nova.$emit('nova.wizard.request', config);
        }
        
        return config;
    });
    
    Nova.request().interceptors.response.use(response => {
        if(!isWizardInstance) {
            isWizardInstance = !!response.headers['wizard-session-id'];
        }

        if(isWizardInstance) {
            Nova.$emit('nova.wizard.response', response);
        }

        return response;
    });

    router.beforeEach((to, from, next) => {
        const {
            name,
            matched: [ matched ],
            params: {
                resourceName
            }
        } = to;

        // Make sure the wizard instance is always false.
        isWizardInstance = false;

        // Only add mixin if route has a resourceId, resourceName, and on first load
        if(resourceName && (name === 'create' || name === 'edit') && matched.components.default.mixins.indexOf(mixin) === -1) {
            if(matched.components.default.computed && matched.components.default.computed.updateResourceFormData) {
                matched.components.default.computed.updateResourceFormData = _.wrap(
                    matched.components.default.computed.updateResourceFormData,
                    function(fn) {
                        const originalData = fn.call(this);

                        if(this.sessionId) {
                            const formData = this.getFormData();

                            formData.set('_method', originalData.get('_method'));
                            
                            return formData;
                        }

                        return originalData;
                    }
                );
            }

            // Push the mixin into the default component.
            matched.components.default.mixins.push(mixin(Nova, Vue));
        }
        
        next();
    });
});