import mixin from './mixin';

Nova.booting((Vue, router, store) => {
    // Register the components.  
    Vue.component('IndexWizard', require('./components/IndexField'));
    Vue.component('DetailWizard', require('./components/DetailField'));
    Vue.component('FormWizard', require('./components/FormField'));
 
    // Add a request interceptor to append to current step to the request
    Nova.request().interceptors.request.use(function(config) {
        const step = parseInt(router.currentRoute.query.step || 1);

        if(config.method === 'get') {
            config.params = Object.assign(config.params || {}, { step });
        }
        else if(config.data) {
            config.data.set('step', step);
        }
    
        return config;
    });
    
    Nova.request().interceptors.response.use(response => {
        Nova.$emit('nova.http.response', response);

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

        // Only add mixin if route has a resourceId, resourceName, and on first load
        if(resourceName && (name === 'create' || name === 'edit') && matched.components.default.mixins.indexOf(mixin) === -1) {
            if(matched.components.default.computed && matched.components.default.computed.updateResourceFormData) {
                matched.components.default.computed.updateResourceFormData = _.wrap(
                    matched.components.default.computed.updateResourceFormData,
                    function(fn) {
                        const originalData = fn.call(this);

                        if(this.hasMultipleSteps) {
                            const formData = this.getFormData();

                            formData.set('_method', originalData.get('_method'));
                            // formData.set('_retrieved_at', );

                            return formData;
                        }

                        return originalData;
                    }
                );
            }

            matched.components.default.mixins.push(mixin(Nova, Vue));
        }
        
        next();
    });
});