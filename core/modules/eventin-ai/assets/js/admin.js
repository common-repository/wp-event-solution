; (function ($) {

    $(document).ready(() => {
        const modalClose = $('.etn-close');
        const modalBackdrop = $('.etn-ai-modal-backdrop');
        const modal = $('.etn-ai-modal');
        const aiBuyButton = $('.etn-ai-buy-button');
        const proActivated = eventin_ai_local_data.evnetin_pro_active;
        const eventinAIActivated = eventin_ai_local_data.evnetin_ai_active;

        // Hide the modal when close button is clicked or backdrop is clicked
        modalClose.on('click', () => modalBackdrop.fadeOut(300));
        modalBackdrop.on('click', () => modalBackdrop.fadeOut(300));
        modal.on('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            e.stopImmediatePropagation();
        });

        aiBuyButton.on('click', (e) => {
            e.stopPropagation();
        });


        const { addAction, doAction } = wp.hooks;


        addAction('eventin-ai-text-generator-modal', 'eventin-ai-free', (props) => {

            const { visible } = props

            if (!visible) {
                return modalBackdrop.fadeOut(300);
            }

            if (!proActivated || !eventinAIActivated) {
                return modalBackdrop.fadeIn(300);
            }

            doAction("eventin-ai-modal-visible", props);
        });
    });

})(jQuery);