/** Contact details for organiser support (set in the frontend .env; empty = hidden). */
export function useSupport() {
    const config = useRuntimeConfig().public;
    const whatsapp = String(config.supportWhatsapp || '').replace(/\D/g, '');
    const email = String(config.contactEmail || '');
    const videoUrl = String(config.helpVideoUrl || '');

    /** wa.me link with a message that says where the organiser is in the app. */
    function whatsappHref(context?: string) {
        const where = context ? `\n\n(I’m on: ${context})` : '';
        return whatsappLink(whatsapp, `Hello Ballotify support, I need help with: ${where}`);
    }

    /** A YouTube or Vimeo link turned into an embeddable URL; '' when not recognised. */
    const videoEmbed = (() => {
        const yt = videoUrl.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
        if (yt) return `https://www.youtube-nocookie.com/embed/${yt[1]}`;
        const vimeo = videoUrl.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (vimeo) return `https://player.vimeo.com/video/${vimeo[1]}`;
        return '';
    })();

    return { whatsapp, email, videoUrl, videoEmbed, whatsappHref };
}
