document.addEventListener("DOMContentLoaded", function(event) {
    const browserSync = document.createElement('script')

    browserSync.setAttribute('async', true)

    browserSync.setAttribute('src', 'http://HOST:3000/browser-sync/browser-sync-client.js?v=2.27.5'.replace('HOST', location.hostname))

    document.body.appendChild(browserSync)
});
