<template>
    <div class="flex items-center justify-between">
        <div class="progress-bar">
            <button
                v-for="(step, i) in steps"
                :key="i"
                type="button"
                :class="{active: currentStep >= i + 1}"
                @click="$emit('click', i + 1)">
                <span class="text-truncate">{{ step.name }}</span>
            </button>
        </div>

        <div>
            <progress-button
                type="button"
                class="mb-3"
                :disabled="processing"
                :processing="processing"
                @click.native="$emit('finish')">
                {{ __('Finish & Close') }}
            </progress-button>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        steps: {
            type: Array,
            required: true
        },
        currentStep: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            processing: false
        };
    }
};
</script>

<style scoped>
button {
    white-space: nowrap
}

.text-truncate {
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.progress-bar {
    display: flex;
	overflow: hidden;
	counter-reset: step;
    padding: 1rem;
    margin-bottom: 1rem;
    width: 100%;
}

.progress-bar > button {
	list-style-type: none;
	text-transform: uppercase;
	font-size: .75rem;
    position: relative;
    text-align: center;
    flex: 1;
    outline: none;
}

.progress-bar > button > span {
    margin-top: 1rem;
    position: relative;
    top: 0.5rem;
}

.progress-bar > button:before {
	content: counter(step);
	counter-increment: step;
	width: 20px;
	line-height: 20px;
	display: block;
	font-size: 10px;
	color: #333;
	background: white;
	border-radius: 3px;
	margin: 0 auto 5px auto;
}

.progress-bar > button:after {
	content: '';
	width: 100%;
	height: 2px;
	background: white;
	position: absolute;
	left: -50%;
	top: 9px;
	z-index: -1;
}

.progress-bar > button:first-child:after {
	content: none; 
}

.progress-bar > button.active:before,
.progress-bar > button.active:after{
	background: var(--primary);
	color: var(--white);
}
</style>