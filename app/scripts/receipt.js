 document.addEventListener('DOMContentLoaded', () => {
        // Simple visual confirmation/animation when the page loads
        const container = document.querySelector('.receipt-container');
        if (container) {
            // Add a temporary subtle pulse animation to the header border
            const header = document.querySelector('.receipt-container');
            header.style.animation = 'fadeIn 0.8s ease-out, pulseBorder 2s ease-in-out 3';
            
            // Remove the pulse after 6 seconds to stop the animation
            setTimeout(() => {
                 header.style.animation = 'fadeIn 0.8s ease-out';
            }, 6000);
        }
    });