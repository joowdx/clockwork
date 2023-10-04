<script setup>
import { onMounted, ref } from 'vue'

const props = defineProps(['mime', 'sample'])

const canvas = ref(null)

const draw = () => {
    const ctx = canvas.value.getContext('2d')

    ctx.fillStyle = 'white';

    ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);

    const sample = new Image

    sample.src = `data:${props.mime};base64,${props.sample}`

    sample.onload = () => {
        const scaleFactor = Math.min(canvas.value.width / sample.width, canvas.value.height / sample.height);

            // Calculate the dimensions of the scaled image
        const scaledWidth = sample.width * scaleFactor;
        const scaledHeight = sample.height * scaleFactor;

        // Calculate the position to center the scaled image on the canvas
        const x = (canvas.value.width - scaledWidth) / 2;
        const y = (canvas.value.height - scaledHeight) / 2;

        // Draw the image on the canvas
        ctx.drawImage(sample, x, y, scaledWidth, scaledHeight);

        // Add a diagonal watermark
        ctx.save(); // Save the current state
        ctx.translate(canvas.value.width / 2, canvas.value.height / 2); // Translate to the center
        ctx.rotate(-Math.PI / 4); // Rotate by 45 degrees
        ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
        ctx.font = '11px Verdana';

        const line1 = 'Unauthorized';
        const line2 = 'Reproduction';
        const line3 = 'Prohibited';

        const textWidth = Math.max(ctx.measureText(line1).width, ctx.measureText(line2).width, ctx.measureText(line3).width);

        // Center the three lines of text vertically
        const lineHeight = 16; // Adjust as needed
        const totalTextHeight = 3 * lineHeight + 5;
        const startY = -totalTextHeight / 2 + lineHeight; // Adjusted for vertical centering

        ctx.fillText(line1, -textWidth / 2, startY);
        ctx.fillText(line2, -textWidth / 2, startY + lineHeight);
        ctx.fillText(line3, -textWidth / 2.75, startY + 2 * lineHeight);

        ctx.restore();
    }
}

onMounted(draw)

</script>

<template>
    <canvas ref="canvas" width="200" height="100"></canvas>
</template>
