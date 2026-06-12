<script setup>
import { Vue3Lottie } from "vue3-lottie";

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    className: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div :class="props.className">
        <Vue3Lottie :animationLink="props.src" :loop="true" :autoplay="true" />
    </div>
</template>
