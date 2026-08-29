import Hls from 'hls.js';

const mount = () => {
    const video = document.querySelector('video[data-live-player]');
    if (!video || video.dataset.mounted === 'true') return;
    video.dataset.mounted = 'true';
    const src = video.dataset.hlsUrl;
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
        return;
    }
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
    }
};

document.addEventListener('DOMContentLoaded', mount);
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', mount);
});
