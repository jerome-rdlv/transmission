(function IIFE(playlist) {
    'use strict';
    
    if (!playlist) {
        return;
    }

    var index = 0;
    var current = playlist.sessions[index];
    var offset = parseInt(playlist.offset);
    var audio = null;
    init(playlist);
    
    function init() {
        
        // create audio node
        var node = document.createElement('audio');
        node.setAttribute('id', 'jdt-audio');
        node.setAttribute('src', current.url);
        node.setAttribute('type', current.meta.mime_type);
        node.setAttribute('preload', 'auto');
        node.setAttribute('style', 'position:absolute;width:1px;height:1px;opacity:0;');
        document.querySelector('body').appendChild(node);
        
        // start player at correct time
        audio = new MediaElement('jdt-audio', {
            startVolume: 1,
            success: onSourceReady,
            error: onSourceError
        });
        
        console.log('Cumulative length of transmissions: '+ formatLength(playlist.length));
        console.log('Current playing time: '+ playlist.time);
    }
    
    function onSourceError(audio) {
        console.error('Can not load', audio);
    }

    function onPlaying() {
        console.log('Playing '+ current.title +' ('+ formatLength(current.length) +') at '+ formatLength(audio.currentTime)); 
    }
    
    function formatLength(seconds) {
        var output = new Date(null, null, null, null, null, Math.max(0, seconds))
            .toTimeString()
            .replace(/^.*(\d{2}):(\d{2}):(\d{2}).*$/, '$1h $2m $3s');
        var days = parseInt(seconds / 3600 / 24);
        if (days > 0) {
            output = days +'d '+ output;
        }
        return output;
    }

    function onEnded() {
        index = (index + 1 < playlist.sessions.length) ? index + 1 : 0;
        current = playlist.sessions[index];
        offset = 0;
        audio.src = current.url;
    }

    function onData() {
        audio.currentTime = offset;
        audio.play();
    }

    function onSourceReady(audio) {
        audio.addEventListener('playing', onPlaying);
        audio.addEventListener('ended', onEnded);
        audio.addEventListener('loadeddata', onData);
        audio.load();
    }
    
})(jdanger_transmission);