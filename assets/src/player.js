(function IIFE(playlist) {
    'use strict';
    
    if (!playlist) {
        return;
    }

    var index = playlist.index;
    var current = playlist.sessions[index];
    var offset = playlist.offset;
    var audio = null;
    init(playlist);
    
    function init() {
        
        // create audio node
        var node = document.createElement('audio');
        node.setAttribute('id', 'jdt-audio');
        node.setAttribute('src', current.url);
        node.setAttribute('type', current.meta.type);
        node.setAttribute('preload', 'auto');
        node.setAttribute('style', 'position:absolute;width:1px;height:1px;opacity:0;');
        document.querySelector('body').appendChild(node);
        
        // start player at correct time
        audio = new MediaElement('jdt-audio', {
            startVolume: 1,
            success: onSourceReady,
            error: onSourceError
        });
        
        console.log('Cumulative duration of transmissions: '+ formatDuration(playlist.duration));
        console.log('Current playing time: '+ playlist.time);
    }
    
    function onSourceError(audio) {
        console.error('Can not load', audio);
    }

    function onPlaying() {
        console.log('Playing '+ current.title +' at '+ formatDuration(audio.currentTime)); 
    }
    
    function formatDuration(seconds) {
        var output = new Date(null, null, null, null, null, seconds).toTimeString().match(/\d{2}:\d{2}:\d{2}/)[0];
        var days = parseInt(seconds / 3600 / 24);
        if (days > 0) {
            output = days +'d '+ output;
        }
        return output;
    }

    function onEnded() {
        console.log('End of '+ current.title);
        index = index + 1 < playlist.sessions.length ? index + 1 : 0;
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